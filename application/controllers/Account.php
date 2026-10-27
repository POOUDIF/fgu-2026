<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Ganti sandi akun sendiri (juga halaman paksa untuk sandi sementara). */
class Account extends Auth_Controller
{
	public function password()
	{
		$this->render('public/change_password', array(
			'title' => 'Ganti Kata Sandi',
			'forced' => (int) $this->current_user['must_change_password'] === 1,
		), 'layouts/auth');
	}

	public function update_password()
	{
		$result = $this->auth_service->change_own_password(
			(int) $this->current_user['id'],
			(string) $this->input->post('current_password'),
			(string) $this->input->post('password'),
			(string) $this->input->post('password_confirm')
		);

		if ($result->ok)
		{
			$this->flash('ok', $result->message);
			redirect(\App\Domain\Role::homeFor($this->current_user['role']));
		}
		$this->flash('err', $result->message);
		redirect('ganti-sandi');
	}
}
