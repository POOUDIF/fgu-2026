<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Store shared Cerdas Cermat round scores and the active round. */
class Migration_Cerdas_cermat_round_board extends CI_Migration
{
	public function up()
	{
		$statements = array(
			"CREATE TABLE cerdas_cermat_progress (
				competition_id INT UNSIGNED NOT NULL,
				current_round TINYINT UNSIGNED NOT NULL DEFAULT 1,
				is_finished TINYINT(1) NOT NULL DEFAULT 0,
				updated_by INT UNSIGNED NULL,
				updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
				PRIMARY KEY (competition_id),
				CONSTRAINT cc_progress_competition_fk FOREIGN KEY (competition_id) REFERENCES competitions (id) ON DELETE CASCADE,
				CONSTRAINT cc_progress_updated_by_fk FOREIGN KEY (updated_by) REFERENCES users (id) ON DELETE SET NULL,
				CONSTRAINT cc_progress_round CHECK (current_round BETWEEN 1 AND 4)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
			"CREATE TABLE cerdas_cermat_round_scores (
				entry_id INT UNSIGNED NOT NULL,
				round_number TINYINT UNSIGNED NOT NULL,
				points DECIMAL(8,2) NOT NULL DEFAULT 0,
				created_by INT UNSIGNED NULL,
				updated_by INT UNSIGNED NULL,
				created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
				updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
				PRIMARY KEY (entry_id, round_number),
				CONSTRAINT cc_round_score_entry_fk FOREIGN KEY (entry_id) REFERENCES entries (id) ON DELETE CASCADE,
				CONSTRAINT cc_round_score_created_by_fk FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE SET NULL,
				CONSTRAINT cc_round_score_updated_by_fk FOREIGN KEY (updated_by) REFERENCES users (id) ON DELETE SET NULL,
				CONSTRAINT cc_round_score_round CHECK (round_number BETWEEN 1 AND 4),
				CONSTRAINT cc_round_score_non_negative CHECK (points >= 0)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
		);

		foreach ($statements as $sql)
		{
			if ($this->db->query($sql) === FALSE)
			{
				throw new RuntimeException('Migrasi papan Cerdas Cermat gagal: '.$this->db->error()['message']);
			}
		}
	}

	public function down()
	{
		foreach (array('cerdas_cermat_round_scores', 'cerdas_cermat_progress') as $table)
		{
			if ($this->db->query('DROP TABLE IF EXISTS '.$table) === FALSE)
			{
				throw new RuntimeException('Rollback papan Cerdas Cermat gagal: '.$this->db->error()['message']);
			}
		}
	}
}
