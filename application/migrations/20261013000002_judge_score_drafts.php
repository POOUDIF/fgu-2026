<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Keep judge review drafts separate from submitted scores and competition results. */
class Migration_Judge_score_drafts extends CI_Migration
{
	public function up()
	{
		$sql = "CREATE TABLE score_drafts (
			id INT UNSIGNED NOT NULL AUTO_INCREMENT,
			entry_id INT UNSIGNED NOT NULL,
			judge_id INT UNSIGNED NOT NULL,
			criterion_id INT UNSIGNED NOT NULL,
			score DECIMAL(8,2) NOT NULL,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY score_drafts_one_per_criterion (entry_id, judge_id, criterion_id),
			KEY score_drafts_judge (judge_id),
			CONSTRAINT score_drafts_entry_fk FOREIGN KEY (entry_id) REFERENCES entries (id) ON DELETE CASCADE,
			CONSTRAINT score_drafts_judge_fk FOREIGN KEY (judge_id) REFERENCES users (id) ON DELETE CASCADE,
			CONSTRAINT score_drafts_criterion_fk FOREIGN KEY (criterion_id) REFERENCES criteria (id) ON DELETE CASCADE,
			CONSTRAINT score_drafts_non_negative CHECK (score >= 0)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
		if ($this->db->query($sql) === FALSE)
		{
			throw new RuntimeException('Migrasi draft penilaian gagal: '.$this->db->error()['message']);
		}
	}

	public function down()
	{
		if ($this->db->query('DROP TABLE IF EXISTS score_drafts') === FALSE)
		{
			throw new RuntimeException('Rollback draft penilaian gagal: '.$this->db->error()['message']);
		}
	}
}
