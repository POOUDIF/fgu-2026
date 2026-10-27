<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** /admin/hasil — rekap penilaian tersimpan; koreksi & reset hanya Super Admin; ekspor Excel. */
class Admin_hasil extends Admin_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model(array('Score_model', 'Audit_model'));
	}

	public function index()
	{
		$this->load->model('Result_model');
		$this->render('admin/hasil', array(
			'title' => 'Hasil & Publikasi',
			'rows' => $this->Score_model->submissions(),
			'audit' => $this->Audit_model->recent(NULL, 50),
			'result_blocks' => $this->Result_model->all_competitions(),
			'is_super' => $this->is_super(),
		) + $this->portal());
	}

	public function detail(string $slug)
	{
		$this->load->model(array('Competition_model', 'Result_model'));
		$competition = $this->Competition_model->find_by_slug($slug);
		if ($competition === NULL)
		{
			show_404();
		}
		$submissions = $this->Score_model->submissions(NULL, (int) $competition['id']);
		$scoreMap = array();
		foreach ($submissions as $submission)
		{
			$scoreMap[$submission['entry_id']][$submission['judge_id']] = $submission;
		}
		$judgeNames = array();
		foreach ($this->Competition_model->judges_by_competition()[$competition['id']] ?? array() as $judge)
		{
			$judgeNames[$judge['id']] = $judge['full_name'];
		}
		$this->render('admin/hasil_detail', array(
			'title' => 'Rincian Nilai',
			'competition' => $competition,
			'results' => $this->Result_model->for_competition($competition),
			'judge_names' => $judgeNames,
			'score_map' => $scoreMap,
			'is_super' => $this->is_super(),
		) + $this->portal());
	}

	public function export()
	{
		$this->load->library(array('Export_guard', 'Excel_exporter'));
		if (($denied = $this->export_guard->authorize($this->current_user)) !== NULL)
		{
			return $this->json(array('error' => $denied['error']), $denied['status']);
		}
		try
		{
			$this->excel_exporter->download($this->excel_exporter->results(), 'hasil');
		}
		catch (Throwable $e)
		{
			log_message('error', 'Ekspor hasil gagal: '.$e->getMessage());
			$this->json(array('error' => 'Ekspor gagal. Silakan coba lagi.'), 500);
		}
	}

	public function edit_scores()
	{
		$this->require_super();
		$this->load->library('Score_service');
		$result = $this->score_service->edit_set(
			(int) $this->current_user['id'], $this->current_user['full_name'],
			(int) $this->input->post('entry_id'), (int) $this->input->post('judge_id'),
			(array) $this->input->post('score'), (string) $this->input->post('reason')
		);
		$this->finish($result, 'admin/hasil');
	}

	public function reset_judge()
	{
		$this->require_super();
		$this->load->library('Score_service');
		$result = $this->score_service->reset_judge_entry(
			(int) $this->current_user['id'], $this->current_user['full_name'],
			(int) $this->input->post('entry_id'), (int) $this->input->post('judge_id'), (string) $this->input->post('reason')
		);
		$this->finish($result, 'admin/hasil');
	}
}
