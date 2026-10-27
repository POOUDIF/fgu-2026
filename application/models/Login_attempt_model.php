<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Pembatas percobaan login per kombinasi (email, IP). */
class Login_attempt_model extends CI_Model
{
	public function record(string $email, string $ip): void
	{
		$this->db->insert('login_attempts', array('email' => mb_strtolower($email), 'ip_address' => $ip));
	}

	public function recent_failures(string $email, string $ip, int $windowSeconds): int
	{
		return (int) $this->db->where('email', mb_strtolower($email))
			->where('ip_address', $ip)
			->where('attempted_at >=', date('Y-m-d H:i:s', time() - $windowSeconds))
			->count_all_results('login_attempts');
	}

	public function clear(string $email, string $ip): void
	{
		$this->db->where(array('email' => mb_strtolower($email), 'ip_address' => $ip))->delete('login_attempts');
	}

	/** Bersihkan catatan lama (dipanggil sesekali saat login). */
	public function purge(int $olderThanSeconds = 86400): void
	{
		$this->db->where('attempted_at <', date('Y-m-d H:i:s', time() - $olderThanSeconds))->delete('login_attempts');
	}
}
