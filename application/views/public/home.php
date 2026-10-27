<?php
/**
 * Beranda publik — mengikuti prototype FGU 3.0.
 * @var array|null $event; @var array $site, $competitions, $villages, $online, $open; @var int $online_count; @var bool $umkm_registration_open; @var string $registration
 */
use App\Support\Format;

$logo = asset('img/logo-fgu.png');
$eventDate = $event['event_date'] ?? null;
$dateLabel = $eventDate ? Format::date($eventDate) : 'Segera diumumkan';
$dayLabel = $eventDate ? Format::weekday($eventDate) : '';
$venue = $event['venue'] ?? 'Komplek Daerah';
$competitionGroups = array();
foreach ($competitions as $competition)
{
	$groupName = trim((string) ($competition['cluster'] ?? '')) ?: 'Lainnya';
	$competitionGroups[$groupName][] = $competition;
}
$categoryCount = count(array_filter(array_keys($competitionGroups), static fn ($name) => stripos($name, 'umum') === FALSE));
$juknisPdf = site_url('juklak');
$openSummary = static function (array $competition): array {
	$name = mb_strtolower($competition['name']);
	if (mb_strpos($name, 'mewarna') !== FALSE)
	{
		return array('icon' => '🎨', 'fields' => 'nama peserta · nama orang tua', 'deadline' => NULL);
	}
	if (mb_strpos($name, 'dakwah') !== FALSE)
	{
		return array('icon' => '🗣️', 'fields' => 'nama peserta · username Instagram · link video/karya', 'deadline' => '29 Okt 2026, 23.59');
	}
	if (mb_strpos($name, 'karya tulis') !== FALSE)
	{
		return array('icon' => '📝', 'fields' => 'nama peserta · username Instagram · link video/karya', 'deadline' => '29 Okt 2026, 23.59');
	}

	return array('icon' => '📹', 'fields' => 'nama peserta · username Instagram · link video/karya', 'deadline' => NULL);
};
$openRow = static function (array $c): string {
	ob_start(); ?>
	<fieldset class="participantRow">
		<legend>Peserta</legend>
		<div class="formGrid">
			<?php if ($c['allow_team']): ?><div class="field"><label>Jenis Pendaftaran</label><select name="entry_type[]"><option value="individual">Individu</option><option value="team">Tim</option></select></div><?php endif; ?>
			<div class="field"><label>Nama <?= $c['allow_team'] ? 'Peserta / Tim' : 'peserta' ?> <span class="requiredMark">*</span></label><input type="text" name="name[]" maxlength="100" required placeholder="Nama lengkap peserta"></div>
			<div class="field"><label>Gender peserta <span class="requiredMark">*</span></label><select name="gender[]" required><option value="">Pilih gender…</option>			<?php if (empty($c['allowed_genders']) || in_array('L', $c['allowed_genders'], true)): ?><option value="L">Putra</option><?php endif; ?><?php if (empty($c['allowed_genders']) || in_array('P', $c['allowed_genders'], true)): ?><option value="P">Putri</option><?php endif; ?></select></div>
			<?php if (in_array('parent_name', $c['form_fields'], true)): ?><div class="field"><label>Nama orang tua <span class="requiredMark">*</span></label><input type="text" name="parent_name[]" maxlength="100" required></div><?php endif; ?>
			<?php if (in_array('ig_username', $c['form_fields'], true)): ?><div class="field"><label>Username Instagram <span class="requiredMark">*</span></label><input type="text" name="ig_username[]" maxlength="31" required placeholder="@nama.akun"></div><?php endif; ?>
			<?php if (in_array('submission_url', $c['form_fields'], true)): ?><div class="field full"><label>Link video/karya <span class="requiredMark">*</span></label><input type="url" name="submission_url[]" maxlength="500" required placeholder="https://..."></div><?php endif; ?>
			<?php if ($c['allow_team']): ?><div class="field full"><label>Daftar anggota tim (opsional)</label><textarea name="members_note[]" maxlength="500" rows="2"></textarea></div><?php endif; ?>
		</div>
		<button type="button" class="btn ghost sm participantRemove" data-row-remove>Hapus peserta</button>
	</fieldset>
	<?php return (string) ob_get_clean();
};

