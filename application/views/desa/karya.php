<?php /** @var array $village; @var list $entries */ ?>
<?= page_head('Pengumpulan Karya Online', 'Khusus lomba online. Tempel link karya (Google Drive / media sosial) untuk setiap peserta.') ?>

<div class="tableWrap">
	<table>
		<thead><tr><th>Peserta</th><th>Lomba</th><th>Link Karya</th><th>Status</th><th>Aksi</th></tr></thead>
		<tbody>
		<?php foreach ($entries as $e): ?>
			<tr>
				<td><b><?= e($e['label']) ?></b></td>
				<td><?= e($e['competition_name']) ?></td>
				<td><?= $e['submission_url'] ? '<a class="tag blue" target="_blank" rel="noopener noreferrer" href="'.e($e['submission_url']).'">Buka Link</a>' : 'Belum ada' ?></td>
				<td><?= $e['submission_url'] ? tag('Terkumpul') : tag('Belum', 'red') ?></td>
				<td><button type="button" class="btn soft sm" data-dialog-open="dlg-link" data-action="<?= e(site_url('desa/karya/'.$e['id'])) ?>" data-fill="<?= json_attr(array('label' => $e['label'].' — '.$e['competition_name'], 'submission_url' => $e['submission_url'])) ?>">Input / Ubah Link</button></td>
			</tr>
		<?php endforeach; ?>
		<?php if (!$entries): ?><tr><td colspan="5" class="empty">Belum ada peserta pada lomba online. Daftarkan dulu di menu Daftar Peserta Lomba.</td></tr><?php endif; ?>
		</tbody>
	</table>
</div>

<dialog id="dlg-link" class="modalDlg narrow">
	<form method="post" class="fields" data-field-action>
		<?= csrf_field() ?>
		<div class="modalHead"><h3>Pengumpulan Karya Online</h3><button type="button" class="x" data-dialog-close aria-label="Tutup">×</button></div>
		<p class="sub"><b data-field="label"></b></p>
		<div class="field"><label for="lk-url">Link Google Drive / karya</label><input id="lk-url" type="url" name="submission_url" maxlength="500" placeholder="https://drive.google.com/..."></div>
		<div class="modalFoot"><button type="button" class="btn ghost" data-dialog-close>Batal</button><button type="submit" class="btn primary" data-pending-text="Menyimpan…">Simpan Link</button></div>
	</form>
</dialog>
