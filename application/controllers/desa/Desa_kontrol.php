<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** /desa/kontrol-lomba dan /desa/kontrol-nilai — hanya data desa sendiri. */
class Desa_kontrol extends Village_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model(array('Competition_model', 'Entry_model', 'Score_model'));
	}

	public function lomba()
	{
		if ( ! $this->require_village())
		{
			return;
		}
		$this->render('desa/kontrol_lomba', array(
			'title' => 'Kontrol Lomba',
			'village' => $this->village,
			'competitions' => $this->Competition_model->active(),
			'summary' => $this->Entry_model->village_summary($this->village_id),
		) + $this->portal());
	}

	public function nilai()
	{
		if ( ! $this->require_village())
		{
			return;
		}
		$this->render('desa/kontrol_nilai', array(
			'title' => 'Kontrol Penilaian',
			'village' => $this->village,
			'competitions' => $this->Competition_model->active(),
			'summary' => $this->Entry_model->village_summary($this->village_id),
			'progress' => $this->Score_model->competition_progress($this->village_id),
		) + $this->portal());
	}
}
