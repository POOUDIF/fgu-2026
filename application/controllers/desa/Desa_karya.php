<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** /desa/karya — tautan karya untuk lomba online. */
class Desa_karya extends Village_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model('Entry_model');
		$this->load->library('Registration_service');
	}

	public function index()
	{
		if ( ! $this->require_village())
		{
			return;
		}
		$entries = array_values(array_filter(
			$this->Entry_model->for_village($this->village_id),
			static fn ($e) => $e['submission_mode'] === 'online'
		));
		$this->render('desa/karya', array(
			'title' => 'Pengumpulan Karya Online',
			'village' => $this->village,
			'entries' => $entries,
		) + $this->portal());
	}

	public function update(int $entryId)
	{
		if ( ! $this->require_village())
		{
			return;
		}
		$this->finish($this->registration_service->update_link($this->village_id, $entryId, $this->input->post('submission_url')), 'desa/karya');
	}
}
