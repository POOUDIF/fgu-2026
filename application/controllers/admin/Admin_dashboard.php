<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** /admin — ringkasan dan kontrol cepat. */
class Admin_dashboard extends Admin_Controller
{
	public function index()
	{
		$this->load->model(array('Competition_model', 'Village_model', 'Entry_model', 'Participant_model', 'Score_model', 'Result_model', 'Cerdas_cermat_model'));

		$competitions = $this->Competition_model->active();
		$ccCompetition = NULL;
		foreach ($competitions as $competition)
		{
			if ($this->Competition_model->is_cerdas_cermat($competition))
			{
				$ccCompetition = $competition;
				break;
			}
		}
		$villages = $this->Village_model->all();
		$registeredEntries = $this->Entry_model->count_registered();
		$progress = $this->Score_model->admin_progress_rows();
		if ($ccCompetition !== NULL)
		{
			$progress = array_values(array_filter(
				$progress,
				static fn ($row) => (int) $row['competition_id'] !== (int) $ccCompetition['id']
			));
		}
		$expectedScores = array_sum(array_map(static fn ($row) => (int) $row['total_entries'], $progress));
		$completedScores = array_sum(array_map(static fn ($row) => (int) $row['scored_entries'], $progress));

		$recentScores = $this->Score_model->recent_submissions();
		$this->render('admin/dashboard', array(
			'title' => 'Panel Panitia',
			'participant_count' => $this->Participant_model->count_all(),
			'entry_count' => $registeredEntries,
			'village_count' => $this->Entry_model->count_registered_villages(),
			'submission_count' => $this->Score_model->count_submissions(),
			'score_percent' => $expectedScores > 0 ? (int) round(100 * $completedScores / $expectedScores) : 0,
			'score_done' => $completedScores,
			'score_expected' => $expectedScores,
			'progress_rows' => $progress,
			'recent_scores' => $recentScores,
			'recent_score_count' => $this->Score_model->count_submissions(),
			'cc_competition' => $ccCompetition,
			'cc_state' => $ccCompetition ? $this->Cerdas_cermat_model->state((int) $ccCompetition['id']) : NULL,
			'cc_results' => $ccCompetition ? $this->Result_model->for_competition($ccCompetition) : array(),
		) + $this->portal());
	}

	public function feed()
	{
		$this->load->model('Score_model');
		$page = filter_var($this->input->get('page'), FILTER_VALIDATE_INT, array('options' => array('min_range' => 1))) ?: 1;
		$limit = 10;
		$items = array_map(static function ($row) {
			return array(
				'time' => fmt_datetime($row['submitted_at']),
				'competition' => $row['competition_name'],
				'entry' => $row['entry_label'],
				'village' => $row['village_name'],
				'judge' => $row['judge_name'],
				'total' => fmt_score($row['total']),
				'criteria_count' => (int) $row['criteria_count'],
			);
		}, $this->Score_model->recent_submissions($limit, ($page - 1) * $limit));

		$this->json(array('items' => $items, 'total' => $this->Score_model->count_submissions(), 'page' => $page, 'page_size' => $limit));
	}
}
