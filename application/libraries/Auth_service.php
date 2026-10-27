<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use App\Support\ActionResult;
use App\Support\Password;

/**
 * Autentikasi berbasis sesi CI3 (driver database) + pembatasan percobaan login.
 */
class Auth_service
{
	/** Hash bcrypt dummy untuk meratakan waktu respons saat email tidak ada. */
	private const DUMMY_HASH = '$2y$10$usesomesillystringfore7hnbRJHxXVLeakoG8K30oukPsA.ztMG';

	private $CI;
	private $user = FALSE;

	public function __construct()
	{
		$this->CI =& get_instance();
		$this->CI->load->model('User_model');
	}

	public function login(string $email, string $password): ActionResult
	{
		$email = $this->CI->User_model->normalize_email($email);

		$user = $this->CI->User_model->find_for_login($email);
		$hash = $user['password_hash'] ?? self::DUMMY_HASH;
		$valid = password_verify($password, $hash) && $user !== NULL && (int) $user['is_active'] === 1;

		if ( ! $valid)
		{
			// Pesan sama untuk email tidak ada / sandi salah / akun nonaktif (hindari user enumeration).
			return ActionResult::fail('Email atau kata sandi salah.');
		}

		if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT))
		{
			$this->CI->User_model->set_password((int) $user['id'], Password::hash($password), (bool) $user['must_change_password']);
		}

		// Cegah session fixation
		$this->CI->session->sess_regenerate(TRUE);
		$this->CI->session->set_userdata('user_id', (int) $user['id']);
		$this->CI->User_model->touch_login((int) $user['id']);
		$this->user = FALSE;

		return ActionResult::ok('', array('user_id' => (int) $user['id']));
	}

	public function logout(): void
	{
		$this->CI->session->sess_destroy();
		$this->user = FALSE;
	}

	/** Pengguna yang sedang login (tanpa password_hash), atau NULL. Di-cache per request. */
	public function user(): ?array
	{
		if ($this->user !== FALSE)
		{
			return $this->user;
		}
		$id = (int) $this->CI->session->userdata('user_id');
		$user = $id > 0 ? $this->CI->User_model->find($id) : NULL;
		if ($user !== NULL && (int) $user['is_active'] !== 1)
		{
			$user = NULL;
		}
		if ($user === NULL && $id > 0)
		{
			$this->CI->session->unset_userdata('user_id'); // akun sudah dihapus / dinonaktifkan
		}

		return $this->user = $user;
	}

	/** Ganti sandi akun sendiri; sandi lama wajib benar. */
	public function change_own_password(int $userId, string $current, string $new, string $confirm): ActionResult
	{
		$hash = $this->CI->User_model->find_hash($userId);
		if ($hash === NULL || ! password_verify($current, $hash))
		{
			return ActionResult::fail('Kata sandi saat ini salah.');
		}
		if (($error = Password::validate($new)) !== NULL)
		{
			return ActionResult::fail($error);
		}
		if ($new !== $confirm)
		{
			return ActionResult::fail('Konfirmasi kata sandi tidak sama.');
		}
		if ($new === $current)
		{
			return ActionResult::fail('Kata sandi baru harus berbeda dari yang lama.');
		}
		$this->CI->User_model->set_password($userId, Password::hash($new), FALSE);
		$this->user = FALSE;

		return ActionResult::ok('Kata sandi berhasil diubah.');
	}
}
