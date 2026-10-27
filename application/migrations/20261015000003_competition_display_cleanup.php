<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Separate participant format, gender eligibility, and school-category labels. */
class Migration_Competition_display_cleanup extends CI_Migration
{
	public function up()
	{
		$this->update_labels(FALSE);
	}

	public function down()
	{
		$this->update_labels(TRUE);
	}

	private function update_labels(bool $legacy): void
	{
		$labels = array(
			array('lomba-mewarnai-dan-menceritakan', 'PAUD & TK', 'Individu', 'PAUD & TK (maks. 6 tahun)', 'Individu (putra/putri)'),
			array('lomba-dakwah-online', 'PAUD, TK – SD Kelas 3', 'Individu', 'PAUD, TK – SD Kelas 3', 'Individu (putra/putri)'),
			array('hafalan-asmaul-husna', 'SD Kelas 1–3', 'Regu 2 orang', 'SD Kelas 1–3 (7–9 tahun)', 'Regu 2 orang (putra/putri)'),
			array('hafalan-doa-harian', 'SD Kelas 1–3', 'Regu 2 orang', 'SD Kelas 1–3 (7–9 tahun)', 'Regu 2 orang (putra/putri/keduanya)'),
			array('praktek-sholat-berjamaah', 'SD Kelas 1–3', 'Regu 10 orang', 'SD Kelas 1–3 (7–9 tahun)', 'Regu 10 orang (1 imam, 5 putra, 4 putri)'),
			array('hafalan-surat-pendek', 'SD Kelas 4–6', 'Individu', 'SD Kelas 4–6 (10–12 tahun)', 'Individu (putra/putri)'),
			array('nasehat-pidato-agama-da-i-cilik', 'SD Kelas 4–6', 'Individu', 'SD Kelas 4–6 (10–12 tahun)', 'Individu putra'),
			array('cerdas-cermat', 'SD Kelas 4–6', 'Regu 3 orang', 'SD Kelas 4–6 (10–12 tahun)', 'Regu 3 orang (putra/putri)'),
			array('lomba-adzan-subuh', 'SMP Kelas 1–3', 'Individu', 'SMP Kelas 1–3 (13–15 tahun)', 'Individu putra'),
			array('membaca-dan-menulis-pegon', 'SMP Kelas 1–3', 'Individu', 'SMP Kelas 1–3 (13–15 tahun)', '1 putra + 1 putri per desa'),
			array('hafalan-dalil-al-qur-an-dan-al-hadits', 'SMP Kelas 1–3', 'Individu', 'SMP Kelas 1–3 (13–15 tahun)', '1 putra + 1 putri per desa'),
			array('lomba-tafsir-al-qur-an', 'SMA/K Kelas 1–3', 'Individu', 'SMA/K Kelas 1–3 (16–18 tahun)', 'Individu (putra/putri)'),
			array('lomba-masak', 'SMA/K Kelas 1–3', 'Regu 3 orang', 'SMA/K Kelas 1–3 (16–18 tahun)', 'Regu 3 putri'),
			array('lomba-stand-bazaar', 'Remaja PGM', 'Grup', 'Remaja PGM • di atas 18 tahun', 'Grup (putra/putri)'),
			array('lomba-karya-tulis', 'Remaja PGM', 'Individu', 'Remaja PGM • di atas 18 tahun', 'Individu (putra/putri)'),
			array('video-campaign', 'Umum', 'Individu atau tim', 'Umum • seluruh usia', 'Individu atau tim (putra/putri)'),
		);

		$this->db->trans_begin();
		try
		{
			foreach ($labels as [$slug, $age, $type, $legacyAge, $legacyType])
			{
				$this->db->where('slug', $slug);
				if ($this->db->count_all_results('competitions') !== 1)
				{
					throw new RuntimeException('Data kategori lomba harus cocok tepat satu kali: '.$slug);
				}

				$updated = $this->db->where('slug', $slug)->update('competitions', array(
					'age_label' => $legacy ? $legacyAge : $age,
					'type_label' => $legacy ? $legacyType : $type,
				));
				if ( ! $updated || ! $this->db->trans_status())
				{
					throw new RuntimeException('Pembaruan tampilan lomba gagal: '.$this->db->error()['message']);
				}
			}

			$this->db->trans_commit();
		}
		catch (Throwable $e)
		{
			$this->db->trans_rollback();
			throw $e;
		}
	}
}
