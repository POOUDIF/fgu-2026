<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use App\Domain\Role;

/** Pengarah ke beranda sesuai peran. */
class Dashboard extends MY_Controller
{
	public function index()
	{
		if ($this->current_user === NULL)
		{
			redirect('login');
		}
		$this->session->keep_flashdata(array('flash')); // jangan hilang di lompatan redirect ini
		redirect(Role::homeFor($this->current_user['role']));
	}
}
