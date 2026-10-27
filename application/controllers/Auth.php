<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use App\Domain\Role;

/** Login & logout. Tidak ada registrasi mandiri. */
class Auth extends MY_Controller
{
	public function login()
	{
		if ($this->current_user !== NULL)
		{
			redirect(Role::homeFor($this->current_user['role']));
		}
		$this->render('public/login', array('title' => 'Masuk'), 'layouts/auth');
	}

	public function authenticate()
	{
		$email = trim((string) $this->input->post('email'));
		$password = (string) $this->input->post('password');

		if ($email === '' || $password === '')
		{
			$this->flash('err', 'Email dan kata sandi wajib diisi.');
			$this->keep_old_input();
			redirect('login');
		}

		$result = $this->auth_service->login($email, $password);
		if ( ! $result->ok)
		{
			$this->flash('err', $result->message);
			$this->keep_old_input();
			redirect('login');
		}
		redirect('dashboard');
	}

	public function logout()
	{
		$this->auth_service->logout();
		redirect('/');
	}
}
