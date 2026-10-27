<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Ringkasan, review, dan input/koreksi nilai pada lomba yang ditugaskan. */
class Manager_dashboard extends Competition_Manager_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model(array('Score_model', 'Entry_model'));
		$this->load->library('Score_service');
	}

	public function index()
	{
		$competitions = $this->Competition_model->assigned_to_manager($this->manager_id());
		$progress = $this->Score_model->competition_progress();
		$this->render('manager/dashboard', array(
			'title' => 'Ringkasan',
			'competitions' => $competitions,
			'cerdas_cermat_ids' => array_map(
				'intval',
				array_column(array_filter($competitions, function ($competition) {
					return $this->Competition_model->is_cerdas_cermat($competition);
				}), 'id')
			),
			'progress' => $progress,
		) + $this->portal());
	}

	public function competition(string $slug)
	{
		$competition = $this->assigned_competition($slug);
		$scoreMap = array();
		foreach ($this->Score_model->submissions(NULL, (int) $competition['id']) as $submission)
		{
			$scoreMap[(int) $submission['entry_id']][(int) $submission['judge_id']] = $submission;
		}
		$this->render('manager/competition', array(
			'title' => 'Review — '.$competition['name'],
			'competition' => $competition,
			'is_cerdas_cermat' => $this->Competition_model->is_cerdas_cermat($competition),
			'entries' => $this->Entry_model->registered_for_competition((int) $competition['id']),
			'score_map' => $scoreMap,
		) + $this->portal());
	}

	public function save(string $slug, int $entryId, int $judgeId)
	{
		$competition = $this->assigned_competition($slug);
		$result = $this->score_service->manage_set(
			$this->manager_id(),
			(string) $this->current_user['full_name'],
			(int) $competition['id'],
			$entryId,
			$judgeId,
			(array) $this->input->post('score'),
			(string) $this->input->post('reason')
		);
		$this->finish($result, 'admin-lomba/nilai/'.$competition['slug']);
	}
}
