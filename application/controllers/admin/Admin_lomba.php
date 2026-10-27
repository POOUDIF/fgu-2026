<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use App\Support\ActionResult;

/** /admin/lomba — CRUD lomba beserta kriteria, ketentuan, dan penugasan juri. */
class Admin_lomba extends Admin_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model(array('Competition_model', 'User_model'));
		$this->load->library('Competition_service');
	}

	public function index()
	{
		$this->render('admin/lomba', array(
			'title' => 'Daftar Lomba',
			'competitions' => $this->Competition_model->active_with_details(),
			'judges' => $this->User_model->judges(),
			'icons' => Competition_service::available_icons(),
			'is_super' => $this->is_super(),
		) + $this->portal());
	}

	public function save()
	{
		$id = (int) $this->input->post('id');
		$result = $this->competition_service->save($id > 0 ? $id : NULL, array(
			'name' => $this->input->post('name'),
			'cluster' => $this->input->post('cluster'),
			'age_label' => $this->input->post('age_label'),
			'type_label' => $this->input->post('type_label'),
			'participation_type' => $this->input->post('participation_type'),
			'quota' => $this->input->post('quota'),
			'minimum_participants' => $this->input->post('minimum_participants'),
			'submission_mode' => $this->input->post('submission_mode'),
			'place' => $this->input->post('place'),
			'icon' => $this->input->post('icon'),
			'winner_count' => $this->input->post('winner_count'),
			'rules' => $this->input->post('rules'),
			'gender_option' => $this->input->post('gender_option'),
			'criteria' => (array) $this->input->post('criteria'),
			'judges' => $this->is_super()
				? (array) $this->input->post('judges')
				: ($id > 0 ? $this->Competition_model->judge_ids($id) : array()),
		));
		$this->finish($result, 'admin/lomba');
	}

	public function registration(int $id)
	{
		$this->require_super();
		$openValue = $this->input->post('open');
		if ( ! in_array($openValue, array('0', '1'), TRUE))
		{
			$this->finish(ActionResult::fail('Status pendaftaran tidak valid.'), 'admin/lomba');
		}
		$open = $openValue === '1';
		$this->finish($this->competition_service->set_registration_open($id, $open), 'admin/lomba');
	}

	public function delete(int $id)
	{
		$this->finish($this->competition_service->delete($id), 'admin/lomba');
	}
}
