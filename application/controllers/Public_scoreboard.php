<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use App\Domain\CerdasCermat;

/** Public read-only live board for the Cerdas Cermat competition. */
class Public_scoreboard extends MY_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model(array('Competition_model', 'Cerdas_cermat_model', 'Result_model'));
	}

	public function show(string $slug)
	{
		$competition = $this->competition($slug);
		$this->output->set_header('Cache-Control: no-store');
		$this->render('public/cerdas_cermat_scoreboard', array(
			'title' => 'Papan Nilai Cerdas Cermat',
			'competition' => $competition,
			'board' => $this->board($competition),
			'feed_url' => site_url('siaran/cerdas-cermat/'.$competition['slug'].'/feed'),
		), 'layouts/scoreboard');
	}

	public function feed(string $slug)
	{
		$competition = $this->competition($slug);
		$this->json($this->board($competition));
	}

	private function competition(string $slug): array
	{
		$competition = $this->Competition_model->find_by_slug($slug);
		if ($competition === NULL || !$this->Competition_model->is_cerdas_cermat($competition))
		{
			show_404();
		}

		return $competition;
	}

	/** @return array{current_round:int,is_finished:bool,updated_at:?string,round_labels:array<int,string>,results:list<array<string,mixed>>} */
	private function board(array $competition): array
	{
		$state = $this->Cerdas_cermat_model->state((int) $competition['id']);
		$results = array();
		foreach ($this->Result_model->for_competition($competition) as $result)
		{
			if ($result['rank'] === NULL)
			{
				continue;
			}
			$results[] = array(
				'rank' => (int) $result['rank'],
				'village_name' => (string) $result['village_name'],
				'round_scores' => array(
					1 => (float) ($result['round_scores'][1] ?? 0),
					2 => (float) ($result['round_scores'][2] ?? 0),
					3 => (float) ($result['round_scores'][3] ?? 0),
					4 => (float) ($result['round_scores'][4] ?? 0),
				),
				'final_score' => (float) $result['final_score'],
			);
		}

		$rounds = CerdasCermat::rounds();

		return array(
			'current_round' => (int) $state['current_round'],
			'is_finished' => (bool) $state['is_finished'],
			'updated_at' => $state['updated_at'] ?? NULL,
			'round_labels' => array_values(array_map(static fn ($round) => $round['label'], $rounds)),
			'results' => $results,
		);
	}
}
