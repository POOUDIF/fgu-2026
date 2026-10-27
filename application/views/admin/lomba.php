<?php /** @var list $competitions, $judges, $icons; @var bool $is_super */ ?>
<?= page_head('Daftar Lomba', 'Kelola lomba, status pendaftaran, kriteria, dan juri. Pengaturan buka/tutup serta penugasan juri hanya untuk Super Admin.', '<button type="button" class="btn primary" data-dialog-open="dlg-comp" data-fill="'.json_attr(new stdClass()).'">+ Tambah Lomba</button>') ?>

<div class="tableWrap">
	<table>
		<thead><tr><th>No</th><th>Mata Lomba</th><th>Usia</th><th>Jenis</th><th>Gender</th><th>Kuota/Desa</th><th>Kriteria</th><th>Pengumpulan</th><th>Pendaftaran</th><th>Juri</th><th>Aksi</th></tr></thead>
		<tbody>
		<?php foreach ($competitions as $i => $c):
			$payload = array(
				'id' => $c['id'], 'name' => $c['name'], 'cluster' => $c['cluster'], 'age_label' => $c['age_label'], 'type_label' => $c['type_label'],
				'participation_type' => $c['participation_type'], 'quota' => $c['quota'], 'minimum_participants' => $c['minimum_participants'], 'submission_mode' => $c['submission_mode'], 'place' => $c['place'],
				'icon' => $c['icon'], 'winner_count' => $c['winner_count'], 'rules' => implode("\n", $c['rules']),
				'gender_option' => $c['gender_option'],
				'criteria' => array_column($c['criteria'], 'name'), 'judges' => array_column($c['judges'], 'id'),
			);
		?>
			<tr>
				<td><?= $i + 1 ?></td>
				<td><div class="withIcon"><?= comp_icon($c, 'compImg xs') ?><div><b><?= e($c['name']) ?></b><br><small><?= e($c['cluster']) ?><?= $c['slug'] === 'bazaar-desa' ? ' · Form khusus Bazaar Desa' : '' ?></small></div></div></td>
				<td><?= e($c['age_label']) ?></td>
				<td><?= e($c['type_label']) ?><br><small><?= $c['is_team'] ? 'Dinilai per regu' : 'Dinilai per peserta' ?></small></td>
				<td><?= e($c['gender_label']) ?></td>
				<td><b><?= $c['minimum_participants'] !== NULL ? e($c['minimum_participants'].'–'.$c['quota'].' orang/desa') : e(quota_label($c['quota'])) ?></b></td>
				<td><?php foreach ($c['criteria'] as $cr): ?><?= tag($cr['name'].($c['scoring_method'] === 'weighted' ? ' '.rtrim(rtrim(number_format((float) $cr['weight'], 2, '.', ''), '0'), '.').'%' : ''), 'blue') ?> <?php endforeach; ?></td>
				<td><?= $c['submission_mode'] === 'online' ? 'Online' : 'Offline' ?></td>
				<td>
					<?= tag($c['registration_open'] ? 'Dibuka' : 'Ditutup', $c['registration_open'] ? '' : 'yellow') ?>
					<?php if ($is_super): ?>
						<form method="post" action="<?= e(site_url('admin/lomba/'.$c['id'].'/pendaftaran')) ?>" class="mt" data-confirm="<?= $c['registration_open'] ? 'Tutup pendaftaran '.$c['name'].'?' : 'Buka pendaftaran '.$c['name'].'?' ?>">
							<?= csrf_field() ?><input type="hidden" name="open" value="<?= $c['registration_open'] ? '0' : '1' ?>">
							<button type="submit" class="btn sm <?= $c['registration_open'] ? 'ghost' : 'primary' ?>"><?= $c['registration_open'] ? 'Tutup' : 'Buka' ?></button>
						</form>
					<?php endif; ?>
				</td>
				<td><?= $c['judges'] ? e(implode(', ', array_column($c['judges'], 'full_name'))) : '<span class="muted">Belum ada</span>' ?></td>
				<td><div class="tdActions">
					<button type="button" class="btn soft sm" data-dialog-open="dlg-comp" data-fill="<?= json_attr($payload) ?>">Edit</button>
					<?php if ($c['slug'] !== 'bazaar-desa'): ?>
						<form method="post" action="<?= e(site_url('admin/lomba/'.$c['id'].'/hapus')) ?>" data-confirm="Hapus lomba <?= e($c['name']) ?>?">
							<?= csrf_field() ?><button type="submit" class="btn danger sm">Hapus</button>
						</form>
					<?php endif; ?>
				</div></td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
</div>

