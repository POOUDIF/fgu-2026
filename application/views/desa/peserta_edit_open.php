<?php
/** @var array<string,mixed> $entry; @var array<string,mixed> $competition; @var bool $can_edit */
$fields = $competition['form_fields'];
?>
<?= page_head('Ubah Data Peserta', 'Perbarui data pendaftaran '.$competition['name'].'.', '<a class="btn ghost" href="'.e(site_url('desa/peserta')).'">Kembali ke data peserta</a>') ?>

<?php if (!$can_edit): ?>
	<div class="alert info">Pendaftaran tidak dapat diubah karena sudah ditutup, peserta sudah dinilai, atau data tidak lagi berstatus terdaftar.</div>
<?php else: ?>
	<div class="alert info">Perubahan hanya berlaku untuk peserta dari desa Anda. Ketentuan gender dan kolom wajib lomba tetap divalidasi saat disimpan.</div>
<?php endif; ?>

<form method="post" action="<?= e(site_url('desa/peserta/'.(int) $entry['id'].'/ubah')) ?>" class="card formStack">
	<?= csrf_field() ?>
	<div class="field">
		<label><?= $competition['allow_team'] ? 'Nama Peserta / Tim' : 'Nama Peserta' ?></label>
		<input type="text" name="name" maxlength="100" required value="<?= e($entry['team_name']) ?>" <?= !$can_edit ? 'disabled' : '' ?>>
	</div>
	<div class="field">
		<label>Gender peserta</label>
		<select name="gender" required <?= !$can_edit ? 'disabled' : '' ?>>
			<option value="">Pilih gender…</option>
			<?php if ($competition['allowed_genders'] === array() || in_array('L', $competition['allowed_genders'], TRUE)): ?><option value="L" <?= $entry['gender'] === 'L' ? 'selected' : '' ?>>Putra</option><?php endif; ?>
			<?php if ($competition['allowed_genders'] === array() || in_array('P', $competition['allowed_genders'], TRUE)): ?><option value="P" <?= $entry['gender'] === 'P' ? 'selected' : '' ?>>Putri</option><?php endif; ?>
		</select>
	</div>
	<?php if ($competition['allow_team']): ?>
		<div class="field">
			<label>Jenis pendaftaran</label>
			<select name="entry_type" required <?= !$can_edit ? 'disabled' : '' ?>>
				<option value="individual" <?= $entry['entry_type'] === 'individual' ? 'selected' : '' ?>>Individu</option>
				<option value="team" <?= $entry['entry_type'] === 'team' ? 'selected' : '' ?>>Tim</option>
			</select>
		</div>
	<?php endif; ?>
	<?php if (in_array('parent_name', $fields, TRUE)): ?>
		<div class="field">
			<label>Nama Orang Tua</label>
			<input type="text" name="parent_name" maxlength="100" required value="<?= e($entry['parent_name'] ?? '') ?>" <?= !$can_edit ? 'disabled' : '' ?>>
		</div>
	<?php endif; ?>
	<?php if (in_array('ig_username', $fields, TRUE)): ?>
		<div class="field">
			<label>Username Instagram</label>
			<input type="text" name="ig_username" maxlength="30" required value="<?= e($entry['ig_username'] ?? '') ?>" <?= !$can_edit ? 'disabled' : '' ?>>
		</div>
	<?php endif; ?>
	<?php if (in_array('submission_url', $fields, TRUE)): ?>
		<div class="field">
			<label>Link Karya</label>
			<input type="url" name="submission_url" maxlength="500" required value="<?= e($entry['submission_url'] ?? '') ?>" <?= !$can_edit ? 'disabled' : '' ?>>
		</div>
	<?php endif; ?>
	<?php if ($competition['allow_team']): ?>
		<div class="field">
			<label>Daftar Anggota (opsional)</label>
			<textarea name="members_note" maxlength="500" rows="3" <?= !$can_edit ? 'disabled' : '' ?>><?= e($entry['members_note'] ?? '') ?></textarea>
		</div>
	<?php endif; ?>
	<div class="flex">
		<a class="btn ghost" href="<?= e(site_url('desa/peserta')) ?>">Batal</a>
		<?php if ($can_edit): ?><button type="submit" class="btn primary" data-pending-text="Menyimpan…">Simpan Perubahan</button><?php endif; ?>
	</div>
</form>
