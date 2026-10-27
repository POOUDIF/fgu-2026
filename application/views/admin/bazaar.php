<?php /** @var array{umkm:list<array>,village:list<array>} $bazaar; @var array|null $village_competition; @var bool $is_super */ ?>
<?= page_head('Bazaar', 'Data pendaftaran Bazaar UMKM dan Bazaar Desa.') ?>
<div class="card sectionCard">
	<div class="between"><h2>Bazaar UMKM</h2><?= tag($umkm_registration_open ? 'Pendaftaran dibuka' : 'Pendaftaran ditutup', $umkm_registration_open ? '' : 'yellow') ?></div>
	<?php if ($is_super): ?>
		<form method="post" action="<?= e(site_url('admin/bazaar/umkm/pendaftaran')) ?>" class="row wrap mt">
			<?= csrf_field() ?><input type="hidden" name="open" value="<?= $umkm_registration_open ? '0' : '1' ?>">
			<button type="submit" class="btn <?= $umkm_registration_open ? 'ghost' : 'primary' ?>"><?= $umkm_registration_open ? 'Tutup pendaftaran UMKM' : 'Buka pendaftaran UMKM' ?></button>
		</form>
	<?php endif; ?>
	<?php if ($bazaar['umkm']): ?><div class="tableWrap"><table class="compact"><thead><tr><th>No. stand</th><th>Pemilik / PJ brand</th><th>Nama brand</th><th>Desa</th><th>Telepon</th><th>Waktu daftar</th></tr></thead><tbody><?php foreach ($bazaar['umkm'] as $row): ?><tr><td><b><?= (int) $row['booth_number'] ?></b></td><td><?= e($row['owner_name']) ?></td><td><?= e($row['brand_name']) ?></td><td><?= e($row['village_name']) ?></td><td><?= e($row['phone']) ?></td><td><?= e(fmt_datetime($row['created_at'])) ?></td></tr><?php endforeach; ?></tbody></table></div><?php else: ?><p class="sub">Belum ada pendaftar Bazaar UMKM.</p><?php endif; ?>
</div>
<div class="card sectionCard">
	<div class="between"><h2>Pengaturan penilaian Bazaar Desa</h2><?= $village_competition ? tag($village_competition['registration_open'] ? 'Pendaftaran dibuka' : 'Pendaftaran ditutup', $village_competition['registration_open'] ? '' : 'yellow') : tag('Belum dikonfigurasi', 'yellow') ?></div>
	<?php if ($village_competition): ?>
		<p class="sub">Juri: <?= $village_competition['judges'] ? e(implode(', ', array_column($village_competition['judges'], 'full_name'))) : 'belum ditugaskan' ?> · Kriteria: <?= $village_competition['criteria'] ? e(implode(', ', array_column($village_competition['criteria'], 'name'))) : 'belum diatur' ?>.</p>
	<?php endif; ?>
	<p class="sub">Form penilaian dan pengaturan juri/kriteria tersedia sebagai lomba Bazaar Desa.</p>
	<a class="btn soft" href="<?= e(site_url('admin/lomba')) ?>"><?= $is_super ? 'Atur juri, kriteria & buka/tutup pendaftaran' : 'Lihat pengaturan Bazaar Desa' ?></a>
</div>
<div class="card sectionCard"><h2>Bazaar Desa</h2>
	<?php if ($bazaar['village']): ?><div class="tableWrap"><table class="compact"><thead><tr><th>No. stand</th><th>Desa</th><th>Tema bazaar</th><th>Telepon</th><th>Waktu daftar</th></tr></thead><tbody><?php foreach ($bazaar['village'] as $row): ?><tr><td><b><?= (int) $row['booth_number'] ?></b></td><td><?= e($row['village_name']) ?></td><td><?= e($row['theme']) ?></td><td><?= e($row['phone']) ?></td><td><?= e(fmt_datetime($row['created_at'])) ?></td></tr><?php endforeach; ?></tbody></table></div><?php else: ?><p class="sub">Belum ada pendaftar Bazaar Desa.</p><?php endif; ?>
</div>
