<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use App\Domain\Role;
use App\Support\ActionResult;

/** /admin/akun — kelola akun admin daerah, admin desa, juri, dan Admin Lomba. */
class Admin_akun extends Admin_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->require_super();
		$this->load->model(array('User_model', 'Village_model', 'Competition_model'));
		$this->load->library('Account_service');
	}

	public function index()
	{
		$assigned = array();
		$managed = array();
		foreach ($this->User_model->judges() as $j)
		{
			$assigned[(int) $j['id']] = $this->Competition_model->assigned_ids((int) $j['id']);
		}
		foreach ($this->User_model->all() as $account)
		{
			if ($account['role'] === Role::COMPETITION_ADMIN)
			{
				$managed[(int) $account['id']] = $this->Competition_model->manager_assigned_ids((int) $account['id']);
			}
		}

		$this->render('admin/akun', array(
			'title' => 'Akun & Juri',
			'accounts' => $this->User_model->all(),
			'villages' => $this->Village_model->all(),
			'competitions' => $this->Competition_model->active(),
			'assigned' => $assigned,
			'managed' => $managed,
			'assignable_roles' => Role::assignableBy($this->current_user['role']),
			'is_super' => $this->is_super(),
		) + $this->portal());
	}

	public function create()
	{
		$result = $this->account_service->create($this->current_user, array(
			'full_name' => $this->input->post('full_name'),
			'email' => $this->input->post('email'),
			'password' => $this->input->post('password'),
			'role' => $this->input->post('role'),
			'village_id' => $this->input->post('village_id'),
			'competition' => ($this->is_super() || $this->input->post('role') === Role::COMPETITION_ADMIN)
				? (array) $this->input->post('competition')
				: array(),
		));
		$this->finish($result, 'admin/akun');
	}

	public function update(int $id)
	{
		$this->finish($this->account_service->update_profile($this->current_user, $id, array(
			'full_name' => $this->input->post('full_name'),
			'role' => $this->input->post('role'),
			'village_id' => $this->input->post('village_id'),
		)), 'admin/akun');
	}

	public function assign(int $judgeId)
	{
		$account = $this->User_model->find($judgeId);
		if ($account === NULL || ! in_array($account['role'], array(Role::JUDGE, Role::COMPETITION_ADMIN), TRUE))
		{
			$this->finish(ActionResult::fail('Akun penilai atau Admin Lomba tidak ditemukan.'), 'admin/akun');
		}
		if ($account['role'] === Role::JUDGE)
		{
			$this->require_super();
		}
		$ok = $account['role'] === Role::JUDGE
			? $this->Competition_model->sync_judge_assignments($judgeId, (array) $this->input->post('competition'))
			: $this->Competition_model->sync_manager_assignments($judgeId, (array) $this->input->post('competition'));
		$this->finish($ok ? ActionResult::ok('Penugasan lomba disimpan.') : ActionResult::fail('Penugasan lomba gagal disimpan.'), 'admin/akun');
	}

	public function reset_password(int $id)
	{
		$this->finish($this->account_service->reset_password($this->current_user, $id, (string) $this->input->post('password')), 'admin/akun');
	}

	public function delete(int $id)
	{
		$this->finish($this->account_service->delete($this->current_user, $id), 'admin/akun');
	}
}
