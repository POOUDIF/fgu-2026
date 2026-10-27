<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Audit_model extends CI_Model
{
	public function log(array $row): bool
	{
		return (bool) $this->db->insert('score_audit_log', $row);
	}

	/** @return list<array<string,mixed>> */
	public function recent(?int $competitionId = NULL, int $limit = 50): array
	{
		if ($competitionId !== NULL)
		{
			$this->db->where('competition_id', $competitionId);
		}

		return $this->db->order_by('changed_at', 'DESC')->order_by('id', 'DESC')->limit($limit)->get('score_audit_log')->result_array();
	}
}
