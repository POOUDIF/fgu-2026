<?php /** @var list $villages */ ?>
<?= page_head('Daftar Desa', 'Kelola desa yang menjadi peserta FGU 3.0.', '<button type="button" class="btn primary" data-dialog-open="dlg-village" data-fill="'.json_attr(new stdClass()).'">+ Tambah Desa</button>') ?>

<div class="tableWrap">
	<table>
		<thead><tr><th>No</th><th>Nama Desa</th><th>Penanggung Jawab Desa</th><th>Aksi</th></tr></thead>
		<tbody>
		<?php foreach ($villages as $i => $v): ?>
			<tr>
				<td><?= $i + 1 ?></td>
				<td><b><?= e($v['name']) ?></b></td>
				<td><?= e($v['pic_name'] ?? '–') ?></td>
				<td><div class="tdActions">
					<button type="button" class="btn soft sm" data-dialog-open="dlg-village" data-fill="<?= json_attr(array('id' => (int) $v['id'], 'name' => $v['name'], 'pic_name' => $v['pic_name'])) ?>">Edit</button>
					<form method="post" action="<?= e(site_url('admin/desa/'.$v['id'].'/hapus')) ?>" data-confirm="Hapus desa <?= e($v['name']) ?>?">
						<?= csrf_field() ?><button type="submit" class="btn danger sm">Hapus</button>
					</form>
				</div></td>
			</tr>
		<?php endforeach; ?>
		<?php if (!$villages): ?><tr><td colspan="4" class="empty">Belum ada desa.</td></tr><?php endif; ?>
		</tbody>
	</table>
</div>

<dialog id="dlg-village" class="modalDlg narrow">
	<form method="post" action="<?= e(site_url('admin/desa/simpan')) ?>" class="fields">
		<?= csrf_field() ?>
		<input type="hidden" name="id" value="">
		<div class="modalHead"><h3 data-title-new="Tambah Desa" data-title-edit="Edit Desa">Desa</h3><button type="button" class="x" data-dialog-close aria-label="Tutup">×</button></div>
		<div class="field"><label for="v-name">Nama Desa</label><input id="v-name" type="text" name="name" maxlength="100" required></div>
		<div class="field"><label for="v-pic">Nama Penanggung Jawab Desa</label><input id="v-pic" type="text" name="pic_name" maxlength="100"></div>
		<div class="modalFoot"><button type="button" class="btn ghost" data-dialog-close>Batal</button><button type="submit" class="btn primary" data-pending-text="Menyimpan…">Simpan</button></div>
	</form>
</dialog>
