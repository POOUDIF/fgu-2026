<?php /** @var array|null $event; @var array $site; @var string|null $registration_status */
use App\Support\Format;
use App\Domain\RegistrationWindow;
?>
<?= page_head('Pengaturan', 'Atur jadwal pendaftaran, konten beranda, serta data operasional festival.') ?>

<div class="card sectionCard">
	<h2>Acara &amp; jadwal pendaftaran</h2>
	<?php if ($event): ?>
		<?php
		$statusLabel = array(
			RegistrationWindow::OPEN => 'Dibuka',
			RegistrationWindow::NOT_OPEN => 'Belum dibuka',
			RegistrationWindow::CLOSED => 'Ditutup',
		)[$registration_status] ?? 'Tidak diketahui';
		$statusStyle = $registration_status === RegistrationWindow::OPEN ? 'ok' : 'err';
		?>
		<div class="alert <?= e($statusStyle) ?>">Status pendaftaran saat ini: <b><?= e($statusLabel) ?></b> · <?= $event['registration_opens_at'] ? 'Dibuka '.e(fmt_datetime($event['registration_opens_at'])) : 'Terbuka tanpa jadwal mulai' ?><?= $event['registration_closes_at'] ? ' sampai '.e(fmt_datetime($event['registration_closes_at'])) : ' tanpa batas waktu tutup' ?></div>
	<?php else: ?>
		<div class="alert err">Data acara belum tersedia. Jalankan seed acara terlebih dahulu.</div>
	<?php endif; ?>
	<?php if ($event && ($portal['label'] ?? '') === 'Super Admin'): ?>
		<p class="sub">Waktu dalam WIB. Kosongkan jadwal pendaftaran bila tidak dibatasi.</p>
		<form method="post" action="<?= e(site_url('admin/homepage/acara')) ?>" class="fields">
			<?= csrf_field() ?>
			<div class="formGrid">
				<div class="field"><label for="e-name">Nama acara</label><input id="e-name" type="text" name="name" required maxlength="150" value="<?= e(old('name', $event['name'])) ?>"></div>
				<div class="field"><label for="e-date">Tanggal acara</label><input id="e-date" type="date" name="event_date" value="<?= e(old('event_date', $event['event_date'])) ?>"></div>
				<div class="field"><label for="e-venue">Tempat</label><input id="e-venue" type="text" name="venue" maxlength="190" value="<?= e(old('venue', $event['venue'])) ?>"></div>
				<div class="field"><label for="e-theme">Tema acara</label><input id="e-theme" type="text" name="theme" maxlength="190" value="<?= e(old('theme', $event['theme'])) ?>" placeholder="Kosong = akan diumumkan menyusul"></div>
				<div class="field"><label for="e-open">Pendaftaran dibuka</label><input id="e-open" type="datetime-local" name="registration_opens_at" value="<?= e(old('registration_opens_at', Format::toLocalInput($event['registration_opens_at'] ?? null))) ?>"></div>
				<div class="field"><label for="e-close">Pendaftaran ditutup</label><input id="e-close" type="datetime-local" name="registration_closes_at" value="<?= e(old('registration_closes_at', Format::toLocalInput($event['registration_closes_at'] ?? null))) ?>"></div>
			</div>
			<div><button class="btn primary" type="submit" data-pending-text="Menyimpan…">Simpan Data Acara</button></div>
		</form>
	<?php elseif ($event): ?>
		<p class="sub">Pengubahan data acara dan jadwal pendaftaran hanya tersedia untuk Super Admin.</p>
	<?php endif; ?>
</div>

