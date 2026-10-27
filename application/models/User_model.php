<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use App\Domain\Role;

class User_model extends CI_Model
{
	private const COLUMNS = 'u.id, u.full_name, u.email, u.role, u.village_id, u.must_change_password, u.is_active, u.last_login_at, u.created_at, v.name AS village_name';

	public function find(int $id): ?array
	{
		$row = $this->db->select(self::COLUMNS)
			->from('users u')
			->join('villages v', 'v.id = u.village_id', 'left')
			->where('u.id', $id)
			->get()->row_array();

		return $row ?: null;
	}

	/** Termasuk password_hash; hanya untuk proses login. */
	public function find_for_login(string $email): ?array
	{
		$row = $this->db->where('email', $this->normalize_email($email))->get('users')->row_array();

		return $row ?: null;
	}

	public function find_hash(int $id): ?string
	{
		$row = $this->db->select('password_hash')->where('id', $id)->get('users')->row_array();

		return $row ? $row['password_hash'] : null;
	}

	/** @return list<array<string,mixed>> */
	public function all(): array
	{
		return $this->db->select(self::COLUMNS)
			->from('users u')
			->join('villages v', 'v.id = u.village_id', 'left')
			->order_by("FIELD(u.role, 'super_admin','regional_admin','competition_admin','village_admin','judge')", '', FALSE)
			->order_by('u.full_name')
			->get()->result_array();
	}

	/** @return list<array<string,mixed>> */
	public function judges(): array
	{
		return $this->db->select('id, full_name, email')
			->where('role', Role::JUDGE)->where('is_active', 1)
			->order_by('full_name')->get('users')->result_array();
	}

	public function normalize_email(string $email): string
	{
		return mb_strtolower(trim($email));
	}

	public function email_exists(string $email, ?int $exceptId = NULL): bool
	{
		$this->db->where('email', $this->normalize_email($email));
		if ($exceptId !== NULL)
		{
			$this->db->where('id !=', $exceptId);
		}

		return $this->db->count_all_results('users') > 0;
	}

	/**
	 * @param array{full_name:string,email:string,password_hash:string,role:string,village_id:?int,must_change_password?:int} $data
	 * @return int|null id baru, atau NULL bila gagal (lihat $this->db->error())
	 */
	public function create(array $data): ?int
	{
		$data['email'] = $this->normalize_email($data['email']);
		if ( ! $this->db->insert('users', $data))
		{
			return NULL;
		}

		return (int) $this->db->insert_id();
	}

	public function update(int $id, array $data): bool
	{
		return (bool) $this->db->where('id', $id)->update('users', $data);
	}

	public function set_password(int $id, string $hash, bool $mustChange): bool
	{
		return $this->update($id, array('password_hash' => $hash, 'must_change_password' => $mustChange ? 1 : 0));
	}

	public function touch_login(int $id): void
	{
		$this->db->where('id', $id)->update('users', array('last_login_at' => date('Y-m-d H:i:s')));
	}

	public function delete(int $id): bool
	{
		return (bool) $this->db->where('id', $id)->delete('users');
	}

	public function count_super_admins(): int
	{
		return (int) $this->db->where('role', Role::SUPER_ADMIN)->where('is_active', 1)->count_all_results('users');
	}
}
