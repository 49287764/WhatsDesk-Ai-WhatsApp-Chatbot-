<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| AI Provider Configuration
|--------------------------------------------------------------------------
| The bot talks to any OpenAI-compatible Chat Completions API
| (with function/tool calling support).
|
|   ai_provider  -> 'openai'   (https://api.openai.com/v1, model gpt-4o-mini)
|                   'groq'     (https://api.groq.com/openai/v1, model openai/gpt-oss-20b — free tier)
|                   'deepseek' (https://api.deepseek.com/v1, model deepseek-chat)
|                   'custom'   (any OpenAI-compatible endpoint via ai_base_url)
|
| These are FALLBACK values. Real values can be saved in the admin panel
| (Settings page) and are stored in the `settings` table.
|--------------------------------------------------------------------------
*/
$config['ai_provider']  = 'openai';
$config['ai_api_key']   = '';
$config['ai_model']     = 'gpt-4o-mini';
$config['ai_base_url']  = 'https://api.openai.com/v1';
$config['ai_temperature'] = 0.3;
$config['ai_max_tokens']  = 800;
