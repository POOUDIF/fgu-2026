<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use App\Domain\RegistrationWindow;

/** /desa — ringkasan desa. */
class Desa_dashboard extends Village_Controller
{
	public function index()
	{
		if ( ! $this->require_village())
		{
			return;
		}
		$this->load->model(array('Competition_model', 'Entry_model', 'Score_model', 'Participant_model', 'Event_model'));

		$allCompetitions = $this->Competition_model->active();
		$competitions = array_values(array_filter(
			$allCompetitions,
			static fn ($competition) => $competition['slug'] !== 'bazaar-desa'
		));
		$summary = $this->Entry_model->village_summary($this->village_id);
		foreach ($allCompetitions as $competition)
		{
			if ($competition['slug'] === 'bazaar-desa')
			{
				unset($summary[(int) $competition['id']]);
			}
		}
		$progress = $this->Score_model->competition_progress($this->village_id);
		$event = $this->Event_model->current();
		$registered = count(array_filter($competitions, static fn ($c) => isset($summary[$c['id']])));
		$quota_filled = count(array_filter($competitions, static fn ($c) => $c['quota'] !== NULL
			&& ($summary[$c['id']]['participants'] ?? 0) >= (int) $c['quota']));

		$this->render('desa/dashboard', array(
			'title' => 'Ringkasan',
			'village' => $this->village,
			'competitions' => $competitions,
			'summary' => $summary,
			'participant_count' => $this->Participant_model->count_by_village($this->village_id),
			'registration_count' => array_sum(array_map(static fn ($row) => $row['entries'], $summary)),
			'pending' => count($competitions) - $registered,
			'quota_filled' => $quota_filled,
			'score_complete' => count(array_filter($progress, static fn ($p) => $p['complete'])),
			'registration_status' => RegistrationWindow::status($event['registration_opens_at'] ?? NULL, $event['registration_closes_at'] ?? NULL),
		) + $this->portal());
	}
}
