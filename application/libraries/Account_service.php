<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use App\Domain\Role;
use App\Support\ActionResult;
use App\Support\DbError;
use App\Support\Password;

/**
 * Manajemen akun oleh panitia. Aturan peran (siapa boleh mengelola siapa) ada di App\Domain\Role.
 * Tidak ada registrasi mandiri: semua akun dibuat lewat service ini atau CLI.
 */
class Account_service
{
	private $CI;

	public function __construct()
	{
		$this->CI =& get_instance();
		$this->CI->load->model(array('User_model', 'Village_model', 'Competition_model'));
	}

	/**
	 * @param array{full_name?:mixed,email?:mixed,password?:mixed,role?:mixed,village_id?:mixed,competition?:mixed} $in
	 */
	public function create(array $actor, array $in): ActionResult
	{
		$name = trim((string) ($in['full_name'] ?? ''));
		$email = $this->CI->User_model->normalize_email((string) ($in['email'] ?? ''));
		$role = (string) ($in['role'] ?? '');
		$password = (string) ($in['password'] ?? '');
		if ($password === '')
		{
			$password = Password::generate();
		}

		if ($name === '' || mb_strlen($name) > 100)
		{
			return ActionResult::fail('Nama lengkap wajib diisi (maksimal 100 karakter).');
		}
		if ( ! filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 190)
		{
			return ActionResult::fail('Format email tidak valid.');
		}
		if ( ! in_array($role, Role::assignableBy($actor['role']), TRUE))
		{
			return ActionResult::fail('Anda tidak berwenang membuat akun dengan peran tersebut.');
		}
		if (($error = Password::validate($password)) !== NULL)
		{
			return ActionResult::fail($error);
		}

		$villageId = NULL;
		if ($role === Role::VILLAGE_ADMIN)
		{
			$villageId = (int) ($in['village_id'] ?? 0);
			if ($this->CI->Village_model->find($villageId) === NULL)
			{
				return ActionResult::fail('Admin Desa wajib dikaitkan dengan satu desa.');
			}
		}
		if ($this->CI->User_model->email_exists($email))
		{
			return ActionResult::fail('Email sudah dipakai akun lain.');
		}

		$db = $this->CI->db;
		$db->trans_begin();
		$id = $this->CI->User_model->create(array(
			'full_name' => $name,
			'email' => $email,
			'password_hash' => Password::hash($password),
			'role' => $role,
			'village_id' => $villageId,
			'must_change_password' => 1,
		));
		if ($id === NULL)
		{
			$error = $db->error();
			$db->trans_rollback();

			return ActionResult::fail(DbError::friendly($error));
		}
		if ($role === Role::JUDGE && ! $this->CI->Competition_model->sync_judge_assignments($id, (array) ($in['competition'] ?? array())))
		{
			$error = $db->error();
			$db->trans_rollback();

			return ActionResult::fail(DbError::friendly($error));
		}
		if ($role === Role::COMPETITION_ADMIN && ! $this->CI->Competition_model->sync_manager_assignments($id, (array) ($in['competition'] ?? array())))
		{
			$error = $db->error();
			$db->trans_rollback();

			return ActionResult::fail(DbError::friendly($error));
		}
		$db->trans_commit();

		return ActionResult::ok(
			sprintf('Akun dibuat. Email: %s · Sandi awal: %s (tampil sekali; pengguna wajib menggantinya saat login pertama).', $email, $password),
			array('id' => $id)
		);
	}

	public function update_profile(array $actor, int $targetId, array $in): ActionResult
	{
		$target = $this->CI->User_model->find($targetId);
		if ($target === NULL)
		{
			return ActionResult::fail('Akun tidak ditemukan.');
		}
		if ((int) $actor['id'] === $targetId && (string) ($in['role'] ?? $target['role']) !== $target['role'])
		{
			return ActionResult::fail('Anda tidak dapat mengubah peran akun sendiri.');
		}
		if ( ! Role::canManage($actor['role'], $target['role']))
		{
			return ActionResult::fail('Anda tidak berwenang mengubah akun ini.');
		}

		$name = trim((string) ($in['full_name'] ?? ''));
		$role = (string) ($in['role'] ?? $target['role']);
		if ($name === '' || mb_strlen($name) > 100)
		{
			return ActionResult::fail('Nama lengkap wajib diisi (maksimal 100 karakter).');
		}
		if ($role !== $target['role'] && ! in_array($role, Role::assignableBy($actor['role']), TRUE))
		{
			return ActionResult::fail('Anda tidak berwenang memberi peran tersebut.');
		}

		$villageId = NULL;
		if ($role === Role::VILLAGE_ADMIN)
		{
			$villageId = (int) ($in['village_id'] ?? 0);
			if ($this->CI->Village_model->find($villageId) === NULL)
			{
				return ActionResult::fail('Admin Desa wajib dikaitkan dengan satu desa.');
			}
		}

		$db = $this->CI->db;
		$db->trans_begin();
		if ( ! $this->CI->User_model->update($targetId, array('full_name' => $name, 'role' => $role, 'village_id' => $villageId)))
		{
			$error = $db->error();
			$db->trans_rollback();

			return ActionResult::fail(DbError::friendly($error));
		}
		if ($role !== $target['role'])
		{
			if ($target['role'] === Role::JUDGE && ! $this->CI->Competition_model->sync_judge_assignments($targetId, array()))
			{
				$error = $db->error();
				$db->trans_rollback();

				return ActionResult::fail(DbError::friendly($error));
			}
			if ($target['role'] === Role::COMPETITION_ADMIN && ! $this->CI->Competition_model->sync_manager_assignments($targetId, array()))
			{
				$error = $db->error();
				$db->trans_rollback();

				return ActionResult::fail(DbError::friendly($error));
			}
		}
		$db->trans_commit();

		return ActionResult::ok('Akun diperbarui.');
	}

	public function reset_password(array $actor, int $targetId, string $password): ActionResult
	{
		$target = $this->CI->User_model->find($targetId);
		if ($target === NULL || ! Role::canManage($actor['role'], $target['role']))
		{
			return ActionResult::fail('Anda tidak berwenang mengatur ulang sandi akun ini.');
		}
		if ($password === '')
		{
			$password = Password::generate();
		}
		if (($error = Password::validate($password)) !== NULL)
		{
			return ActionResult::fail($error);
		}
		$this->CI->User_model->set_password($targetId, Password::hash($password), TRUE);

		return ActionResult::ok(sprintf('Sandi %s direset. Sandi sementara: %s (tampil sekali; wajib diganti saat login berikutnya).', $target['email'], $password));
	}

	public function delete(array $actor, int $targetId): ActionResult
	{
		if ((int) $actor['id'] === $targetId)
		{
			return ActionResult::fail('Anda tidak dapat menghapus akun sendiri.');
		}
		$target = $this->CI->User_model->find($targetId);
		if ($target === NULL || ! Role::canManage($actor['role'], $target['role']))
		{
			return ActionResult::fail('Anda tidak berwenang menghapus akun ini.');
		}
		if ( ! $this->CI->User_model->delete($targetId))
		{
			return ActionResult::fail(DbError::friendly($this->CI->db->error(), 'Akun tidak dapat dihapus.'));
		}

		return ActionResult::ok('Akun dihapus.');
	}
}
