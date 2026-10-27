<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use App\Domain\Role;
use App\Support\Password;

/**
 * Perintah baris perintah (hanya CLI):
 *   php public/index.php cli migrate [versi|latest]
 *   php public/index.php cli create_admin                      (interaktif: tanya email, nama, sandi)
 *   php public/index.php cli create_admin email%40contoh.com Nama%20Lengkap [sandi]
 *     (argumen CLI melewati validasi URI CI3, jadi '@' dan spasi harus di-URL-encode)
 */
#[\AllowDynamicProperties]
class Cli extends CI_Controller
{
	public function __construct()
	{
		parent::__construct();
		if ( ! is_cli())
		{
			show_404();
		}
		$this->db->query("SET time_zone = '+07:00'");
	}

	public function migrate($version = 'latest')
	{
		$this->load->library('migration');
		$result = $version === 'latest' ? $this->migration->latest() : $this->migration->version((int) $version);
		if ($result === FALSE)
		{
			fwrite(STDERR, 'Migrasi gagal: '.$this->migration->error_string().PHP_EOL);
			exit(1);
		}
		echo 'Migrasi selesai. Versi saat ini: '.(is_string($result) || is_int($result) ? $result : 'tidak berubah').PHP_EOL;
	}

	public function create_admin($email = NULL, $name = NULL, $password = NULL)
	{
		$this->load->model('User_model');
		if ($email === NULL || $name === NULL)
		{
			$email = $this->prompt('Email: ');
			$name = $this->prompt('Nama lengkap: ');
			$typed = $this->prompt('Sandi (kosongkan untuk acak, min. 8 karakter): ');
			$password = $typed === '' ? NULL : $typed;
		}
		else
		{
			$email = urldecode($email);
			$name = urldecode($name);
			$password = $password === NULL ? NULL : urldecode($password);
		}
		$email = $this->User_model->normalize_email($email);
		if ( ! filter_var($email, FILTER_VALIDATE_EMAIL))
		{
			fwrite(STDERR, 'Email tidak valid.'.PHP_EOL);
			exit(1);
		}
		if ($this->User_model->email_exists($email))
		{
			fwrite(STDERR, 'Email sudah terdaftar.'.PHP_EOL);
			exit(1);
		}
		$generated = $password === NULL;
		$password = $password ?? Password::generate(16);
		if (($error = Password::validate($password)) !== NULL)
		{
			fwrite(STDERR, $error.PHP_EOL);
			exit(1);
		}
		$id = $this->User_model->create(array(
			'full_name' => trim($name),
			'email' => $email,
			'password_hash' => Password::hash($password),
			'role' => Role::SUPER_ADMIN,
			'village_id' => NULL,
			'must_change_password' => 1,
		));
		if ($id === NULL)
		{
			fwrite(STDERR, 'Gagal membuat akun: '.$this->db->error()['message'].PHP_EOL);
			exit(1);
		}
		echo "Super Admin dibuat (id $id).".PHP_EOL.'Email: '.$email.PHP_EOL;
		if ($generated)
		{
			echo 'Sandi sementara: '.$password.PHP_EOL;
		}
		echo 'Sandi wajib diganti saat login pertama.'.PHP_EOL;
	}

	private function prompt(string $label): string
	{
		fwrite(STDOUT, $label);

		return trim((string) fgets(STDIN));
	}
}
