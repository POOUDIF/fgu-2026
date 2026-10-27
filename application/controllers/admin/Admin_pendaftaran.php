<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use App\Support\ActionResult;

/** /admin/pendaftaran — rekap peserta (admin desa + kiriman publik), diskualifikasi, ekspor. */
class Admin_pendaftaran extends Admin_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model(array('Entry_model', 'Competition_model', 'Village_model'));
	}

	public function index()
	{
		$competitionId = $this->positive_int($this->input->get('lomba'));
		$villageId = $this->positive_int($this->input->get('desa'));

		$this->render('admin/pendaftaran', array(
			'title' => 'Peserta & Pendaftaran',
			'entries' => $this->Entry_model->admin_list($competitionId, $villageId),
			'public_entries' => $this->Entry_model->admin_list($competitionId, $villageId, 'public'),
			'villages' => $this->Village_model->all(),
			'competitions' => $this->Competition_model->active(),
			'participant_matrix' => $this->Entry_model->participant_counts_by_competition_village(),
			'f_lomba' => $competitionId,
			'f_desa' => $villageId,
		) + $this->portal());
	}

	public function export()
	{
		$this->load->library(array('Export_guard', 'Excel_exporter'));
		if (($denied = $this->export_guard->authorize($this->current_user)) !== NULL)
		{
			return $this->json(array('error' => $denied['error']), $denied['status']);
		}
		$lomba = $this->input->get('lomba');
		$desa = $this->input->get('desa');
		if (($lomba !== NULL && $lomba !== '' && $this->positive_int($lomba) === NULL)
			|| ($desa !== NULL && $desa !== '' && $this->positive_int($desa) === NULL))
		{
			return $this->json(array('error' => 'Filter tidak valid.'), 400);
		}

		try
		{
			$book = $this->excel_exporter->participants($this->positive_int($lomba), $this->positive_int($desa));
			$this->excel_exporter->download($book, 'peserta');
		}
		catch (Throwable $e)
		{
			log_message('error', 'Ekspor peserta gagal: '.$e->getMessage());
			$this->json(array('error' => 'Ekspor gagal. Silakan coba lagi.'), 500);
		}
	}

	public function status(int $entryId)
	{
		$status = $this->input->post('status') === 'disqualified' ? 'disqualified' : 'registered';
		$reason = trim((string) $this->input->post('reason'));
		if (mb_strlen($reason) > 500)
		{
			$this->finish(ActionResult::fail('Alasan maksimal 500 karakter.'), $this->back());
		}
		if ($this->Entry_model->find($entryId) === NULL)
		{
			show_404();
		}
		$this->Entry_model->set_status($entryId, $status, $reason === '' ? NULL : $reason);
		$this->finish(ActionResult::ok($status === 'disqualified' ? 'Peserta didiskualifikasi.' : 'Peserta dipulihkan.'), $this->back());
	}

	private function positive_int($v): ?int
	{
		return ($v !== NULL && is_scalar($v) && ctype_digit((string) $v) && (int) $v > 0) ? (int) $v : NULL;
	}

	/** Kembali ke daftar dengan filter yang sama (hanya angka, aman dari open redirect). */
	private function back(): string
	{
		$q = array_filter(array(
			'lomba' => $this->positive_int($this->input->post('f_lomba')),
			'desa' => $this->positive_int($this->input->post('f_desa')),
		));

		return 'admin/pendaftaran'.($q ? '?'.http_build_query($q) : '');
	}
}