$winnersLabel = static fn (array $c): string => $c['winner_count'] === 3 ? 'Juara 1, 2, 3' : $c['winner_count'].' terbaik';
$windowText = 'Tanggal akan ditentukan';
if (!empty($event['registration_opens_at']) || !empty($event['registration_closes_at'])) {
	$windowText = trim(
		(!empty($event['registration_opens_at']) ? fmt_datetime($event['registration_opens_at']) : 'Sekarang')
		.' – '.(!empty($event['registration_closes_at']) ? fmt_datetime($event['registration_closes_at']) : 'selesai')
	);
}
?>
<span id="top"></span>

<section class="hero">
	<div class="container">
		<div class="heroGrid">
			<div class="heroCopy">
				<div class="heroBadges">
					<div class="kicker">🏆 FGU 3.0 • Bekasi Barat • 2026</div>
					<?php if ($registration === 'open'): ?><?= tag('Pendaftaran dibuka') ?>
					<?php elseif ($registration === 'not_open'): ?><?= tag('Pendaftaran belum dibuka', 'yellow') ?>
					<?php else: ?><?= tag('Pendaftaran ditutup', 'red') ?><?php endif; ?>
				</div>
				<h1>Festival Generasi <span>Unggul</span> 3.0</h1>
				<p class="lead"><?= e($site['tagline']) ?></p>
				<div class="heroBtns">
					<a class="btn primary" href="<?= e(site_url('dashboard')) ?>">Klik Pendaftaran</a>
					<a class="btn ghost" href="#pengumpulan">Daftar &amp; Kumpulkan Karya</a>
				</div>
				<div class="stats">
					<div class="stat"><b><?= count($competitions) ?></b><span>Cabang lomba</span></div>
					<div class="stat"><b><?= count($villages) ?></b><span>Desa</span></div>
					<div class="stat"><b><?= $categoryCount ?></b><span>Kategori kelas</span></div>
					<div class="stat"><b><?= $online_count ?></b><span>Lomba online</span></div>
				</div>
			</div>
			<div class="heroLogo"><div class="logoRing"><img src="<?= e($logo) ?>" alt="Logo Festival Generasi Unggul"></div></div>
		</div>
		<div class="cards g3 heroInfo">
			<div class="card"><div class="icon">📅</div><b><?= e($dateLabel) ?></b><small class="sub"><?= e(trim($dayLabel.($dayLabel ? ' • ' : '').'07.00 WIB–selesai')) ?></small></div>
			<div class="card"><div class="icon">📍</div><b><?= e($venue) ?></b><small class="sub">Tempat pelaksanaan utama</small></div>
			<div class="card"><div class="icon">👥</div><b><?= count($villages) ?> Desa</b><small class="sub">Se-Daerah Bekasi Barat</small></div>
		</div>
	</div>
</section>

