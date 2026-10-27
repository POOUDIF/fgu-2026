<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Pembatas laju sederhana berbasis cache berkas (berlaku lintas worker PHP-FPM).
 * Dipakai untuk endpoint publik tanpa login (kiriman karya).
 */
class Rate_limiter
{
	private $CI;

	public function __construct()
	{
		$this->CI =& get_instance();
		$this->CI->load->driver('cache', array('adapter' => 'file'));
	}

	/** @return bool TRUE bila masih diizinkan (dan hit dicatat); FALSE bila batas terlampaui */
	public function hit(string $key, int $max, int $windowSeconds): bool
	{
		$id = 'rl_'.md5($key);
		$now = time();
		$state = $this->CI->cache->get($id);

		if ( ! is_array($state) || ($now - (int) $state[1]) >= $windowSeconds)
		{
			$this->CI->cache->save($id, array(1, $now), $windowSeconds);

			return TRUE;
		}
		if ((int) $state[0] >= $max)
		{
			return FALSE;
		}
		$this->CI->cache->save($id, array((int) $state[0] + 1, (int) $state[1]), max(1, $windowSeconds - ($now - (int) $state[1])));

		return TRUE;
	}
}
