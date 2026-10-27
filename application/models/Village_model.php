<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Village_model extends CI_Model
{
	/** @return list<array<string,mixed>> */
	public function all(): array
	{
		return $this->db->order_by('name')->order_by('id')->get('villages')->result_array();
	}

	public function find(int $id): ?array
	{
		$row = $this->db->where('id', $id)->get('villages')->row_array();

		return $row ?: NULL;
	}

	public function count(): int
	{
		return (int) $this->db->count_all('villages');
	}

	public function create(string $name, ?string $pic): ?int
	{
		$next = (int) $this->db->select_max('sort_order', 'm')->get('villages')->row()->m + 1;

		return $this->db->insert('villages', array('name' => $name, 'pic_name' => $pic, 'sort_order' => $next))
			? (int) $this->db->insert_id() : NULL;
	}

	public function update(int $id, string $name, ?string $pic): bool
	{
		return (bool) $this->db->where('id', $id)->update('villages', array('name' => $name, 'pic_name' => $pic));
	}

	public function delete(int $id): bool
	{
		return (bool) $this->db->where('id', $id)->delete('villages');
	}
}
