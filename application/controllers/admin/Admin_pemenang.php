<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use App\Support\ActionResult;

/** /admin/pemenang — peringkat per lomba (akumulasi seluruh juri); penalti hanya Super Admin. */
class Admin_pemenang extends Admin_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model(array('Result_model', 'Penalty_model', 'Entry_model'));
		$this->load->library('Score_service');
	}

	public function index()
	{
		$this->render('admin/pemenang', array(
			'title' => 'Daftar Pemenang',
			'blocks' => $this->Result_model->all_competitions(),
			'is_super' => $this->is_super(),
		) + $this->portal());
	}

	public function penalty_add()
	{
		$this->require_super();
		$entryId = (int) $this->input->post('entry_id');
		$points = Score_service::parse_score($this->input->post('points'));
		$reason = trim((string) $this->input->post('reason'));

		if ($this->Entry_model->find($entryId) === NULL || $points === NULL || $points <= 0 || mb_strlen($reason) > 500)
		{
			$this->finish(ActionResult::fail('Poin penalti harus angka lebih dari 0 (koma desimal diterima).'), 'admin/pemenang');
		}
		$this->Penalty_model->add($entryId, $points, $reason === '' ? NULL : $reason, (int) $this->current_user['id']);
		$this->finish(ActionResult::ok('Penalti ditambahkan.'), 'admin/pemenang');
	}

	public function penalty_delete(int $penaltyId)
	{
		$this->require_super();
		$this->Penalty_model->delete($penaltyId);
		$this->finish(ActionResult::ok('Penalti dihapus.'), 'admin/pemenang');
	}

	public function publish(int $competitionId)
	{
		$value = (string) $this->input->post('published');
		if ( ! in_array($value, array('0', '1'), TRUE))
		{
			$this->finish(ActionResult::fail('Status publikasi tidak valid.'), 'admin/pemenang');
		}
		$published = $value === '1';
		$this->load->model('Competition_model');
		$competition = $this->Competition_model->find($competitionId);
		if ($competition === NULL)
		{
			$this->finish(ActionResult::fail('Lomba tidak ditemukan.'), 'admin/pemenang');
		}

		$ok = $this->Competition_model->set_results_published($competitionId, $published);
		$this->finish(
			$ok ? ActionResult::ok($published ? 'Hasil lomba dipublikasikan.' : 'Publikasi hasil dibatalkan.') : ActionResult::fail('Status publikasi gagal disimpan.'),
			'admin/pemenang'
		);
	}
}
