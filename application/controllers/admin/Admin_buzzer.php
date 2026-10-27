<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** /admin/buzzer — tombol rebutan Cerdas Cermat (6 peserta). */
class Admin_buzzer extends Admin_Controller
{
	private const BUTTON_COUNT = 6;

	public function index()
	{
		$this->load->model(array('Competition_model', 'Result_model'));

		$names = array();
		foreach ($this->Competition_model->active() as $competition)
		{
			if ( ! $this->Competition_model->is_cerdas_cermat($competition))
			{
				continue;
			}
			foreach ($this->Result_model->for_competition($competition) as $result)
			{
				if ($result['rank'] !== NULL)
				{
					$names[] = (string) $result['village_name'];
				}
			}
			break;
		}

		$teams = array();
		for ($i = 0; $i < self::BUTTON_COUNT; $i++)
		{
			$teams[] = $names[$i] ?? 'Peserta '.($i + 1);
		}

		$this->render('admin/buzzer', array(
			'title' => 'Buzzer Cerdas Cermat',
			'teams' => $teams,
			'sound_url' => asset('audio/buzzer.mp3'),
			'extra_css' => array('css/buzzer.css'),
			'extra_js' => array('js/buzzer.js'),
		) + $this->portal());
	}
}