<section id="juknis" class="section">
	<div class="container">
		<div class="sectionHead"><div><div class="eyebrow">Juklak &amp; Juknis • Ringkas</div><h2>Aturan umum yang wajib diperhatikan</h2><p>Disarikan dari regulasi FGU 3.0 tahun 2026.</p></div></div>
		<div class="notice">
			<div class="noticeBox">
				<h2>Sebelum ikut lomba</h2>
				<div class="checkGrid">
					<div class="check">⏰ Hadir 15 menit sebelum lomba dimulai</div>
					<div class="check">⚠️ Toleransi terlambat 10 menit (ada pengurangan poin)</div>
					<div class="check">🚫 Peserta tidak boleh ikut dua lomba berbeda</div>
					<div class="check">👥 Setiap desa wajib mengirim 2 pendamping</div>
					<div class="check">📌 Boleh peserta dari kategori di bawah lomba</div>
					<div class="check">⛔ Tidak boleh melewati batas kategori lomba</div>
					<div class="check">➖ Tidak disiplin &amp; mengganggu: minus 1 poin/pelanggaran</div>
					<div class="check">🏛️ Panitia tidak boleh ikut lomba</div>
				</div>
			</div>
			<div class="card">
				<h3>Ketentuan lainnya</h3>
				<ul class="bullets">
					<li>Peserta adalah caberawit, pra remaja, dan remaja dari <?= count($villages) ?> Desa se-Daerah Bekasi Barat yang sudah mendaftar ke panitia.</li>
					<li>Pendaftaran melalui website resmi FGU 3.0; periode pendaftaran akan ditentukan panitia.</li>
					<li>Urutan penampilan ditentukan melalui Technical Meeting.</li>
					<li>Pemenang yang tidak sesuai kriteria peserta akan didiskualifikasi.</li>
					<li>Keputusan dewan juri tidak dapat diganggu gugat, kecuali ada kesalahan panitia yang akan diperbaiki.</li>
					<li>Kedisiplinan bukan kriteria penilaian.</li>
				</ul>
			</div>
		</div>
		<div class="documentCard card">
			<div class="icon">📄</div>
			<div><h3>Juklak &amp; Juknis Lengkap — Semua Lomba</h3><p>Dokumen PDF berisi ketentuan umum serta petunjuk teknis, kriteria penilaian, dan perlengkapan <?= count($competitions) ?> cabang lomba.</p></div>
			<div class="row wrap"><a class="btn soft" href="<?= e($juknisPdf) ?>" target="_blank" rel="noopener noreferrer">Buka PDF</a><a class="btn ghost" href="<?= e($juknisPdf.'?download=1') ?>">Unduh</a></div>
		</div>
		<div class="sectionHead subSectionHead"><div><div class="eyebrow">Kisi-kisi Lomba</div><h2>Kisi-kisi materi</h2></div></div>
		<div class="cards g3">
			<div class="card kisiCard">
				<b>Kisi-kisi Hafalan Doa Harian</b><span class="tag blue">SD Kelas 1–3</span>
				<p>Materi hafalan sesuai petunjuk lomba Hafalan Doa Harian.</p>
				<div class="row wrap">
					<a class="btn soft" href="https://drive.google.com/file/d/10sE4aUdOSjbADVYowFJSm4fxGrhxosru/view?usp=sharing" target="_blank" rel="noopener noreferrer">Buka File</a>
					<a class="btn ghost" href="https://drive.google.com/uc?export=download&amp;id=10sE4aUdOSjbADVYowFJSm4fxGrhxosru" target="_blank" rel="noopener noreferrer">Unduh</a>
				</div>
			</div>
			<div class="card kisiCard">
				<b>Kisi-kisi Hafalan Dalil Al-Qur’an &amp; Al-Hadits</b><span class="tag blue">SMP Kelas 1–3</span>
				<p>Materi hafalan sesuai petunjuk lomba Hafalan Dalil.</p>
				<div class="row wrap">
					<a class="btn soft" href="https://drive.google.com/file/d/1BnHaMcgE_nUaKYylraSTomfPCd8Nfn4Z/view?usp=sharing" target="_blank" rel="noopener noreferrer">Buka File</a>
					<a class="btn ghost" href="https://drive.google.com/uc?export=download&amp;id=1BnHaMcgE_nUaKYylraSTomfPCd8Nfn4Z" target="_blank" rel="noopener noreferrer">Unduh</a>
				</div>
			</div>
		</div>
	</div>
</section>

<section id="kategori" class="section alt">
	<div class="container">
		<div class="sectionHead"><div><div class="eyebrow">Kategori Kelas</div><h2>Kenali kelompok peserta</h2><p>Pendaftaran peserta berdasarkan kelas; usia tidak menjadi syarat.</p></div></div>
		<div class="cards categoryGrid">
			<?php foreach ($competitionGroups as $groupName => $groupCompetitions): ?>
				<?php if (stripos($groupName, 'umum') !== FALSE): continue; endif; ?>
				<?php $ages = array_values(array_unique(array_filter(array_column($groupCompetitions, 'age_label')))); ?>
				<div class="card ageCard"><span class="eyebrow"><?= e($groupName) ?></span><b><?= e(implode(' · ', $ages)) ?></b><p><?= e(implode(', ', array_column($groupCompetitions, 'name'))) ?></p></div>
			<?php endforeach; ?>
			<?php foreach ($competitionGroups as $groupName => $groupCompetitions): ?>
				<?php if (stripos($groupName, 'umum') === FALSE): continue; endif; ?>
				<?php $ages = array_values(array_unique(array_filter(array_column($groupCompetitions, 'age_label')))); ?>
				<div class="card ageCard"><span class="eyebrow">Kategori Umum</span><b><?= e(implode(' · ', $ages)) ?></b><p><?= e(implode(', ', array_column($groupCompetitions, 'name'))) ?></p></div>
			<?php endforeach; ?>
		</div>
	</div>
