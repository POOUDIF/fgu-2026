<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** /admin/kontrol-lomba dan /admin/kontrol-nilai. */
class Admin_kontrol extends Admin_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model(array('Competition_model', 'Village_model', 'Entry_model', 'Score_model'));
	}

	public function lomba()
	{
		$this->render('admin/kontrol_lomba', array(
			'title' => 'Kontrol Lomba',
			'competitions' => $this->Competition_model->active(),
			'villages' => $this->Village_model->all(),
			'counts' => $this->Entry_model->counts_by_competition_village(),
		) + $this->portal());
	}

	public function nilai()
	{
		$this->render('admin/kontrol_nilai', array(
			'title' => 'Kontrol Penilaian',
			'competitions' => $this->Competition_model->active(),
			'progress' => $this->Score_model->competition_progress(),
			'total_scores' => $this->Score_model->count_submissions(),
		) + $this->portal());
	}
}
