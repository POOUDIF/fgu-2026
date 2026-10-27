<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** /juri/kontrol-lomba dan /juri/kontrol-nilai — hanya lomba yang ditugaskan. */
class Juri_kontrol extends Judge_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model(array('Competition_model', 'Entry_model', 'Score_model', 'Cerdas_cermat_model'));
	}

	public function lomba()
	{
		$judgeId = (int) $this->current_user['id'];
		$blocks = array();
		foreach ($this->Competition_model->assigned_to_judge($judgeId) as $c)
		{
			$blocks[] = array(
				'competition' => $c,
				'entries' => $this->Entry_model->registered_for_competition($c['id']),
				'cc_state' => $this->Competition_model->is_cerdas_cermat($c) ? $this->Cerdas_cermat_model->state((int) $c['id']) : NULL,
				'scored' => $this->Score_model->scored_entry_ids($c['id'], $judgeId),
				'drafted' => $this->Score_model->draft_entry_ids($c['id'], $judgeId),
			);
		}
		$this->render('juri/kontrol_lomba', array('title' => 'Kontrol Lomba', 'blocks' => $blocks) + $this->portal());
	}

	public function nilai()
	{
		$judgeId = (int) $this->current_user['id'];
		$progress = $this->Score_model->competition_progress(NULL, $judgeId);
		$ccStates = array();
		foreach ($this->Competition_model->assigned_to_judge($judgeId) as $competition)
		{
			if ($this->Competition_model->is_cerdas_cermat($competition))
			{
				$ccStates[(int) $competition['id']] = $this->Cerdas_cermat_model->state((int) $competition['id']);
			}
		}
		$this->render('juri/kontrol_nilai', array(
			'title' => 'Kontrol Penilaian',
			'competitions' => $this->Competition_model->assigned_to_judge($judgeId),
			'progress' => $progress,
			'cc_states' => $ccStates,
			'draftCounts' => $this->Score_model->draft_counts_by_competition($judgeId),
			'my_total' => $this->Score_model->count_for_judge($judgeId),
		) + $this->portal());
	}
}