</section>

<section id="lomba" class="section">
	<div class="container">
		<div class="sectionHead"><div><div class="eyebrow"><?= count($competitions) ?> Cabang Lomba</div><h2>Semua lomba, satu tampilan.</h2><p>Klik <b>Lihat Juknis</b> untuk membaca aturan, peserta, lokasi, dan kriteria setiap cabang.</p></div></div>
		<?php foreach ($competitionGroups as $groupName => $groupCompetitions): ?>
			<div class="competitionGroup">
				<h3><?= e($groupName) ?></h3>
				<div class="cards competitionGrid">
					<?php foreach ($groupCompetitions as $c): ?>
						<div class="card competition">
							<div class="top"><?= comp_icon($c, 'compImg') ?><?= tag($c['submission_mode'] === 'online' ? 'Online' : 'Offline', $c['submission_mode'] === 'online' ? 'blue' : '') ?></div>
							<h3><?= e($c['name']) ?></h3>
							<div class="meta">👥 <?= e($c['type_label']) ?><br>⚥ Gender: <?= e($c['gender_label']) ?><br>🏫 <?= e($c['age_label']) ?><br>📍 <?= e($c['place']) ?><br>🏆 <?= e($winnersLabel($c)) ?></div>
							<div class="bottom"><?= tag($c['registration_open'] ? 'Pendaftaran dibuka' : 'Pendaftaran ditutup', $c['registration_open'] ? '' : 'yellow') ?><button type="button" class="btn soft" data-dialog-open="juknis-<?= (int) $c['id'] ?>">Lihat Juknis</button></div>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
		<?php endforeach; ?>
	</div>
</section>

<section id="alur" class="section flowSection">
	<div class="container">
		<div class="sectionHead"><div><div class="eyebrow">Alur</div><h2>Dari pendaftaran sampai pelaksanaan.</h2></div></div>
		<div class="cards g4">
			<div class="card"><div class="eyebrow">01</div><h3>Daftar</h3><p>Admin desa mendaftarkan peserta sesuai kelas. Lomba online dapat didaftarkan langsung tanpa login.</p></div>
			<div class="card"><div class="eyebrow">02</div><h3>Technical Meeting</h3><p>Urutan penampilan ditentukan oleh panitia.</p></div>
			<div class="card"><div class="eyebrow">03</div><h3>Hari H / Pre-Event</h3><p>Ikuti metode dan batas pengumpulan masing-masing lomba.</p></div>
			<div class="card"><div class="eyebrow">04</div><h3>Penjurian</h3><p>Nilai mengikuti kriteria yang tercantum pada juknis.</p></div>
		</div>
	</div>
</section>

