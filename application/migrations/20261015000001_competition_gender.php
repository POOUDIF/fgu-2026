<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Make competition gender eligibility explicit and persist it for open entries. */
class Migration_Competition_gender extends CI_Migration
{
	public function up()
	{
		$columns = array_column($this->db->field_data('entries'), 'name');
		if ( ! in_array('gender', $columns, TRUE) && ! $this->db->query("ALTER TABLE entries ADD gender ENUM('L','P') NULL AFTER entry_type"))
		{
			throw new RuntimeException('Kolom gender pendaftaran gagal ditambahkan: '.$this->db->error()['message']);
		}

		$settings = array(
			array('lomba-mewarnai-dan-menceritakan', 'Mewarnai & Menceritakan', 'PAUD & TK (maks. 6 tahun)', 'Individu (putra/putri)', array('PAUD', 'TK'), NULL, NULL, array('L', 'P')),
			array('lomba-dakwah-online', 'Dakwah Online', 'PAUD, TK – SD Kelas 3', 'Individu (putra/putri)', array('PAUD', 'TK', 'SD'), 1, 3, array('L', 'P')),
			array('hafalan-asmaul-husna', 'Asmaul Husna', 'SD Kelas 1–3 (7–9 tahun)', 'Regu 2 orang (putra/putri)', array('SD'), 1, 3, array('L', 'P')),
			array('hafalan-doa-harian', 'Doa Harian', 'SD Kelas 1–3 (7–9 tahun)', 'Regu 2 orang (putra/putri/keduanya)', array('SD'), 1, 3, array('L', 'P')),
			array('praktek-sholat-berjamaah', 'Sholat Berjamaah', 'SD Kelas 1–3 (7–9 tahun)', 'Regu 10 orang (1 imam, 5 putra, 4 putri)', array('SD'), 1, 3, array('L', 'P')),
			array('hafalan-surat-pendek', 'Surat Pendek', 'SD Kelas 4–6 (10–12 tahun)', 'Individu (putra/putri)', array('SD'), 4, 6, array('L', 'P')),
			array('nasehat-pidato-agama-da-i-cilik', 'Dai Cilik', 'SD Kelas 4–6 (10–12 tahun)', 'Individu putra', array('SD'), 4, 6, array('L')),
			array('cerdas-cermat', 'Cerdas Cermat', 'SD Kelas 4–6 (10–12 tahun)', 'Regu 3 orang (putra/putri)', array('SD'), 4, 6, array('L', 'P')),
			array('lomba-adzan-subuh', 'Adzan Subuh', 'SMP Kelas 1–3 (13–15 tahun)', 'Individu putra', array('SMP'), 1, 3, array('L')),
			array('membaca-dan-menulis-pegon', 'Membaca & Menulis Pegon', 'SMP Kelas 1–3 (13–15 tahun)', '1 putra + 1 putri per desa', array('SMP'), 1, 3, array('L', 'P')),
			array('hafalan-dalil-al-qur-an-dan-al-hadits', 'Hafalan Dalil', 'SMP Kelas 1–3 (13–15 tahun)', '1 putra + 1 putri per desa', array('SMP'), 1, 3, array('L', 'P')),
			array('lomba-tafsir-al-qur-an', 'Tafsir Al-Quran', 'SMA/K Kelas 1–3 (16–18 tahun)', 'Individu (putra/putri)', array('SMA'), 1, 3, array('L', 'P')),
			array('lomba-masak', 'Masak', 'SMA/K Kelas 1–3 (16–18 tahun)', 'Regu 3 putri', array('SMA'), 1, 3, array('P')),
			array('lomba-stand-bazaar', 'Stand Bazaar', 'Remaja PGM • di atas 18 tahun', 'Grup (putra/putri)', array('PGM'), NULL, NULL, array('L', 'P')),
			array('lomba-karya-tulis', 'Karya Tulis', 'Remaja PGM • di atas 18 tahun', 'Individu (putra/putri)', array('PGM'), NULL, NULL, array('L', 'P')),
			array('video-campaign', 'Video Campaign', 'Umum • seluruh usia', 'Individu atau tim (putra/putri)', array('PAUD', 'TK', 'SD', 'SMP', 'SMA', 'PGM'), NULL, NULL, array('L', 'P')),
		);

		$this->db->trans_begin();
		try
		{
			foreach ($settings as [$slug, $label, $age, $type, $levels, $gradeMin, $gradeMax, $genders])
			{
				$this->db->where('slug', $slug);
				if ($this->db->count_all_results('competitions') !== 1)
				{
					throw new RuntimeException('Data lomba gender harus cocok tepat satu kali: '.$label);
				}

				$this->db->where('slug', $slug)->update('competitions', array(
					'type_label' => $type,
					'age_label' => $age,
					'levels' => json_encode($levels, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
					'grade_min' => $gradeMin,
					'grade_max' => $gradeMax,
					'allowed_genders' => json_encode($genders, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
				));
				if ( ! $this->db->trans_status())
				{
					throw new RuntimeException('Pembaruan gender lomba gagal: '.$this->db->error()['message']);
				}
			}

			$this->db->where('slug', 'lomba-adzan-subuh')->update('competitions', array('quota' => 1));
			$rulesPath = APPPATH.'../database/juklak_2026_rules.json';
			$rules = is_file($rulesPath) ? json_decode((string) file_get_contents($rulesPath), TRUE) : NULL;
			if ( ! is_array($rules) || ! isset($rules['cilik|nasehat'][0]))
			{
				throw new RuntimeException('Data juklak gender Da\'i Cilik tidak valid.');
			}
			$rules['cilik|nasehat'][0] = 'Tampil perorangan, maksimal 1 perwakilan putra per desa.';
			$this->db->where('slug', 'nasehat-pidato-agama-da-i-cilik')->update('competitions', array(
				'rules' => json_encode($rules['cilik|nasehat'], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
			));

			if ( ! $this->db->trans_status())
			{
				throw new RuntimeException('Pembaruan ketentuan lomba gagal: '.$this->db->error()['message']);
			}
			$this->db->trans_commit();
		}
		catch (Throwable $e)
		{
			$this->db->trans_rollback();
			throw $e;
		}
	}

	public function down()
	{
		if (in_array('gender', array_column($this->db->field_data('entries'), 'name'), TRUE)
			&& ! $this->db->query('ALTER TABLE entries DROP COLUMN gender'))
		{
			throw new RuntimeException('Kolom gender pendaftaran gagal dihapus: '.$this->db->error()['message']);
		}
	}
}
