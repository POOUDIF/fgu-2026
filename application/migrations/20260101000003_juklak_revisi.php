<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Add the revised open-registration fields to existing CI3 installations. */
class Migration_Juklak_revisi extends CI_Migration
{
	public function up()
	{
		$columns = array(
			'competitions' => array(
				'registration_mode' => "ALTER TABLE competitions ADD registration_mode ENUM('participant','open') NOT NULL DEFAULT 'participant' AFTER participation_type",
				'form_fields' => 'ALTER TABLE competitions ADD form_fields JSON NULL AFTER registration_mode',
				'allow_team' => 'ALTER TABLE competitions ADD allow_team TINYINT(1) NOT NULL DEFAULT 0 AFTER form_fields',
				'scoring_method' => "ALTER TABLE competitions ADD scoring_method ENUM('weighted','points') NOT NULL DEFAULT 'weighted' AFTER allow_team",
				'levels' => 'ALTER TABLE competitions ADD levels JSON NULL AFTER scoring_method',
				'grade_min' => 'ALTER TABLE competitions ADD grade_min TINYINT UNSIGNED NULL AFTER levels',
				'grade_max' => 'ALTER TABLE competitions ADD grade_max TINYINT UNSIGNED NULL AFTER grade_min',
				'allowed_genders' => 'ALTER TABLE competitions ADD allowed_genders JSON NULL AFTER grade_max',
			),
			'entries' => array(
				'team_name' => 'ALTER TABLE entries ADD team_name VARCHAR(100) NULL AFTER source',
				'parent_name' => 'ALTER TABLE entries ADD parent_name VARCHAR(100) NULL AFTER team_name',
				'ig_username' => 'ALTER TABLE entries ADD ig_username VARCHAR(30) NULL AFTER parent_name',
				'entry_type' => "ALTER TABLE entries ADD entry_type ENUM('individual','team') NULL AFTER ig_username",
				'members_note' => 'ALTER TABLE entries ADD members_note VARCHAR(500) NULL AFTER entry_type',
			),
			'criteria' => array(
				'weight' => 'ALTER TABLE criteria ADD weight DECIMAL(6,2) NOT NULL DEFAULT 100 AFTER name',
			),
			'participants' => array(
				'parent_name' => 'ALTER TABLE participants ADD parent_name VARCHAR(100) NULL AFTER age',
				'gender' => "ALTER TABLE participants ADD gender ENUM('L','P') NULL AFTER parent_name",
				'education_level' => "ALTER TABLE participants ADD education_level ENUM('PAUD','TK','SD','SMP','SMA','PGM') NULL AFTER gender",
				'grade' => 'ALTER TABLE participants ADD grade TINYINT UNSIGNED NULL AFTER education_level',
			),
		);

		foreach ($columns as $table => $definitions)
		{
			$existing = array_column($this->db->field_data($table), 'name');
			foreach ($definitions as $name => $sql)
			{
				if ( ! in_array($name, $existing, TRUE) && ! $this->db->query($sql))
				{
					throw new RuntimeException('Migrasi juklak gagal: '.$this->db->error()['message']);
				}
			}
		}

		$this->where_name_matches('Dakwah|Karya Tulis|Video Campaign');
		$this->db->update('competitions', array('registration_mode' => 'open', 'form_fields' => '["ig_username","submission_url"]', 'submission_mode' => 'online', 'participation_type' => 'individual', 'quota' => NULL));
		$this->where_name_matches('Video Campaign');
		$this->db->update('competitions', array('allow_team' => 1));
		$this->where_name_matches('Mewarnai');
		$this->db->update('competitions', array('registration_mode' => 'open', 'form_fields' => '["parent_name"]', 'submission_mode' => 'offline', 'participation_type' => 'individual', 'quota' => NULL));
		$this->where_name_matches('Cerdas Cermat');
		$this->db->update('competitions', array('scoring_method' => 'points'));

		$revisions = array(
			array('mewarna', array('cluster' => 'PAUD – TK', 'age_label' => 'PAUD & TK', 'type_label' => 'Individu', 'submission_mode' => 'offline', 'place' => 'Komplek Daerah', 'winner_count' => 5, 'rules' => array(
				'Pendaftaran dilakukan online melalui website resmi FGU 3.0.', 'Peserta membawa alat mewarnai dan meja masing-masing.',
				'Peserta mewarnai lembar gambar sesuai kreativitas dan kemampuan.', 'Lima peserta terbaik mempresentasikan makna gambar atau pengalaman yang relevan.',
				'Tidak boleh meminjam alat atau mendapat bantuan selama lomba.', 'Pendamping dilarang memasuki area lomba.',
				'Karya yang selesai segera dikumpulkan; sketsa A4 disediakan panitia.', 'Keputusan juri mutlak dan tidak dapat diganggu gugat.',
			))),
			array('dakwah', array('cluster' => 'PAUD – TK', 'age_label' => 'PAUD, TK – SD kelas 1', 'type_label' => 'Individu', 'submission_mode' => 'online', 'place' => 'Pre-event (daring)', 'rules' => array(
				'Video dakwah/nasehat maksimal 5 menit dalam format portrait.', 'Peserta menjadi pemeran/pendakwah; pembuatan video boleh dibantu orang tua atau pembimbing desa.',
				'Tema: Berbakti Kepada Orang Tua, Bersyukur, atau Menjaga Lisan dan Ucapan.', 'Gunakan bahasa umum dan tidak menyebut hal-hal bithonah.',
				'Upload maksimal H-3 ke media sosial pribadi/desa, tag @festivalgenerasiunggul dan @jamsirat, lalu submit melalui website.',
				'Tidak ada batas jumlah peserta per desa; penilaian dilakukan sebelum acara.',
			))),
			array('asmaul', array('cluster' => 'SD Kelas 1–3', 'age_label' => 'Kelas 1–3 SD', 'type_label' => 'Regu 2 orang', 'place' => 'Komplek Daerah', 'rules' => array(
				'Tampil beregu, 2 orang perwakilan per desa (putra/putri).', 'Pendamping per desa maksimal 1 orang.',
				'Pengujian pertama: peserta membaca Asmaul Husna bergantian dan berurutan.', 'Pengujian kedua: peserta mengambil undian; juri membaca 3 nomor dan peserta melanjutkan 5 nomor berikutnya.',
				'Waktu menjawab pengujian kedua maksimal 10 detik; urutan tampil sesuai Technical Meeting.',
			))),
			array('doa harian', array('cluster' => 'SD Kelas 1–3', 'age_label' => 'Kelas 1–3 SD', 'type_label' => 'Regu 2 orang', 'place' => 'Komplek Daerah', 'rules' => array(
				'Tampil beregu, 2 orang perwakilan per desa (bebas putra/putri/keduanya).', 'Pendamping per desa maksimal 1 orang.',
				'Doa merujuk pada Buku Saku Doa; bacaan dipilih melalui undian versi panjang atau pendek.', 'Juri membacakan soal sesuai hasil undian; urutan tampil sesuai Technical Meeting.',
			))),
			array('sholat|shalat', array('cluster' => 'SD Kelas 1–3', 'age_label' => 'Kelas 1–3 SD', 'type_label' => 'Regu 10 orang', 'place' => 'Komplek Daerah', 'rules' => array(
				'Tampil beregu 10 orang: 1 imam, 5 putra, dan 4 putri.', 'Pendamping per desa maksimal 1 orang; peserta yang belum tampil menunggu di tempat yang disediakan.',
				'Praktik sholat Subuh berjamaah. Bacaan surat ditentukan desa dan tidak terlalu panjang.', 'Semua bacaan dilakukan bersama kecuali Al-Fatihah dan surat pilihan oleh imam.',
				'Urutan tampil sesuai Technical Meeting.',
			))),
			array('surat pendek|juz\\s*30', array('cluster' => 'SD Kelas 4–6', 'age_label' => 'Kelas 4–6 SD', 'type_label' => 'Individu', 'place' => 'Komplek Daerah', 'rules' => array(
				'Maksimal 1 orang perwakilan desa (putra/putri); pendamping maksimal 1 orang.', 'Materi Juz 30 dari An-Nas sampai Al-Lail.',
				'Peserta mendapat dua sesi: soal undian dan soal langsung dari juri.', 'Pada sesi kedua juri membaca potongan surat dan peserta melanjutkan hingga dihentikan.',
				'Urutan tampil sesuai Technical Meeting.',
			))),
			array('cilik|nasehat', array('cluster' => 'SD Kelas 4–6', 'age_label' => 'Kelas 4–6 SD', 'type_label' => 'Individu', 'place' => 'Panggung Utama', 'rules' => array(
				'Tampil perorangan, maksimal 1 perwakilan desa.', 'Durasi maksimal 10 menit; keterlambatan waktu mengurangi nilai.',
				'Tema: Pentingnya Memiliki 29 Karakter Luhur. Peserta tampil tanpa teks.', 'Juri dapat menghentikan penampilan bila diperlukan; urutan sesuai Technical Meeting.',
			))),
			array('cerdas cermat', array('cluster' => 'SD Kelas 4–6', 'age_label' => 'Kelas 4–6 SD', 'type_label' => 'Regu 3 orang', 'place' => 'Komplek Daerah dan Panggung Utama', 'rules' => array(
				'Satu regu terdiri dari 3 orang per desa.', 'Babak pertama: soal bergantian; soal tidak terjawab hangus tanpa pengurangan nilai.',
				'Babak kedua memakai sistem lempar dan memilih 6 grup terbaik.', 'Babak penyisihan: 15 soal rebutan, benar +50, salah −20; 3 tim melaju ke final.',
				'Final di Panggung Utama: 15 soal rebutan, benar +100, salah −50.',
			))),
			array('adzan|azan', array('cluster' => 'SMP', 'age_label' => 'Kelas 1–3 SMP', 'type_label' => 'Individu putra', 'place' => 'Panggung Utama', 'rules' => array(
				'Tampil perorangan, 1 perwakilan putra per desa.', 'Adzan yang digunakan adalah Adzan Subuh dengan nada lagu bebas.',
				'Berpakaian sopan dan rapi; urutan tampil sesuai Technical Meeting.',
			))),
			array('pegon', array('cluster' => 'SMP', 'age_label' => 'Kelas 1–3 SMP', 'type_label' => 'Individu (putra dan putri)', 'place' => 'Komplek Daerah', 'rules' => array(
				'Perlombaan terdiri atas sesi Kitabah (menulis), Istima (mendengarkan), dan Qiraah (membaca).',
				'Peserta membawa alas tulis/meja lipat dan alat tulis sendiri.', 'Pendamping tidak diperkenankan masuk ruang lomba setelah lomba dimulai.',
			))),
			array('dalil', array('cluster' => 'SMP', 'age_label' => 'Kelas 1–3 SMP', 'type_label' => 'Individu (putra dan putri)', 'place' => 'Komplek Daerah', 'rules' => array(
				'Tampil perorangan, 2 perwakilan per desa (putra dan putri).', 'Materi: 6 Thobiat Luhur, 4 Maqodirullah, dan 3 Sukses beserta artinya.',
				'Juri memberi dua soal dan satu soal tambahan dari undian; urutan tampil sesuai Technical Meeting.',
			))),
			array('tafsir', array('cluster' => 'SMA / K', 'age_label' => 'Kelas 1–3 SMA/K', 'type_label' => 'Individu (putra/putri)', 'place' => 'Komplek Daerah', 'rules' => array(
				'Peserta memilih tema Bab Hukum atau Cerita dalam Al-Quran.', 'Peserta menyampaikan makna, keterangan, dan penjelasan ayat; durasi maksimal 15 menit.',
				'Berpakaian rapi dan syari; langsung menyampaikan tanpa muqodimah.', 'Urutan tampil sesuai Technical Meeting.',
			))),
			array('masak', array('cluster' => 'SMA / K', 'age_label' => 'Kelas 1–3 SMA/K', 'type_label' => 'Regu 3 putri', 'place' => 'Komplek Daerah', 'rules' => array(
				'Satu regu terdiri dari 3 putri per desa; bahan utama ikan dengan tema masakan Nusantara.', 'Durasi memasak 90 menit; lokasi meja ditentukan panitia.',
				'Anggaran bahan Rp250.000 per peserta disediakan panitia setelah pendaftaran.', 'Peserta menyiapkan alat, bahan, plating, dan LPJ anggaran.',
			))),
			array('bazaar|bazar', array('cluster' => 'Remaja PGM', 'age_label' => 'Remaja PGM', 'type_label' => 'Grup', 'place' => 'Komplek Daerah', 'rules' => array(
				'Peserta berupa grup; stand disiapkan pada malam sebelum hari pelaksanaan.', 'Tema bazar: Profesi. Kegiatan dimulai pukul 07.00 WIB.',
				'Produk kuliner/fashion dibuat kreatif; produk kuliner wajib halal dan aman.', 'Dekorasi serta dress code sesuai tema dan tetap syari; jaga kebersihan area.',
			))),
			array('karya tulis', array('cluster' => 'Remaja PGM', 'age_label' => 'Remaja PGM', 'type_label' => 'Individu', 'submission_mode' => 'online', 'place' => 'Komplek Daerah', 'rules' => array(
				'Karya tulis individu bertema 29 Karakter Luhur; bentuk karya bebas.', 'Peserta mempresentasikan atau menceritakan karya pada hari perlombaan.',
				'Karya tidak mengandung SARA, pornografi, atau konten yang melanggar aturan.', 'Karya dikumpulkan maksimal H-3 melalui website; berkas dapat berupa PNG/JPG, PDF, atau DOCX.',
			))),
			array('video campaign', array('cluster' => 'Kategori Umum', 'age_label' => 'Umum (terbuka untuk seluruh usia)', 'type_label' => 'Individu atau tim', 'submission_mode' => 'online', 'place' => 'Komplek Daerah', 'rules' => array(
				'Video mengangkat semangat, nilai, atau keunikan festival dan mengajak audiens berpartisipasi.', 'Peserta perorangan atau tim; pendaftaran melalui admin desa atau umum tanpa login.',
				'Setiap peserta/tim hanya mengirim satu video berdurasi 60–180 detik, orientasi vertikal 9:16.', 'Video memuat identitas FGU 3.0, waktu/lokasi acara, dan ajakan hadir.',
				'Karya harus orisinal, tidak mengandung konten terlarang, dan diunggah ke akun publik dengan tag akun resmi serta hashtag wajib.',
			))),
		);
		foreach ($revisions as $revision)
		{
			$fields = $revision[1];
			foreach (array('rules', 'levels', 'allowed_genders') as $jsonField)
			{
				if (isset($fields[$jsonField]) && is_array($fields[$jsonField]))
				{
					$fields[$jsonField] = json_encode($fields[$jsonField], JSON_UNESCAPED_UNICODE);
				}
			}
			$this->where_name_matches($revision[0]);
			$this->db->update('competitions', $fields);
		}
		$eligibility = array(
			array('mewarna', array('PAUD', 'TK'), NULL, NULL, array('L', 'P')),
			array('dakwah', array('PAUD', 'TK', 'SD'), 1, 1, array('L', 'P')),
			array('asmaul|doa harian|sholat|shalat', array('SD'), 1, 3, array('L', 'P')),
			array('surat pendek|juz\\s*30|cilik|nasehat|cerdas cermat', array('SD'), 4, 6, array('L', 'P')),
			array('adzan|azan|pegon|dalil', array('SMP'), 1, 3, array('L', 'P')),
			array('tafsir', array('SMA'), 1, 3, array('L', 'P')),
			array('masak', array('SMA'), 1, 3, array('P')),
			array('bazaar|bazar|karya tulis', array('PGM'), NULL, NULL, array('L', 'P')),
		);
		foreach ($eligibility as [$pattern, $levels, $gradeMin, $gradeMax, $genders])
		{
			$this->where_name_matches($pattern);
			$this->db->update('competitions', array(
				'levels' => json_encode($levels), 'grade_min' => $gradeMin, 'grade_max' => $gradeMax,
				'allowed_genders' => json_encode($genders),
			));
		}

		$weightsByName = array(
			'mewarna' => array(30, 40, 30), 'dakwah' => array(30, 40, 30), 'asmaul' => array(40, 30, 20, 10),
			'doa harian' => array(50, 30, 10, 10), 'sholat|shalat' => array(30, 30, 30, 10),
			'surat pendek|juz\\s*30' => array(40, 30, 20, 5, 5), 'cilik|nasehat' => array(20, 40, 5, 30, 5),
			'adzan|azan' => array(40, 30, 20, 10), 'dalil' => array(40, 25, 35), 'tafsir' => array(70, 15, 15),
			'masak' => array(35, 20, 15, 15, 15), 'bazaar|bazar' => array(40, 25, 20, 15), 'karya tulis' => array(40, 30, 30),
		);
		$criterionNames = array(
			'mewarna' => array('Komposisi warna', 'Motorik & ketelitian', 'Kerapihan & kebersihan'),
			'dakwah' => array('Penyampaian (Bahasa, Mimik, Gesture)', 'Penguasaan Materi (Pesan Yang Disampaikan)', 'Penampilan'),
			'asmaul' => array('Kebenaran bacaan (makhroj & tajwid)', 'Kelengkapan hafalan (99 asma sesuai urutan)', 'Keindahan lantunan (lagu/nada)', 'Kekompakan'),
			'doa harian' => array('Kelancaran', 'Makhorijul huruf dan Tajwid', 'Kekompakan', 'Penampilan seragam'),
			'sholat|shalat' => array('Sikap & gerakan sholat', 'Tuma’ninah sholat', 'Bacaan sholat + lagu', 'Kekompakan gerakan'),
			'surat pendek|juz\\s*30' => array('Kelancaran', 'Tajwid', 'Makhorijul huruf', 'Lagu', 'Kerapian penampilan'),
			'cilik|nasehat' => array('Relevansi dengan tema', 'Penguasaan materi nasehat', 'Ketepatan waktu', 'Retorika (intonasi, ekspresi, persuasi)', 'Kerapian penampilan'),
			'adzan|azan' => array('Lagu adzan', 'Makhorijul huruf', 'Sikap & gerak (sunnah adzan)', 'Kerapian penampilan'),
			'dalil' => array('Kelancaran lafadz', 'Arti dalil', 'Makhorijul huruf'),
			'tafsir' => array('Kejelasan penyampaian', 'Penampilan (busana)', 'Relevansi dengan tema'),
			'masak' => array('Cita rasa', 'Penyajian', 'Kebersihan', 'LPJ', 'Waktu'),
			'bazaar|bazar' => array('Dekorasi stand', 'Dress code peserta', 'Keunikan produk', 'Kebersihan & kerapihan'),
			'karya tulis' => array('Kesesuaian tema', 'Orisinalitas & keunikan karya', 'Kerapihan penulisan'),
		);
		foreach ($weightsByName as $pattern => $weights)
		{
			$this->where_name_matches($pattern);
			$competition = $this->db->select('id')->get('competitions')->row_array();
			if ( ! $competition)
			{
				continue;
			}
			$hasScores = $this->db->from('scores s')->join('criteria cr', 'cr.id = s.criterion_id')
				->where('cr.competition_id', (int) $competition['id'])->count_all_results() > 0;
			$criteria = $this->db->where('competition_id', (int) $competition['id'])->order_by('sort_order')->order_by('id')->get('criteria')->result_array();
			if ( ! $hasScores && count($criteria) === count($weights))
			{
				foreach ($criteria as $index => $criterion)
				{
					$update = array('weight' => $weights[$index]);
					if (isset($criterionNames[$pattern][$index]))
					{
						$update['name'] = $criterionNames[$pattern][$index];
					}
					$this->db->where('id', (int) $criterion['id'])->update('criteria', $update);
				}
			}
		}
	}

	private function where_name_matches(string $pattern): void
	{
		$this->db->where('name REGEXP '.$this->db->escape($pattern), NULL, FALSE);
	}

	public function down()
	{
		$this->db->query('ALTER TABLE entries DROP COLUMN members_note, DROP COLUMN entry_type, DROP COLUMN ig_username, DROP COLUMN parent_name, DROP COLUMN team_name');
		$this->db->query('ALTER TABLE criteria DROP COLUMN weight');
		$this->db->query('ALTER TABLE competitions DROP COLUMN scoring_method, DROP COLUMN allow_team, DROP COLUMN form_fields, DROP COLUMN registration_mode');
		$this->db->query('ALTER TABLE participants DROP COLUMN grade, DROP COLUMN education_level, DROP COLUMN gender, DROP COLUMN parent_name');
		$this->db->query('ALTER TABLE competitions DROP COLUMN allowed_genders, DROP COLUMN grade_max, DROP COLUMN grade_min, DROP COLUMN levels');
	}
}