<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** /juri — ringkasan penugasan juri. */
class Juri_dashboard extends Judge_Controller
{
	public function index()
	{
		$this->load->model(array('Competition_model', 'Score_model', 'Cerdas_cermat_model'));
		$judgeId = (int) $this->current_user['id'];
		$mine = $this->Competition_model->assigned_to_judge($judgeId);
		$progress = $this->Score_model->competition_progress(NULL, $judgeId);
		$draftCounts = $this->Score_model->draft_counts_by_competition($judgeId);
		$ccStates = array();
		$pending = 0;
		foreach ($mine as $competition)
		{
			if ($this->Competition_model->is_cerdas_cermat($competition))
			{
				$ccStates[(int) $competition['id']] = $this->Cerdas_cermat_model->state((int) $competition['id']);
				continue;
			}
			$p = $progress[$competition['id']] ?? array('entries' => 0, 'actual' => 0);
			$pending += max(0, (int) $p['entries'] - (int) $p['actual'] - ($draftCounts[$competition['id']] ?? 0));
		}

		$this->render('juri/dashboard', array(
			'title' => 'Ringkasan',
			'competitions' => $mine,
			'progress' => $progress,
			'draftCounts' => $draftCounts,
			'cc_states' => $ccStates,
			'to_score' => $pending,
			'scored' => array_sum(array_map(static fn ($c) => $progress[$c['id']]['actual'] ?? 0, $mine)),
		) + $this->portal());
	}
}
