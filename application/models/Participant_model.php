<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use App\Domain\RegistrationInput;

class Participant_model extends CI_Model
{
	/** Peserta milik satu desa, termasuk path foto privat. */
	public function for_village(int $villageId): array
	{
		return $this->db->select('p.*, c.name AS competition_name, c.slug AS competition_slug, e.id AS entry_id, e.competition_id, e.submission_url')
			->from('participants p')
			->join('entry_members em', 'em.participant_id = p.id', 'left')
			->join('entries e', 'e.id = em.entry_id', 'left')
			->join('competitions c', 'c.id = e.competition_id', 'left')
			->where('p.village_id', $villageId)->order_by('p.name')->get()->result_array();
	}

	public function find_for_village(int $id, int $villageId): ?array
	{
		$row = $this->db->where(array('id' => $id, 'village_id' => $villageId))->get('participants')->row_array();

		return $row ?: NULL;
	}

	public function find(int $id): ?array
	{
		$row = $this->db->where('id', $id)->get('participants')->row_array();

		return $row ?: NULL;
	}

	public function set_photo_path(int $id, int $villageId, string $path): bool
	{
		return (bool) $this->db->where(array('id' => $id, 'village_id' => $villageId))->update('participants', array('photo_path' => $path));
	}

	public function set_gender(int $id, string $gender): bool
	{
		return (bool) $this->db->where('id', $id)->update('participants', array('gender' => $gender));
	}

	public function create(int $villageId, string $name, ?int $age, array $profile = array()): ?int
	{
		$ok = $this->db->insert('participants', array(
			'village_id' => $villageId,
			'name' => $name,
			'name_key' => RegistrationInput::nameKey($name),
			'age' => $age,
			'parent_name' => $profile['parent_name'] ?? NULL,
			'gender' => $profile['gender'] ?? NULL,
			'education_level' => $profile['education_level'] ?? NULL,
			'grade' => $profile['grade'] ?? NULL,
			'photo_path' => $profile['photo_path'] ?? NULL,
		));

		return $ok ? (int) $this->db->insert_id() : NULL;
	}

	/** @param list<int> $ids */
	public function delete_many(array $ids): void
	{
		if ($ids !== array())
		{
			$this->db->where_in('id', array_map('intval', $ids))->delete('participants');
		}
	}

	/**
	 * Nama-nama (yang diajukan) yang sudah terdaftar pada lomba LAIN di desa yang sama.
	 *
	 * @param list<string> $names
	 * @return list<string>
	 */
	public function names_in_other_competitions(int $villageId, int $competitionId, array $names): array
	{
		$keys = array_values(array_unique(array_map(array(RegistrationInput::class, 'nameKey'), $names)));
		if ($keys === array())
		{
			return array();
		}
		$rows = $this->db->select('p.name')
			->from('participants p')
			->join('entry_members em', 'em.participant_id = p.id')
			->join('entries e', 'e.id = em.entry_id')
			->where('p.village_id', $villageId)
			->where('e.competition_id !=', $competitionId)
			->where_in('p.name_key', $keys)
			->get()->result_array();

		return array_column($rows, 'name');
	}

	public function count_by_village(int $villageId): int
	{
		return (int) $this->db->where('village_id', $villageId)->count_all_results('participants');
	}

	public function count_all(): int
	{
		return (int) $this->db->count_all('participants');
	}

	/** Jumlah peserta terdata per desa. */
	public function counts_by_village(): array
	{
		$rows = $this->db->select('village_id, COUNT(*) AS n', FALSE)
			->group_by('village_id')->get('participants')->result_array();
		$counts = array();
		foreach ($rows as $row)
		{
			$counts[(int) $row['village_id']] = (int) $row['n'];
		}

		return $counts;
	}
}
