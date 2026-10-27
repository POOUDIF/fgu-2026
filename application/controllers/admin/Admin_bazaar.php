<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use App\Support\ActionResult;

/** Rekap pendaftar Bazaar UMKM dan Bazaar Desa untuk Admin Daerah. */
class Admin_bazaar extends Admin_Controller
{
	public function index()
	{
		$this->load->model(array('Bazaar_model', 'Event_model'));
		$this->render('admin/bazaar', array(
			'title' => 'Bazaar',
			'bazaar' => $this->Bazaar_model->all(),
			'village_competition' => $this->Bazaar_model->village_competition(),
			'umkm_registration_open' => (bool) ($this->Event_model->current()['umkm_registration_open'] ?? FALSE),
			'is_super' => $this->is_super(),
		) + $this->portal());
	}

	public function umkm_registration()
	{
		$this->require_super();
		$this->load->model('Bazaar_model');
		$openValue = $this->input->post('open');
		if ( ! in_array($openValue, array('0', '1'), TRUE))
		{
			$this->finish(ActionResult::fail('Status pendaftaran Bazaar UMKM tidak valid.'), 'admin/bazaar');
		}

		$open = $openValue === '1';
		if ( ! $this->Bazaar_model->set_umkm_registration_open($open))
		{
			$this->finish(ActionResult::fail('Status pendaftaran Bazaar UMKM gagal disimpan.'), 'admin/bazaar');
		}
		$this->finish(ActionResult::ok($open ? 'Pendaftaran Bazaar UMKM dibuka.' : 'Pendaftaran Bazaar UMKM ditutup.'), 'admin/bazaar');
	}
}
