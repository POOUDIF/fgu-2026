<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use App\Domain\Role;

/**
 * Perlindungan endpoint ekspor: autentikasi, otorisasi admin, dan pembatas laju per pengguna.
 * Pembatas memakai cache berkas bersama (bukan memori per proses) sehingga berlaku lintas worker PHP-FPM.
 */
class Export_guard
{
	public const MIN_INTERVAL = 3;
	private $CI;

	public function __construct()
	{
		$this->CI =& get_instance();
	}

	/**
	 * @param array<string,mixed>|null $user
	 * @return array{status:int,error:string}|null NULL bila boleh lanjut
	 */
	public function authorize(?array $user): ?array
	{
		if ($user === NULL)
		{
			return array('status' => 401, 'error' => 'Anda belum login.');
		}
		if ( ! Role::isAdmin($user['role']))
		{
			return array('status' => 403, 'error' => 'Anda tidak berwenang mengekspor data.');
		}

		$this->CI->load->driver('cache', array('adapter' => 'file'));
		$key = 'export_'.(int) $user['id'];
		if ($this->CI->cache->get($key) !== FALSE)
		{
			return array('status' => 429, 'error' => 'Ekspor terlalu sering. Tunggu beberapa detik lalu coba lagi.');
		}
		$this->CI->cache->save($key, time(), self::MIN_INTERVAL);

		return NULL;
	}
}