<section id="jadwal" class="section alt">
	<div class="container">
		<div class="sectionHead"><div><div class="eyebrow">Waktu &amp; Tempat</div><h2>Catat jadwal utama.</h2></div></div>
		<div class="notice">
			<div class="noticeBox">
				<h2><?= e(trim($dayLabel.($dayLabel ? ', ' : '').$dateLabel)) ?></h2>
				<p>07.00 WIB – selesai<br><b><?= e($venue) ?></b><br><br>Tema acara: <b><?= e(!empty($event['theme']) ? $event['theme'] : 'akan diumumkan menyusul') ?></b>.</p>
			</div>
			<div class="schedule">
				<div><b>Pendaftaran</b><small><?= e($windowText) ?></small></div>
				<div><b>Pre-Event</b><small>Online • sesuai ketentuan lomba</small></div>
				<div><b>Technical Meeting</b><small>Urutan tampil ditentukan panitia</small></div>
				<div><b>Hari H</b><small><?= e($dateLabel) ?></small></div>
			</div>
		</div>
		<div class="sectionHead subSectionHead"><div><div class="eyebrow">Rangkaian Acara</div><h2>Rundown FGU 3.0</h2></div></div>
		<?php if ($rundown): ?><div class="tableWrap"><table class="compact"><thead><tr><th>Waktu</th><th>Detail Kegiatan</th><th>Lokasi</th></tr></thead><tbody><?php foreach ($rundown as $item): ?><tr><td><b><?= e($item['start_time']) ?></b></td><td><?= multiline($item['detail']) ?></td><td><?= e($item['location']) ?></td></tr><?php endforeach; ?></tbody></table></div><?php else: ?><div class="card"><p class="sub">Rundown akan diumumkan oleh panitia.</p></div><?php endif; ?>
	</div>
</section>

<section id="denah" class="section sitePlanSection">
	<div class="container">
		<div class="sectionHead"><div><div class="eyebrow">Lokasi Acara</div><h2>Denah Festival Generasi Unggul</h2><p>Gunakan denah berikut untuk menemukan area kegiatan dan lokasi lomba.</p></div></div>
		<?php if (!empty($event['site_plan_path'])): ?>
			<div class="sitePlanPublic"><a href="<?= e(site_url('denah')) ?>" target="_blank" rel="noopener noreferrer"><img src="<?= e(site_url('denah')) ?>" alt="Denah lokasi Festival Generasi Unggul" loading="lazy"></a></div>
		<?php else: ?>
			<div class="card"><p class="sub">Denah lokasi akan diumumkan oleh panitia.</p></div>
		<?php endif; ?>
	</div>
</section>

<section id="pengumpulan" class="section alt">
	<div class="container">
		<div class="sectionHead">
			<div><div class="eyebrow">Pengumpulan Karya</div><h2>Daftar &amp; Kumpulkan Karya Online</h2><p>Lomba online dan Mewarnai bisa didaftarkan tanpa login. Pilih lomba, pilih desa, isi data peserta, lalu kirim. Lomba Mewarnai boleh diisi banyak peserta sekaligus.</p></div>
		</div>
		<div class="notice">
			<div class="noticeBox">
				<h2>📹 Kirim Karya</h2>
				<p>Pastikan link Google Drive dapat diakses panitia dan akun Instagram tidak digembok. Data masuk ke rekap pendaftaran lomba yang bersangkutan.</p>
				<?php if ($open): ?>
					<button type="button" class="btn white" data-dialog-open="dlg-open">Daftar &amp; Kumpulkan Karya</button>
					<?php if ($online): ?><button type="button" class="btn ghost sm openSecondaryAction" data-dialog-open="dlg-karya">Kirim link karya online lainnya</button><?php endif; ?>
				<?php elseif ($online): ?>
					<button type="button" class="btn white" data-dialog-open="dlg-karya">Daftar &amp; Kumpulkan Karya</button>
				<?php else: ?>
					<p><b>Belum ada pendaftaran karya yang dibuka.</b></p>
				<?php endif; ?>
			</div>
			<div class="card">
				<h3>Alur Pendaftaran</h3>
				<div class="schedule">
					<div><b>01</b><small>Pilih lomba online</small></div>
					<div><b>02</b><small>Pilih desa</small></div>
					<div><b>03</b><small>Isi data peserta (Mewarnai boleh banyak sekaligus)</small></div>
					<div><b>04</b><small>Kirim — tanpa login</small></div>
				</div>
			</div>
		</div>
		<div class="cards openEntryGrid" id="open-entries">
			<?php foreach ($open as $c): $summary = $openSummary($c); ?>
				<div class="card openEntryCard">
					<div class="openEntryTitle"><span aria-hidden="true"><?= e($summary['icon']) ?></span><h3><?= e($c['name']) ?></h3></div>
					<p><b>Data:</b> <?= e($summary['fields']) ?></p>
					<?php if ($summary['deadline']): ?><p><b>Batas:</b> <?= e($summary['deadline']) ?></p><?php endif; ?>
					<?php if ($c['allow_team']): ?><p><b>Jenis:</b> Individu atau Tim</p><?php endif; ?>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
