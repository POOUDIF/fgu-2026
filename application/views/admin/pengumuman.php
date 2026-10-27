<?php /** @var array $site */ ?>
<?= page_head('Pengumuman', 'Informasi yang tampil pada halaman depan.') ?>
<div class="card sectionCard">
	<form method="post" action="<?= e(site_url('admin/pengumuman')) ?>" class="fields">
		<?= csrf_field() ?>
		<div class="field"><label for="a-text">Isi pengumuman</label><textarea id="a-text" name="announcement" rows="8" maxlength="5000" required><?= e(old('announcement', $site['announcement'])) ?></textarea></div>
		<div><button class="btn primary" type="submit" data-pending-text="Menyimpan…">Publikasikan</button></div>
	</form>
</div>
