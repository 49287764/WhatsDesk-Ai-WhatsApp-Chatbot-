<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Admin_model extends CI_Model
{
	/**
	 * Verify username/password. Returns the user row on success, else NULL.
	 *
	 * Passwords are always stored as bcrypt hashes. The factory seed row in
	 * the SQL dump carries a random, unusable password and is flagged with
	 * is_seed = 1 — the owner claims it through the "Create your account"
	 * page, which overwrites both username and password. There are no
	 * default credentials anyone can log in with.
	 */
	public function verify($username, $password)
	{
		$user = $this->db->get_where('admin_users', array('username' => $username), 1)->row_array();
		if ( ! $user)
		{
			return NULL;
		}

		$stored = $user['password'];
		if (strncmp($stored, '$2y$', 4) === 0)
		{
			$ok = password_verify($password, $stored);
		}
		else
		{
			// Legacy plaintext seed from very old installs — never accepted
			// unless it matches exactly, and immediately upgraded on success.
			$ok = hash_equals($stored, $password);
			if ($ok)
			{
				$this->update_password($user['id'], $password);
			}
		}

		return $ok ? $user : NULL;
	}

	public function update_password($id, $plain_password)
	{
		$this->db->where('id', (int)$id);
		return $this->db->update('admin_users', array(
			'password' => password_hash($plain_password, PASSWORD_BCRYPT),
		));
	}

	public function change_password($username, $current, $new)
	{
		$user = $this->verify($username, $current);
		if ( ! $user)
		{
			return FALSE;
		}
		return $this->update_password($user['id'], $new);
	}

	/**
	 * Whether the panel account is still on the factory seed row — i.e. the
	 * owner has NOT claimed it yet via "Create your account".
	 */
	public function is_seed_account()
	{
		$user = $this->db->order_by('id', 'ASC')->limit(1)->get('admin_users')->row_array();
		if ( ! $user)
		{
			return FALSE;
		}
		if (array_key_exists('is_seed', $user))
		{
			return (int)$user['is_seed'] === 1;
		}
		// Old installs without the column: a bcrypt hash that is NOT a hash
		// of 'admin123' means it was already claimed.
		return strncmp($user['password'], '$2y$', 4) === 0
			? password_verify('admin123', $user['password'])
			: TRUE;
	}

	/**
	 * Claim/create the panel account during onboarding: overwrites the seed
	 * admin with the owner's chosen username + password (bcrypt) and marks
	 * the account as claimed.
	 */
	public function create_account($username, $password)
	{
		$first = $this->db->order_by('id', 'ASC')->limit(1)->get('admin_users')->row_array();
		$data = array(
			'username' => $username,
			'password' => password_hash($password, PASSWORD_BCRYPT),
			'is_seed'  => 0,
		);
		if ($first)
		{
			$this->db->where('id', (int)$first['id']);
			$this->db->update('admin_users', $data);
			return $first['id'];
		}
		$this->db->insert('admin_users', $data);
		return $this->db->insert_id();
	}

	/**
	 * All admin accounts (newest first) for the Accounts management page.
	 */
	public function list_all()
	{
		return $this->db->order_by('id', 'ASC')->get('admin_users')->result_array();
	}

	/**
	 * Add an additional admin/staff account.
	 */
	public function create_user($username, $password)
	{
		$data = array(
			'username' => $username,
			'password' => password_hash($password, PASSWORD_BCRYPT),
			'is_seed'  => 0,
		);
		$this->db->insert('admin_users', $data);
		return $this->db->insert_id();
	}

	public function get_by_id($id)
	{
		return $this->db->where('id', (int)$id)->limit(1)->get('admin_users')->row_array();
	}

	public function get_by_username($username)
	{
		return $this->db->where('username', $username)->limit(1)->get('admin_users')->row_array();
	}

	/**
	 * Whether this is the last remaining account (never allow deleting it).
	 */
	public function is_last_account($id)
	{
		$count = (int)$this->db->count_all('admin_users');
		if ($count <= 1)
		{
			return TRUE;
		}
		$user = $this->get_by_id($id);
		return $user ? ((int)$user['is_seed'] === 1 && $count === 1) : TRUE;
	}

	public function delete_user($id)
	{
		$this->db->where('id', (int)$id);
		return $this->db->delete('admin_users');
	}
}
