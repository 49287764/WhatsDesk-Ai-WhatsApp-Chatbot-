<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Ai_bot
 *
 * OpenAI-compatible Chat Completions client with function/tool calling.
 * Works with OpenAI, DeepSeek, or any OpenAI-compatible endpoint.
 * Uses plain cURL — no Composer dependencies.
 */
class Ai_bot
{
	protected $api_key = '';
	protected $base_url = '';
	protected $model = '';
	protected $temperature = 0.3;
	protected $max_tokens = 800;

	public function __construct()
	{
		$CI =& get_instance();
		if ( ! isset($CI->settings_model))
		{
			$CI->load->model('settings_model');
		}
		$s = $CI->settings_model->merged();

		$this->api_key     = $s['ai_api_key'];
		$this->base_url    = rtrim((string)$s['ai_base_url'], '/');
		$this->model       = $s['ai_model'] !== '' ? $s['ai_model'] : 'gpt-4o-mini';
		$this->temperature = $s['ai_temperature'] !== '' ? (float)$s['ai_temperature'] : 0.3;
		$this->max_tokens  = $s['ai_max_tokens'] !== '' ? (int)$s['ai_max_tokens'] : 800;
	}

	public function is_configured()
	{
		return $this->api_key !== '' && $this->base_url !== '';
	}

	/**
	 * The configured model name (useful for test/status messages).
	 */
	public function model_name()
	{
		return $this->model;
	}

	/**
	 * Run a chat completion, automatically executing tool calls until the
	 * model produces a final answer.
	 *
	 * @param array    $messages      OpenAI messages array
	 * @param array    $tools         OpenAI tools (function) definitions
	 * @param callable $tool_executor function($tool_name, array $args) => string
	 * @param int      $max_turns     safety limit on tool-call rounds
	 * @return array|null             array('content' => string) or NULL on failure
	 */
	public function chat(array $messages, array $tools = array(), $tool_executor = NULL, $max_turns = 5)
	{
		if ( ! $this->is_configured())
		{
			log_message('error', 'AI bot not configured (missing API key).');
			return NULL;
		}

		$turn = 0;
		while ($turn < $max_turns)
		{
			// Rebuild the payload each turn so tool results appended to
			// $messages are actually sent back to the model (the loop is
			// what makes multi-step tool calling work).
			$payload = array(
				'model'       => $this->model,
				'messages'    => $messages,
				'temperature' => $this->temperature,
				'max_tokens'  => $this->max_tokens,
			);
			if ($tools)
			{
				$payload['tools'] = $tools;
				$payload['tool_choice'] = 'auto';
			}

			$response = $this->_post($payload);

			// Transient provider hiccups (rate limits, 5xx, timeouts) are
			// common on free tiers — retry once with a short pause before
			// giving up so customers still get an AI answer.
			if ($response === NULL && $turn < 1)
			{
				usleep(1800000); // 1.8s
				$response = $this->_post($payload);
			}
			if ($response === NULL)
			{
				return NULL;
			}
			$choice = isset($response['choices'][0]) ? $response['choices'][0] : NULL;
			if ( ! $choice)
			{
				log_message('error', 'AI API returned no choices.');
				return NULL;
			}
			$message = isset($choice['message']) ? $choice['message'] : array();
			$content = isset($message['content']) ? (string)$message['content'] : '';
			$tool_calls = isset($message['tool_calls']) ? $message['tool_calls'] : array();

			if ( ! $tool_calls)
			{
				return array('content' => $content, 'turns' => $turn + 1);
			}

			// Feed the assistant's tool-call message back, then run each tool.
			$messages[] = $message;
			foreach ($tool_calls as $call)
			{
				$name = isset($call['function']['name']) ? (string)$call['function']['name'] : '';
				$args = array();
				if (isset($call['function']['arguments']))
				{
					$decoded = json_decode((string)$call['function']['arguments'], TRUE);
					if (is_array($decoded))
					{
						$args = $decoded;
					}
				}
				$result = '';
				if ($tool_executor !== NULL && is_callable($tool_executor))
				{
					$result = (string)call_user_func($tool_executor, $name, $args);
				}
				else
				{
					$result = 'Tool is not available.';
				}
				$messages[] = array(
					'role'         => 'tool',
					'tool_call_id' => isset($call['id']) ? (string)$call['id'] : '',
					'content'      => $result,
				);
			}
			$turn++;
		}

		return array('content' => 'I could not complete that request. Please try again.', 'turns' => $turn);
	}

	protected function _post(array $payload)
	{
		$url = $this->base_url . '/chat/completions';

		$ch = curl_init($url);
		curl_setopt_array($ch, array(
			CURLOPT_RETURNTRANSFER => TRUE,
			CURLOPT_POST           => TRUE,
			CURLOPT_POSTFIELDS     => json_encode($payload),
			CURLOPT_HTTPHEADER     => array(
				'Authorization: Bearer ' . $this->api_key,
				'Content-Type: application/json',
			),
			CURLOPT_TIMEOUT        => 60,
			CURLOPT_CONNECTTIMEOUT => 10,
		));
		$response = curl_exec($ch);
		$http_code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$error = curl_error($ch);
		curl_close($ch);

		if ($response === FALSE)
		{
			log_message('error', 'AI API curl error: ' . $error);
			return NULL;
		}
		$decoded = json_decode($response, TRUE);
		if ($http_code >= 200 && $http_code < 300)
		{
			return $decoded;
		}
		log_message('error', 'AI API error (' . $http_code . '): ' . $response);
		return NULL;
	}
}
