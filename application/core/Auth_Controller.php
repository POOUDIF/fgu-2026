<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Controller untuk halaman yang mewajibkan login.
 * Pengguna yang wajib mengganti sandi dialihkan ke halaman ganti sandi.
 */
class Auth_Controller extends MY_Controller
{
	public function __construct()
	{
		parent::__construct();

		if ($this->current_user === NULL)
		{
			if ($this->input->is_ajax_request())
			{
				$this->json(array('error' => 'Sesi berakhir. Silakan masuk kembali.'), 401);
				$this->output->_display();
				exit;
			}
			redirect('login');
		}
		if ((int) $this->current_user['must_change_password'] === 1 && ! $this->is_password_route())
		{
			redirect('ganti-sandi');
		}
	}

	private function is_password_route(): bool
	{
		return in_array($this->uri->uri_string(), array('ganti-sandi', 'logout'), TRUE);
	}

	/** @param string|list<string> $roles */
	protected function require_roles($roles): void
	{
		if ( ! in_array($this->current_user['role'], (array) $roles, TRUE))
		{
			$this->forbidden();
		}
	}
}

require_once __DIR__.'/Village_Controller.php';
require_once __DIR__.'/Judge_Controller.php';
require_once __DIR__.'/Admin_Controller.php';
