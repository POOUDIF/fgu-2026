<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Event_model extends CI_Model
{
	/** Aplikasi hanya mengenal satu acara: baris tertua. */
	public function current(): ?array
	{
		$row = $this->db->order_by('created_at')->order_by('id')->limit(1)->get('events')->row_array();

		return $row ?: NULL;
	}

	public function update(int $id, array $data): bool
	{
		return (bool) $this->db->where('id', $id)->update('events', $data);
	}

	public function rundown(): array
	{
		return $this->db->order_by('sort_order')->order_by('id')->get('event_rundown')->result_array();
	}

	public function save_rundown(array $rows): bool
	{
		$this->db->trans_begin();
		if ( ! $this->db->delete('event_rundown'))
		{
			$this->db->trans_rollback();

			return FALSE;
		}
		foreach ($rows as $index => $row)
		{
			if ( ! $this->db->insert('event_rundown', array(
				'start_time' => $row['start_time'], 'detail' => $row['detail'],
				'location' => $row['location'], 'sort_order' => $index,
			)))
			{
				$this->db->trans_rollback();

				return FALSE;
			}
		}
		if ( ! $this->db->trans_status())
		{
			$this->db->trans_rollback();

			return FALSE;
		}
		$this->db->trans_commit();

		return TRUE;
	}
}
