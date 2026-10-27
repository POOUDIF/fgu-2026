<?php
/** @var array $village; @var list $competitions; @var array $registered [competition_id] => list peserta; @var bool $registration_open */
$participantRow = static function (array $r, bool $online, array $levels, array $allowedGenders): string {
	ob_start(); ?>
	<div class="participantRow card flat">
		<b class="rowLabel"></b>
		<div class="formGrid">
			<div class="field"><label>Nama Peserta</label><input type="text" name="name[]" maxlength="100" value="<?= e($r['name'] ?? '') ?>" placeholder="Nama lengkap"></div>
			<div class="field"><label>Nama Orang Tua</label><input type="text" name="parent_name[]" maxlength="100" required value="<?= e($r['parent_name'] ?? '') ?>"></div>
			<div class="field"><label>Gender peserta</label><select name="gender[]" required><option value="">Pilih gender…</option><?php if ($allowedGenders === array() || in_array('L', $allowedGenders, true)): ?><option value="L" <?= ($r['gender'] ?? '') === 'L' ? 'selected' : '' ?>>Putra</option><?php endif; ?><?php if ($allowedGenders === array() || in_array('P', $allowedGenders, true)): ?><option value="P" <?= ($r['gender'] ?? '') === 'P' ? 'selected' : '' ?>>Putri</option><?php endif; ?></select></div>
			<div class="field"><label>Jenjang</label><select name="education_level[]" required><option value="">Pilih…</option><?php foreach ($levels as $level): ?><option value="<?= e($level) ?>" <?= ($r['education_level'] ?? '') === $level ? 'selected' : '' ?>><?= e($level) ?></option><?php endforeach; ?></select></div>
			<div class="field"><label>Kelas (SD/SMP/SMA)</label><input type="number" name="grade[]" min="1" max="12" inputmode="numeric" value="<?= e($r['grade'] ?? '') ?>"></div>
			<div class="field full">
				<label>Foto peserta <span class="requiredMark">*</span> (maks. 2 MB)</label>
				<?php if (!empty($r['photo_path']) && !empty($r['id'])): ?><img src="<?= e(site_url('desa/peserta/'.(int) $r['id'].'/foto')) ?>" alt="<?= e('Foto '.$r['name']) ?>" width="56" height="56" style="object-fit:cover;border-radius:8px;margin-bottom:8px"><?php endif; ?>
				<input type="file" name="photo[]" data-has-photo="<?= !empty($r['photo_path']) ? '1' : '0' ?>"<?= !empty($r['name']) && empty($r['photo_path']) ? ' required' : '' ?> accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
				<small class="sub"><?= !empty($r['photo_path']) ? 'Foto yang sudah ada tetap digunakan jika tidak memilih pengganti.' : 'Wajib untuk peserta baru. Format JPG, PNG, atau WebP.' ?></small>
			</div>
			<?php if ($online): ?><div class="field full"><label>Link Karya</label><input type="url" name="link[]" maxlength="500" value="<?= e($r['link'] ?? '') ?>" placeholder="https://..."></div><?php endif; ?>
		</div>
	</div>
	<?php return (string) ob_get_clean();
};
$openRow = static function (array $fields, bool $allowTeam, array $allowedGenders): string {
	ob_start(); ?>
	<div class="participantRow card flat dynamicRow">
		<div class="formGrid">
			<?php if ($allowTeam): ?><div class="field"><label>Jenis</label><select name="entry_type[]"><option value="individual">Individu</option><option value="team">Tim</option></select></div><?php endif; ?>
			<div class="field"><label>Nama <?= $allowTeam ? 'Peserta / Tim' : 'Peserta' ?></label><input type="text" name="name[]" maxlength="100" required placeholder="Nama lengkap"></div>
			<div class="field"><label>Gender peserta</label><select name="gender[]" required><option value="">Pilih gender…</option><?php if ($allowedGenders === array() || in_array('L', $allowedGenders, true)): ?><option value="L">Putra</option><?php endif; ?><?php if ($allowedGenders === array() || in_array('P', $allowedGenders, true)): ?><option value="P">Putri</option><?php endif; ?></select></div>
			<?php if (in_array('parent_name', $fields, true)): ?><div class="field"><label>Nama Orang Tua</label><input type="text" name="parent_name[]" maxlength="100" required></div><?php endif; ?>
			<?php if (in_array('ig_username', $fields, true)): ?><div class="field"><label>Username Instagram</label><input type="text" name="ig_username[]" maxlength="31" required placeholder="@nama.akun"></div><?php endif; ?>
			<?php if (in_array('submission_url', $fields, true)): ?><div class="field full"><label>Link Karya</label><input type="url" name="submission_url[]" maxlength="500" required placeholder="https://..."></div><?php endif; ?>
			<?php if ($allowTeam): ?><div class="field full"><label>Daftar Anggota (opsional)</label><textarea name="members_note[]" maxlength="500" rows="2"></textarea></div><?php endif; ?>
		</div>
		<button type="button" class="btn ghost sm" data-row-remove>Hapus baris</button>
	</div>
	<?php return (string) ob_get_clean();
};
$competitionGroups = array();
foreach ($competitions as $competition)
{
	$groupName = trim((string) ($competition['cluster'] ?? '')) ?: 'Lomba';
	$competitionGroups[$groupName][] = $competition;
}
?>
<?= page_head('Pendaftaran Peserta', 'Pilih lomba dan kelola peserta sesuai kuota serta ketentuan.', tag('Desa: '.$village['name'], 'blue')) ?>