<?php if ($event && ($portal['label'] ?? '') === 'Super Admin'): ?>
<div class="card sectionCard">
	<h2>Denah &amp; kuota Bazaar UMKM</h2>
	<p class="sub">Unggah denah JPG, PNG, atau WebP (maks. 10 MB). Denah akan tampil besar di beranda publik.</p>
	<?php if (!empty($event['site_plan_path'])): ?><div class="sitePlanPreview"><img src="<?= e(site_url('denah')) ?>" alt="Denah saat ini"></div><?php endif; ?>
	<form method="post" enctype="multipart/form-data" action="<?= e(site_url('admin/homepage/denah')) ?>" class="fields">
		<?= csrf_field() ?>
		<div class="formGrid">
			<div class="field"><label for="site-plan">Ganti / unggah denah</label><input id="site-plan" type="file" name="site_plan" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"></div>
			<div class="field"><label for="umkm-quota">Kuota booth UMKM</label><input id="umkm-quota" type="number" name="umkm_booth_quota" min="1" max="500" required value="<?= (int) $event['umkm_booth_quota'] ?>"></div>
		</div>
		<button class="btn primary" type="submit">Simpan Denah &amp; Kuota</button>
	</form>
</div>
<div class="card sectionCard">
	<h2>Rundown acara</h2><p class="sub">Isi waktu, detail kegiatan, dan lokasi. Baris kosong akan diabaikan; urutannya mengikuti form.</p>
	<form method="post" action="<?= e(site_url('admin/homepage/rundown')) ?>" class="fields">
		<?= csrf_field() ?>
		<?php for ($i = 0; $i < max(8, count($rundown)); $i++): $item = $rundown[$i] ?? array('start_time' => '', 'detail' => '', 'location' => ''); ?>
			<div class="formGrid rundownAdminRow">
				<div class="field"><label>Waktu</label><input type="text" name="start_time[]" maxlength="30" value="<?= e($item['start_time']) ?>" placeholder="07.00 WIB"></div>
				<div class="field"><label>Detail kegiatan</label><input type="text" name="detail[]" maxlength="2000" value="<?= e($item['detail']) ?>"></div>
				<div class="field"><label>Lokasi</label><input type="text" name="location[]" maxlength="190" value="<?= e($item['location']) ?>"></div>
			</div>
		<?php endfor; ?>
		<button class="btn primary" type="submit">Simpan Rundown</button>
	</form>
</div>
<?php endif; ?>

<div class="card sectionCard">
	<h2>Konten beranda</h2>
	<p class="sub">Isian yang dikosongkan tidak menghapus konten yang sudah ada.</p>
	<form method="post" action="<?= e(site_url('admin/homepage/konten')) ?>" class="fields">
		<?= csrf_field() ?>
		<div class="field"><label for="h-tag">Tagline</label><input id="h-tag" type="text" name="tagline" maxlength="5000" value="<?= e(old('tagline', $site['tagline'])) ?>"></div>
		<div class="field"><label for="h-about">Tentang FGU</label><textarea id="h-about" name="about" rows="6" maxlength="5000"><?= e(old('about', $site['about'])) ?></textarea></div>
		<div><button class="btn primary" type="submit" data-pending-text="Menyimpan…">Simpan Perubahan</button></div>
	</form>
</div>

<div class="card sectionCard">
	<h3>Kelola data operasional</h3>
	<p class="sub">Pengelolaan desa, cabang lomba, alur pendaftaran, dan pengumuman.</p>
	<div class="adminSettingsLinks">
		<a class="btn soft" href="<?= e(site_url('admin/desa')) ?>">Kelola desa</a>
		<a class="btn soft" href="<?= e(site_url('admin/lomba')) ?>">Kelola lomba &amp; kriteria</a>
		<a class="btn soft" href="<?= e(site_url('admin/kontrol-lomba')) ?>">Kontrol pendaftaran lomba</a>
		<a class="btn soft" href="<?= e(site_url('admin/kontrol-nilai')) ?>">Kontrol penilaian</a>
		<a class="btn soft" href="<?= e(site_url('admin/pengumuman')) ?>">Kelola pengumuman</a>
		<a class="btn soft" href="<?= e(site_url('admin/pemenang')) ?>">Daftar pemenang &amp; penalti</a>
	</div>
</div>
