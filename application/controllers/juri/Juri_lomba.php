<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use App\Domain\CerdasCermat;

/** Lomba saya, form penilaian per peserta, dan penilaian tersimpan. */
class Juri_lomba extends Judge_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model(array('Competition_model', 'Entry_model', 'Score_model', 'Penalty_model', 'Cerdas_cermat_model', 'Result_model'));
		$this->load->library('Score_service');
	}

	public function index()
	{
		$competitions = $this->mine();
		$this->render('juri/lomba', array(
			'title' => 'Lomba Saya',
			'competitions' => $competitions,
			'cc_states' => $this->cc_states($competitions),
			'progress' => $this->Score_model->competition_progress(NULL, $this->judge_id()),
			'draftCounts' => $this->Score_model->draft_counts_by_competition($this->judge_id()),
		) + $this->portal());
	}

	public function penilaian()
	{
		$competitions = $this->mine();
		$this->render('juri/penilaian', array(
			'title' => 'Penilaian',
			'competitions' => $competitions,
			'cc_states' => $this->cc_states($competitions),
			'progress' => $this->Score_model->competition_progress(NULL, $this->judge_id()),
			'draftCounts' => $this->Score_model->draft_counts_by_competition($this->judge_id()),
		) + $this->portal());
	}

	/** Daftar peserta satu lomba beserta status penilaian juri saat ini. */
	public function nilai(string $slug)
	{
		$c = $this->assigned_competition($slug);
		$selectedEntryId = $this->input->get('entry_id', TRUE);
		if ($selectedEntryId !== NULL)
		{
			if ( ! ctype_digit((string) $selectedEntryId) || (int) $selectedEntryId < 1)
			{
				show_404();
			}

			redirect('juri/nilai/'.$c['slug'].'/'.(int) $selectedEntryId);
		}

		if ($this->Competition_model->is_cerdas_cermat($c))
		{
			$this->render('juri/cerdas_cermat', array(
				'title' => 'Papan Cerdas Cermat',
				'c' => $c,
				'state' => $this->Cerdas_cermat_model->state((int) $c['id']),
				'rounds' => CerdasCermat::rounds(),
				'results' => $this->Result_model->for_competition($c),
			) + $this->portal());

			return;
		}

		$judgeId = $this->judge_id();
		$entries = $this->Entry_model->registered_for_competition($c['id']);
		$this->render('juri/daftar_entry', array(
			'title' => 'Peserta — '.$c['name'],
			'c' => $c,
			'entries' => $entries,
			'scored' => $this->Score_model->scored_entry_ids($c['id'], $judgeId),
			'drafted' => $this->Score_model->draft_entry_ids($c['id'], $judgeId),
		) + $this->portal());
	}

	/** Form penilaian satu peserta; juri hanya dapat membuka entry terdaftar pada lomba tugasnya. */
	public function entry(string $slug, int $entryId)
	{
		$c = $this->assigned_competition($slug);
		if ($this->Competition_model->is_cerdas_cermat($c))
		{
			redirect('juri/nilai/'.$c['slug']);
		}
		$entry = $this->Entry_model->find($entryId);
		if ($entry === NULL || (int) $entry['competition_id'] !== (int) $c['id'] || $entry['status'] !== 'registered' || $entry['attendance_status'] !== 'present')
		{
			show_404();
		}
		$judgeId = $this->judge_id();
		$given = array();
		$scored = $this->Score_model->scored_entry_ids($c['id'], $judgeId);
		if (isset($scored[$entryId]))
		{
			foreach ($this->Score_model->submissions($judgeId, $c['id']) as $submission)
			{
				if ((int) $submission['entry_id'] === $entryId)
				{
					$submission['is_draft'] = FALSE;
					$given[$entryId] = $submission;
					break;
				}
			}
		}
		else
		{
			$draft = $this->Score_model->draft_for_entry_judge($entryId, $judgeId, $c['scoring_method']);
			if ($draft !== NULL)
			{
				$given[$entryId] = $draft;
			}
		}
		$this->render('juri/nilai', array(
			'title' => 'Penilaian — '.$c['name'],
			'c' => $c,
			'entries' => array($entry),
			'given' => $given,
			'isCerdasCermat' => $this->Competition_model->is_cerdas_cermat($c),
			'roundPenalties' => $this->Penalty_model->rounds_for_entry($entryId),
			'otherPenaltyTotal' => $this->Penalty_model->total_for_entry($entryId),
		) + $this->portal());
	}

	public function photo(string $slug, int $entryId, int $participantId)
	{
		$c = $this->assigned_competition($slug);
		$entry = $this->Entry_model->find($entryId);
		if ($entry === NULL || (int) $entry['competition_id'] !== (int) $c['id'] || $entry['status'] !== 'registered' || $entry['attendance_status'] !== 'present')
		{
			show_404();
		}
		$photoPath = NULL;
		foreach ($entry['members'] as $member)
		{
			if ((int) $member['id'] === $participantId)
			{
				$photoPath = $member['photo_path'];
				break;
			}
		}
		if (empty($photoPath) || ! preg_match('/^[0-9]+\/(?:[0-9]+-[a-f0-9]{16}|upload-[a-f0-9]{32})\.(jpg|jpeg|png|webp)$/i', $photoPath))
		{
			show_404();
		}
		$path = rtrim(APPPATH, '/\\').DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'writable'.DIRECTORY_SEPARATOR.'uploads'.DIRECTORY_SEPARATOR.'participants'.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $photoPath);
		$info = is_file($path) ? @getimagesize($path) : FALSE;
		if ( ! is_array($info) || ! in_array($info['mime'], array('image/jpeg', 'image/png', 'image/webp'), TRUE))
		{
			show_404();
		}
		$content = file_get_contents($path);
		if ($content === FALSE)
		{
			log_message('error', 'Foto penilaian gagal dibaca: '.$participantId);
			show_error('Foto tidak dapat dibaca.', 500);
		}
		$this->output->set_content_type($info['mime'])->set_header('Cache-Control: private, no-store')
			->set_header('X-Content-Type-Options: nosniff')->set_output($content);
	}

	public function save(string $slug, int $entryId)
	{
		$c = $this->assigned_competition($slug);
		$entry = $this->Entry_model->find($entryId);
		if ($entry === NULL || (int) $entry['competition_id'] !== $c['id'] || $entry['status'] !== 'registered' || $entry['attendance_status'] !== 'present')
		{
			show_404();
		}
		$result = $this->score_service->save_draft($this->judge_id(), $entryId, (array) $this->input->post('c'));
		$this->finish($result, 'juri/nilai/'.$c['slug'].'/'.$entryId);
	}

	public function cerdas_points(string $slug, int $roundNumber, int $entryId)
	{
		$this->assigned_competition($slug);
		$change = (string) $this->input->post('change');
		$result = $this->score_service->change_cerdas_cermat_points(
			$this->judge_id(), $entryId, $roundNumber, $change
		);
		$this->finish($result, 'juri/nilai/'.$slug);
	}

	public function cerdas_penalty(string $slug, int $roundNumber, int $entryId)
	{
		$this->assigned_competition($slug);
		$points = Score_service::parse_score($this->input->post('points'));
		$reason = trim((string) $this->input->post('reason'));
		$result = $this->score_service->add_cerdas_cermat_penalty(
			$this->judge_id(), $entryId, $roundNumber, $points, $reason
		);
		$this->finish($result, 'juri/nilai/'.$slug);
	}

	public function advance_cerdas_cermat(string $slug, int $roundNumber)
	{
		$c = $this->assigned_competition($slug);
		$result = $this->score_service->advance_cerdas_cermat_round($this->judge_id(), (int) $c['id'], $roundNumber);
		$this->finish($result, 'juri/nilai/'.$c['slug']);
	}

	public function back_cerdas_cermat(string $slug, int $roundNumber)
	{
		$c = $this->assigned_competition($slug);
		$result = $this->score_service->back_cerdas_cermat_round($this->judge_id(), (int) $c['id'], $roundNumber);
		$this->finish($result, 'juri/nilai/'.$c['slug']);
	}

	public function submit(string $slug)
	{
		$c = $this->assigned_competition($slug);
		$result = $this->score_service->submit_competition($this->judge_id(), (int) $c['id']);
		$this->finish($result, 'juri/nilai/'.$c['slug']);
	}

	public function riwayat()
	{
		$this->render('juri/riwayat', array(
			'title' => 'Penilaian Tersimpan',
			'rows' => $this->Score_model->submissions($this->judge_id()),
		) + $this->portal());
	}

	private function judge_id(): int
	{
		return (int) $this->current_user['id'];
	}

	/** @return list<array<string,mixed>> */
	private function mine(): array
	{
		return $this->Competition_model->assigned_to_judge($this->judge_id());
	}

	/** @param list<array<string,mixed>> $competitions @return array<int,array<string,mixed>> */
	private function cc_states(array $competitions): array
	{
		$states = array();
		foreach ($competitions as $competition)
		{
			if ($this->Competition_model->is_cerdas_cermat($competition))
			{
				$states[(int) $competition['id']] = $this->Cerdas_cermat_model->state((int) $competition['id']);
			}
		}

		return $states;
	}

	/** Lomba aktif yang ditugaskan ke juri ini; selain itu 404 (tidak membocorkan keberadaan). */
	private function assigned_competition(string $slug): array
	{
		$c = $this->Competition_model->find_by_slug($slug);
		if ($c === NULL || ! $this->Competition_model->is_judge_assigned($c['id'], $this->judge_id()))
		{
			show_404();
		}

		return $c;
	}
}