<?php if (!$registration_open): ?>
	<div class="alert info">Pendaftaran sedang tidak dibuka. Anda tetap dapat melihat peserta yang sudah terdaftar.</div>
<?php endif; ?>

<?php foreach ($competitionGroups as $groupName => $groupCompetitions): ?>
	<section class="registrationGroup">
		<h2 class="lomba-cluster-title"><?= e($groupName) ?></h2>
		<div class="cards g3">
			<?php foreach ($groupCompetitions as $c):
				$existing = $registered[$c['id']] ?? array();
				$n = count($existing);
				$isVillageBazaar = $c['slug'] === 'bazaar-desa';
				$canRegister = $c['registration_open'] && ($isVillageBazaar || $registration_open);
			?>
				<div class="card competition">
					<div class="top"><?= comp_icon($c, 'compImg') ?><?= tag($n.($c['quota'] !== null ? '/'.$c['quota'] : '').' terisi', $n ? '' : 'yellow') ?></div>
					<h3><?= e($c['name']) ?></h3>
					<div class="meta"><?= e($c['age_label']) ?> • <?= e($c['type_label']) ?><br>⚥ Gender: <b><?= e($c['gender_label']) ?></b><br><?= $c['registration_mode'] === 'open' ? 'Pendaftaran langsung: <b>'.(int) $n.' entry</b>' : 'Kuota <b>'.e(quota_label($c['quota'])).'/desa</b>' ?><br><?= $c['submission_mode'] === 'online' ? '📤 Karya via link' : '📍 '.e($c['place']) ?></div>
					<div><?= tag($canRegister ? 'Pendaftaran dibuka' : 'Pendaftaran ditutup', $canRegister ? '' : 'yellow') ?></div>
					<div class="bottom">
						<span></span>
						<?php if ($isVillageBazaar): ?>
							<a class="btn <?= $canRegister ? 'primary' : 'ghost' ?>" href="<?= e(site_url('desa/bazaar')) ?>"><?= $n ? 'Lihat / Kelola' : 'Form Bazaar Desa' ?></a>
						<?php else: ?>
							<button type="button" class="btn <?= $canRegister ? 'primary' : 'ghost' ?>" data-dialog-open="reg-<?= (int) $c['id'] ?>"<?= $canRegister ? '' : ' disabled' ?>><?= $canRegister ? ($n ? 'Tambah / Lihat' : 'Daftar') : 'Pendaftaran ditutup' ?></button>
						<?php endif; ?>
					</div>
				</div>
			<?php endforeach; ?>
			</div>
	</section>
<?php endforeach; ?>