</section>

<section id="bazaar" class="section">
	<div class="container">
		<div class="sectionHead"><div><div class="eyebrow">Bazaar FGU</div><h2>Daftar Bazaar UMKM</h2><p>Pilih nomor stand yang masih tersedia. Pendaftaran tanpa login.</p></div></div>
		<div class="cards">
			<div class="card"><div class="icon">🛍️</div><h3>Bazaar UMKM</h3><p>Kuota <?= (int) ($event['umkm_booth_quota'] ?? 1) ?> stand. Nomor yang sudah terdaftar tidak dapat dipilih kembali.</p><?php if ($umkm_registration_open): ?><button class="btn primary" type="button" data-dialog-open="dlg-bazaar-umkm">Daftar Bazaar UMKM</button><?php else: ?><p class="sub">Pendaftaran Bazaar UMKM sedang ditutup.</p><button class="btn ghost" type="button" disabled>Pendaftaran ditutup</button><?php endif; ?></div>
		</div>
	</div>
</section>

<section id="pemenang" class="section">
	<div class="container">
		<div class="sectionHead"><div><div class="eyebrow">Hasil Resmi</div><h2>Pemenang</h2><p><?= $published_winners ? 'Berikut hasil lomba yang telah dipublikasikan oleh panitia.' : 'Pemenang akan diumumkan setelah penilaian selesai.' ?></p></div></div>
		<?php if ($published_winners): ?>
			<div class="cards">
				<?php foreach ($published_winners as $block): ?>
					<?php $isCerdasCermat = strtolower((string) $block['competition']['slug']) === 'cerdas-cermat'; ?>
					<div class="card">
						<h3><?= e($block['competition']['name']) ?></h3>
						<div class="tableWrap flush"><table class="compact">
							<thead><tr><th>Peringkat</th><th>Peserta</th><th>Desa</th><?php if ($isCerdasCermat): ?><th>Babak 1</th><th>Babak 2</th><th>Babak 3</th><th>Babak 4</th><?php endif; ?><th class="num">Nilai</th></tr></thead>
							<tbody><?php foreach ($block['results'] as $winner): ?>
								<tr class="winner"><td><b><?= e(medal((int) $winner['rank'])) ?></b></td><td><?= e($winner['label']) ?></td><td><?= e($winner['village_name']) ?></td><?php if ($isCerdasCermat): foreach ($winner['round_scores'] as $roundScore): ?><td><?= e(fmt_score($roundScore)) ?></td><?php endforeach; endif; ?><td class="num"><b><?= e(fmt_score($winner['final_score'])) ?></b></td></tr>
							<?php endforeach; ?></tbody>
						</table></div>
					</div>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div>
</section>

<?php if (trim((string) $site['announcement']) !== ''): ?>
<section id="pengumuman" class="section alt">
	<div class="container">
		<div class="sectionHead"><div><div class="eyebrow">Pengumuman</div><h2>Info resmi panitia</h2></div></div>
		<div class="card"><p class="announce"><?= multiline($site['announcement']) ?></p></div>
	</div>
</section>
<?php endif; ?>

