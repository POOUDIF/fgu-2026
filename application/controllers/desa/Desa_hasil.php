<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** /desa/hasil — nilai peserta desa sendiri. */
class Desa_hasil extends Village_Controller
{
	public function index()
	{
		if ( ! $this->require_village())
		{
			return;
		}
		$this->load->model('Result_model');
		$this->render('desa/hasil', array(
			'title' => 'Hasil Penilaian',
			'village' => $this->village,
			'blocks' => $this->Result_model->for_village($this->village_id),
		) + $this->portal());
	}
}