<?php foreach ($competitions as $c):
	if ($c['slug'] === 'bazaar-desa')
	{
		continue;
	}
	$existing = $registered[$c['id']] ?? array();
	$isOpen = $c['registration_mode'] === 'open';
	$online = $c['submission_mode'] === 'online';
	$initialRows = max(1, (int) ($c['minimum_participants'] ?? 1));
	$rows = $isOpen ? 1 : max(count($existing), min($initialRows, $c['quota'] ?? 20, 20));
	$levels = $c['levels'] ?: array('PAUD', 'TK', 'SD', 'SMP', 'SMA', 'PGM');
?>
	<dialog id="reg-<?= (int) $c['id'] ?>" class="modalDlg">
		<form method="post" enctype="multipart/form-data" action="<?= e(site_url('desa/daftar/'.$c['slug'])) ?>"<?= !$isOpen && $c['minimum_participants'] !== NULL ? ' data-min-participants="'.(int) $c['minimum_participants'].'"' : '' ?>>
			<?= csrf_field() ?>
			<?php if ($isOpen): ?><input type="hidden" name="registration_mode" value="open"><?php endif; ?>
			<div class="modalHead"><h3>Pendaftaran — <?= e($c['name']) ?></h3><button type="button" class="x" data-dialog-close aria-label="Tutup">×</button></div>
			<?php if ($isOpen): ?>
			<p class="sub">Pendaftaran langsung atas nama peserta<?= $c['allow_team'] ? ' atau tim' : '' ?>. Gender yang diperbolehkan: <b><?= e($c['gender_label']) ?></b>. Setiap kiriman menambahkan entry baru dan tidak mengubah pendaftaran sebelumnya.</p>
			<div data-rows="open"<?= stripos($c['name'], 'mewarna') !== FALSE ? ' data-max="30"' : ' data-max="1"' ?>><?= $openRow($c['form_fields'], $c['allow_team'], $c['allowed_genders']) ?></div>
			<template data-row-template="open"><?= $openRow($c['form_fields'], $c['allow_team'], $c['allowed_genders']) ?></template>
			<?php if (stripos($c['name'], 'mewarna') !== FALSE): ?><button type="button" class="btn soft" data-row-add="open">+ Tambah Peserta</button><?php endif; ?>
			<?php else: ?>
			<p class="sub">
				<?= $c['minimum_participants'] !== NULL ? 'Jumlah peserta <b>'.(int) $c['minimum_participants'].'–'.(int) $c['quota'].' orang/desa</b>.' : ($c['quota'] !== null ? 'Kuota maksimal <b>'.(int) $c['quota'].' orang/desa</b>. Boleh kurang, tidak boleh lebih.' : 'Jumlah peserta mengikuti ketentuan juknis; tidak ada batas yang dicantumkan.') ?>
				Jenis: <b><?= e($c['type_label']) ?></b> • <?= e($c['age_label']) ?>. Gender: <b><?= e($c['gender_label']) ?></b>.
				<?= $c['is_team'] ? 'Seluruh peserta desa pada lomba ini dinilai sebagai <b>satu regu</b>.' : 'Setiap peserta dinilai sendiri.' ?>
				Kosongkan nama untuk menghapus baris. Satu peserta tidak boleh mengikuti dua lomba berbeda.
			</p>
			<div data-rows="participants"<?= $c['quota'] !== null ? ' data-max="'.(int) $c['quota'].'"' : '' ?>>
				<?php for ($i = 0; $i < $rows; $i++): ?><?= $participantRow($existing[$i] ?? array(), $online, $levels, $c['allowed_genders']) ?><?php endfor; ?>
			</div>
				<template data-row-template="participants"><?= $participantRow(array(), $online, $levels, $c['allowed_genders']) ?></template>
			<?php if ($c['quota'] === null || $rows < $c['quota']): ?>
				<button type="button" class="btn soft" data-row-add="participants">+ Tambah Peserta</button>
			<?php endif; ?>
			<?php endif; ?>
			<div class="modalFoot">
				<button type="button" class="btn ghost" data-dialog-close>Batal</button>
				<?php if ($registration_open): ?><button type="submit" class="btn primary" data-pending-text="Menyimpan…">Simpan Pendaftaran</button><?php endif; ?>
			</div>
		</form>
	</dialog>
<?php endforeach; ?>