<?php /* ---- Dialog juknis per lomba ---- */ ?>
<?php foreach ($competitions as $c): ?>
	<dialog id="juknis-<?= (int) $c['id'] ?>" class="modalDlg">
		<div class="modalHead"><h3><?= e($c['name']) ?></h3><button type="button" class="x" data-dialog-close aria-label="Tutup">×</button></div>
		<div class="card flat">
			<div class="formGrid">
				<div><b>Kategori</b><p><?= e($c['age_label']) ?></p></div>
				<div><b>Format</b><p><?= e($c['type_label']) ?><?= $c['minimum_participants'] !== NULL ? ' • '.(int) $c['minimum_participants'].'–'.(int) $c['quota'].' orang/desa' : ($c['quota'] !== null ? ' • '.(int) $c['quota'].' orang/desa' : '') ?></p></div>
				<div><b>Gender peserta</b><p><?= e($c['gender_label']) ?></p></div>
				<div><b>Tempat</b><p><?= e($c['place']) ?></p></div>
				<div><b>Metode</b><p><?= $c['submission_mode'] === 'online' ? 'Online' : 'Offline' ?></p></div>
			</div>
			<hr class="rule">
			<?php if ($c['rules']): ?>
				<b>Petunjuk teknis</b>
				<ul class="bullets"><?php foreach ($c['rules'] as $r): ?><li><?= e($r) ?></li><?php endforeach; ?></ul>
			<?php endif; ?>
			<b>Kriteria penilaian</b>
			<div class="checkGrid mt"><?php foreach ($c['criteria'] as $cr): ?><div class="check">✓ <?= e($cr['name']) ?><?php if ($c['scoring_method'] === 'weighted'): ?> <b><?= e(rtrim(rtrim(number_format((float) $cr['weight'], 2, '.', ''), '0'), '.')) ?>%</b><?php endif; ?></div><?php endforeach; ?></div>
		</div>
		<div class="modalFoot"><button type="button" class="btn ghost" data-dialog-close>Tutup</button></div>
	</dialog>
<?php endforeach; ?>

<?php if ($online): ?>
	<dialog id="dlg-karya" class="modalDlg">
		<form method="post" action="<?= e(site_url('karya')) ?>" class="fields">
			<?= csrf_field() ?>
			<div class="modalHead"><h3>Kumpulkan Karya Online</h3><button type="button" class="x" data-dialog-close aria-label="Tutup">×</button></div>
			<div class="card flat"><b>📹 Kirim Karya</b><p class="sub">Tanpa login. Pilih lomba online, isi nama peserta dan desa, lalu tempel link Google Drive yang dapat diakses panitia.</p></div>
			<div class="field"><label for="k-comp">Pilih Lomba *</label>
				<select id="k-comp" name="competition_id" required>
					<?php foreach ($online as $c): ?><option value="<?= (int) $c['id'] ?>" data-genders="<?= e(implode(',', $c['allowed_genders'])) ?>"><?= e($c['name']) ?></option><?php endforeach; ?>
				</select>
			</div>
			<div class="field"><label for="k-name">Nama Peserta *</label><input id="k-name" type="text" name="name" maxlength="100" required placeholder="Nama lengkap peserta / tim"></div>
			<div class="field"><label for="k-gender">Gender Peserta *</label><select id="k-gender" name="gender" data-work-gender required><option value="">Pilih gender…</option><option value="L">Putra</option><option value="P">Putri</option></select></div>
			<div class="field"><label for="k-village">Desa *</label>
				<select id="k-village" name="village_id" required>
					<option value="">Pilih desa…</option>
					<?php foreach ($villages as $v): ?><option value="<?= (int) $v['id'] ?>"><?= e($v['name']) ?></option><?php endforeach; ?>
				</select>
			</div>
			<div class="field"><label for="k-link">Link Google Drive *</label><input id="k-link" type="url" name="link" maxlength="500" required placeholder="https://drive.google.com/..."><small class="sub">Pastikan akses dibuka untuk siapa saja yang memiliki link.</small></div>
			<div class="modalFoot"><button type="button" class="btn ghost" data-dialog-close>Batal</button><button type="submit" class="btn primary" data-pending-text="Mengirim…">Kirim Karya</button></div>
		</form>
	</dialog>
<?php endif; ?>