<dialog id="dlg-comp" class="modalDlg">
	<form method="post" action="<?= e(site_url('admin/lomba/simpan')) ?>">
		<?= csrf_field() ?>
		<input type="hidden" name="id" value="">
		<div class="modalHead"><h3 data-title-new="Tambah Lomba" data-title-edit="Edit Lomba">Lomba</h3><button type="button" class="x" data-dialog-close aria-label="Tutup">×</button></div>
		<div class="formGrid">
			<div class="field full"><label for="c-name">Nama Lomba</label><input id="c-name" type="text" name="name" maxlength="150" required></div>
			<div class="field"><label for="c-cluster">Cluster</label><input id="c-cluster" type="text" name="cluster" maxlength="100" placeholder="Contoh: Indoor • Hari H"></div>
			<div class="field"><label for="c-age">Kategori Peserta</label><input id="c-age" type="text" name="age_label" maxlength="150" required placeholder="Contoh: SD Kelas 4–6"></div>
			<div class="field"><label for="c-type">Keterangan Jenis</label><input id="c-type" type="text" name="type_label" maxlength="100" placeholder="Contoh: Regu 3 orang"><small class="sub">Jelaskan jumlah/format peserta saja; ketentuan gender diatur terpisah.</small></div>
			<div class="field"><label for="c-part">Dinilai sebagai</label>
				<select id="c-part" name="participation_type"><option value="individual">Perorangan (tiap peserta dinilai sendiri)</option><option value="team">Regu (satu desa dinilai bersama)</option></select>
			</div>
			<div class="field"><label for="c-gender">Gender peserta</label>
				<select id="c-gender" name="gender_option" required>
					<option value="both" data-individual-label="Putra/Putri" data-team-label="Putra dan putri">Putra/Putri</option>
					<option value="male">Putra saja</option>
					<option value="female">Putri saja</option>
				</select>
				<small class="sub">Untuk regu dengan komposisi khusus, rincikan jumlah putra/putri pada ketentuan.</small>
			</div>
			<div class="field"><label for="c-quota">Kuota maksimal peserta per desa</label><input id="c-quota" type="number" name="quota" min="1" max="1000" placeholder="Kosongkan = tidak dibatasi"></div>
			<div class="field"><label for="c-min-participants">Jumlah minimal peserta</label><input id="c-min-participants" type="number" name="minimum_participants" min="1" max="1000" placeholder="Opsional"><small class="sub">Isi jika lomba mensyaratkan jumlah peserta minimum; kuota maksimal juga harus diisi.</small></div>
			<div class="field"><label for="c-sub">Pengumpulan</label>
				<select id="c-sub" name="submission_mode"><option value="offline">Offline</option><option value="online">Online (link karya)</option></select>
			</div>
			<div class="field"><label for="c-win">Jumlah pemenang</label><input id="c-win" type="number" name="winner_count" min="1" max="20" value="3"></div>
			<div class="field"><label for="c-icon">Ikon</label>
				<select id="c-icon" name="icon"><option value="">(tanpa ikon)</option><?php foreach ($icons as $ic): ?><option value="<?= e($ic) ?>"><?= e(preg_replace('/\.png$/', '', $ic)) ?></option><?php endforeach; ?></select>
			</div>
			<div class="field full"><label for="c-place">Lokasi</label><input id="c-place" type="text" name="place" maxlength="190"></div>
			<div class="field full"><label for="c-rules">Ketentuan / petunjuk teknis <small class="sub">(satu butir per baris)</small></label><textarea id="c-rules" name="rules" rows="5"></textarea></div>

			<div class="field full">
				<div class="dynamicAdd"><div><label>Kriteria Penilaian</label><small class="sub">Satu kotak = satu kriteria (nilai 0–100). Klik + untuk menambah, × untuk menghapus.</small></div><button type="button" class="dynamicBtn add" data-row-add="criteria" aria-label="Tambah kriteria">+</button></div>
				<div class="dynamicList" data-rows="criteria"></div>
				<template data-row-template="criteria"><div class="dynamicRow"><input type="text" name="criteria[]" maxlength="190" placeholder="Nama kriteria penilaian"><button type="button" class="dynamicBtn remove" data-row-remove aria-label="Hapus kriteria">×</button></div></template>
			</div>

			<?php if ($is_super): ?><div class="field full">
				<label>Juri yang bertugas</label>
				<?php if ($judges): ?>
					<div class="checkGrid"><?php foreach ($judges as $j): ?>
						<label class="check"><input type="checkbox" name="judges[]" value="<?= (int) $j['id'] ?>"><span><?= e($j['full_name']) ?> <small class="sub"><?= e($j['email']) ?></small></span></label>
					<?php endforeach; ?></div>
				<?php else: ?>
					<small class="sub">Belum ada akun juri. Buat dulu di menu <a href="<?= e(site_url('admin/akun')) ?>">Kelola Akun</a>.</small>
				<?php endif; ?>
			</div><?php else: ?><div class="field full"><small class="sub">Penugasan juri hanya dapat diubah oleh Super Admin.</small></div><?php endif; ?>
		</div>
		<div class="modalFoot"><button type="button" class="btn ghost" data-dialog-close>Batal</button><button type="submit" class="btn primary" data-pending-text="Menyimpan…">Simpan</button></div>
	</form>
</dialog>