<?php if ($umkm_registration_open): ?>
<dialog id="dlg-bazaar-umkm" class="modalDlg">
	<form method="post" action="<?= e(site_url('bazaar/umkm')) ?>" class="fields">
		<?= csrf_field() ?><input type="text" name="website" tabindex="-1" autocomplete="off" class="honeypot" aria-hidden="true">
		<div class="modalHead"><h3>Pendaftaran Bazaar UMKM</h3><button type="button" class="x" data-dialog-close aria-label="Tutup">×</button></div>
		<div class="field"><label>Nama pemilik / PJ brand *</label><input name="owner_name" type="text" maxlength="120" required></div>
		<div class="field"><label>Nama brand *</label><input name="brand_name" type="text" maxlength="120" required></div>
		<div class="field"><label>Desa *</label><select name="village_id" required><option value="">Pilih desa…</option><?php foreach ($villages as $v): ?><option value="<?= (int) $v['id'] ?>"><?= e($v['name']) ?></option><?php endforeach; ?></select></div>
		<div class="field"><label>Nomor HP *</label><input name="phone" type="tel" maxlength="30" required></div>
		<div class="field"><label>Nomor stand *</label><select name="booth_number" required><option value="">Pilih nomor…</option><?php for ($booth = 1; $booth <= (int) ($event['umkm_booth_quota'] ?? 1); $booth++): ?><option value="<?= $booth ?>"<?= in_array($booth, $umkm_booths, TRUE) ? ' disabled' : '' ?>><?= $booth ?><?= in_array($booth, $umkm_booths, TRUE) ? ' — sudah dipilih' : '' ?></option><?php endfor; ?></select></div>
		<div class="modalFoot"><button class="btn ghost" type="button" data-dialog-close>Batal</button><button class="btn primary" type="submit">Kirim Pendaftaran</button></div>
	</form>
</dialog>
<?php endif; ?>
<?php if ($open): ?>
	<dialog id="dlg-open" class="modalDlg registrationDlg">
		<div class="modalHead"><h3>Pendaftaran &amp; Pengumpulan Karya Online</h3><button type="button" class="x" data-dialog-close aria-label="Tutup">×</button></div>
		<div class="alert info registrationIntro"><b>📹 Tanpa login.</b> Pilih lomba, pilih desa, isi data peserta, lalu kirim (Lomba Mewarnai boleh banyak peserta sekaligus).</div>
		<form method="post" action="<?= e(site_url('karya')) ?>" class="fields">
			<?= csrf_field() ?>
			<input type="hidden" name="registration_mode" value="open">
			<input type="text" name="website" tabindex="-1" autocomplete="off" class="honeypot" aria-hidden="true">
			<p class="requiredHint"><span class="requiredMark">*</span> wajib diisi</p>
			<div class="field"><label for="open-comp">Pilih Lomba <span class="requiredMark">*</span></label>
				<select id="open-comp" name="competition_id" data-open-competition required>
					<?php foreach ($open as $index => $c): ?><option value="<?= (int) $c['id'] ?>"<?= $index === 0 ? ' selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach; ?>
				</select>
			</div>
			<div class="field"><label for="open-village">Desa <span class="requiredMark">*</span></label>
				<select id="open-village" name="village_id" required><option value="">Pilih desa…</option><?php foreach ($villages as $v): ?><option value="<?= (int) $v['id'] ?>"><?= e($v['name']) ?></option><?php endforeach; ?></select>
			</div>
			<?php foreach ($open as $index => $c): $rowKey = 'open-'.(int) $c['id']; $multi = stripos($c['name'], 'mewarna') !== FALSE; ?>
				<div class="openCompetitionFields" data-open-fields="<?= (int) $c['id'] ?>"<?= $index !== 0 ? ' hidden' : '' ?>>
					<?php if ($multi): ?><p class="fieldHint">Bisa mengisi <b>banyak peserta sekaligus</b>: klik “+ Tambah peserta” untuk baris baru.</p><?php endif; ?>
					<div data-rows="<?= e($rowKey) ?>" data-max="<?= $multi ? 30 : 1 ?>" class="participantRows"><?= $openRow($c) ?></div>
					<template data-row-template="<?= e($rowKey) ?>"><?= $openRow($c) ?></template>
					<?php if ($multi): ?>
						<div class="participantActions">
							<button type="button" class="btn soft sm" data-row-add="<?= e($rowKey) ?>">+ Tambah peserta</button>
							<span class="muted small"><span data-row-count="<?= e($rowKey) ?>">1</span> / 30</span>
						</div>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>
			<div class="modalFoot"><button type="button" class="btn ghost" data-dialog-close>Tutup</button><button type="submit" class="btn primary" data-pending-text="Mengirim…">Kirim Pendaftaran</button></div>
		</form>
	</dialog>
<?php endif; ?>
