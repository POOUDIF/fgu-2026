<?php
use App\Domain\Role;
/** @var list $accounts, $villages, $competitions; @var array $assigned, $managed; @var list $assignable_roles; @var bool $is_super */
?>
<?= page_head('Kelola Akun', 'Tidak ada pendaftaran mandiri. Sandi sementara ditampilkan sekali setelah akun dibuat dan wajib diganti saat login pertama.', '<button type="button" class="btn primary" data-dialog-open="dlg-create-account" data-fill="'.json_attr(new stdClass()).'">+ Buat akun</button>') ?>

<p class="muted small"><?= count($accounts) ?> akun terdaftar.</p>
<div class="tableWrap">
	<table>
		<thead><tr><th>Nama</th><th>Email</th><th>Peran</th><th>Desa</th><th>Login terakhir</th><th>Aksi</th></tr></thead>
		<tbody>
		<?php foreach ($accounts as $a):
			$manageable = Role::canManage($current_user['role'], $a['role']);
			$self = (int) $a['id'] === (int) $current_user['id'];
		?>
			<tr>
				<td><b><?= e($a['full_name']) ?></b><?= $self ? ' '.tag('Anda') : '' ?></td>
				<td><?= e($a['email']) ?></td>
				<td><?= tag(Role::label($a['role']), Role::isAdmin($a['role']) ? 'red' : ($a['role'] === Role::JUDGE ? 'yellow' : '')) ?></td>
				<td><?= e($a['village_name'] ?? '–') ?></td>
				<td><?= $a['last_login_at'] ? e(fmt_datetime($a['last_login_at'])) : '<span class="muted">belum pernah</span>' ?></td>
				<td><div class="tdActions">
					<?php if (!$manageable): ?>
						<span class="muted" title="Hanya Super Admin yang dapat mengelola akun admin">🔒 terkunci</span>
					<?php else: ?>
						<button type="button" class="btn soft sm" data-dialog-open="dlg-edit-account" data-fill="<?= json_attr(array('role' => $a['role'], 'full_name' => $a['full_name'], 'village_id' => $a['village_id'], 'self' => $self ? '1' : '0')) ?>" data-action="<?= e(site_url('admin/akun/'.$a['id'].'/ubah')) ?>">Ubah</button>
						<button type="button" class="btn soft sm" data-dialog-open="dlg-reset-password" data-fill="<?= json_attr(array('label' => $a['email'])) ?>" data-action="<?= e(site_url('admin/akun/'.$a['id'].'/sandi')) ?>">Reset sandi</button>
						<?php if (!$self): ?>
							<form method="post" action="<?= e(site_url('admin/akun/'.$a['id'].'/hapus')) ?>" data-confirm="Hapus akun <?= e($a['email']) ?>?">
								<?= csrf_field() ?><button type="submit" class="btn danger sm">Hapus</button>
							</form>
						<?php endif; ?>
					<?php endif; ?>
				</div></td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
</div>

<div class="card sectionCard">
	<h2>Penugasan juri</h2>
	<?php if (!$is_super): ?><p class="muted">Penugasan juri hanya dapat dikelola oleh Super Admin melalui menu Daftar Lomba.</p><?php endif; ?>
	<?php $judges = array_values(array_filter($accounts, static fn ($account) => $account['role'] === Role::JUDGE)); ?>
	<?php if (!$judges): ?>
		<p class="muted">Belum ada akun berperan Juri. Buat akun Juri dengan tombol “Buat akun” di atas.</p>
	<?php else: ?>
		<?php if ($is_super): ?><div class="cards g2">
			<?php foreach ($judges as $judge): if (!Role::canManage($current_user['role'], $judge['role'])) continue; ?>
				<form method="post" action="<?= e(site_url('admin/akun/'.$judge['id'].'/juri')) ?>" class="card flat judgeAssignment">
					<?= csrf_field() ?>
					<div><h3><?= e($judge['full_name'] ?: $judge['email']) ?></h3><small class="muted"><?= e($judge['email']) ?></small></div>
					<strong>Lomba yang dinilai</strong>
					<?php foreach ($competitions as $c): ?>
						<label class="check"><input type="checkbox" name="competition[]" value="<?= (int) $c['id'] ?>" <?= in_array((int) $c['id'], $assigned[(int) $judge['id']] ?? array(), true) ? 'checked' : '' ?>><span><?= e($c['name']) ?></span></label>
					<?php endforeach; ?>
					<button type="submit" class="btn soft sm">Simpan penugasan</button>
				</form>
			<?php endforeach; ?>
		</div><?php endif; ?>
	<?php endif; ?>
</div>

<div class="card sectionCard">
	<h2>Penugasan Admin Lomba</h2>
	<?php $competitionAdmins = array_values(array_filter($accounts, static fn ($account) => $account['role'] === Role::COMPETITION_ADMIN)); ?>
	<?php if (!$competitionAdmins): ?>
		<p class="muted">Belum ada akun Admin Lomba. Buat akun dengan peran Admin Lomba, lalu pilih lomba yang menjadi tanggung jawabnya.</p>
	<?php else: ?>
		<div class="cards g2">
			<?php foreach ($competitionAdmins as $manager): if (!Role::canManage($current_user['role'], $manager['role'])) continue; ?>
				<form method="post" action="<?= e(site_url('admin/akun/'.$manager['id'].'/penugasan')) ?>" class="card flat judgeAssignment">
					<?= csrf_field() ?>
					<div><h3><?= e($manager['full_name'] ?: $manager['email']) ?></h3><small class="muted"><?= e($manager['email']) ?></small></div>
					<strong>Lomba yang dikelola</strong>
					<?php foreach ($competitions as $c): ?>
						<label class="check"><input type="checkbox" name="competition[]" value="<?= (int) $c['id'] ?>" <?= in_array((int) $c['id'], $managed[(int) $manager['id']] ?? array(), TRUE) ? 'checked' : '' ?>><span><?= e($c['name']) ?></span></label>
					<?php endforeach; ?>
					<button type="submit" class="btn soft sm">Simpan penugasan</button>
				</form>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
</div>

<dialog id="dlg-create-account" class="modalDlg narrow">
	<form method="post" action="<?= e(site_url('admin/akun/buat')) ?>" class="fields" autocomplete="off">
		<?= csrf_field() ?>
		<div class="modalHead"><h3>Buat akun</h3><button type="button" class="x" data-dialog-close aria-label="Tutup">×</button></div>
		<div class="field"><label for="n-name">Nama lengkap</label><input id="n-name" type="text" name="full_name" required maxlength="100"></div>
		<div class="field"><label for="n-email">Email</label><input id="n-email" type="email" name="email" required maxlength="190"></div>
		<div class="field"><label for="n-role">Peran</label>
			<select id="n-role" name="role" required data-role-select>
				<?php foreach ($assignable_roles as $r): ?><option value="<?= e($r) ?>"><?= e(Role::label($r)) ?></option><?php endforeach; ?>
			</select>
		</div>
		<div class="field" data-village-field><label for="n-village">Desa</label>
			<select id="n-village" name="village_id"><option value="">Pilih desa…</option><?php foreach ($villages as $v): ?><option value="<?= (int) $v['id'] ?>"><?= e($v['name']) ?></option><?php endforeach; ?></select>
		</div>
		<?php if ($is_super): ?><fieldset class="field" data-competition-field hidden>
			<legend>Lomba yang ditugaskan</legend>
			<?php foreach ($competitions as $c): ?><label class="check"><input type="checkbox" name="competition[]" value="<?= (int) $c['id'] ?>"><span><?= e($c['name']) ?></span></label><?php endforeach; ?>
		</fieldset><?php else: ?><fieldset class="field" data-manager-competition-field hidden>
			<legend>Lomba yang dikelola</legend>
			<?php foreach ($competitions as $c): ?><label class="check"><input type="checkbox" name="competition[]" value="<?= (int) $c['id'] ?>"><span><?= e($c['name']) ?></span></label><?php endforeach; ?>
		</fieldset><small class="sub">Penugasan juri diatur oleh Super Admin setelah akun dibuat.</small><?php endif; ?>
		<div class="field"><label for="n-pass">Sandi sementara <small class="sub">(kosongkan untuk dibuat acak, min. 8 karakter)</small></label><input id="n-pass" type="text" name="password" minlength="8" maxlength="72" autocomplete="off"></div>
		<div class="modalFoot"><button type="button" class="btn ghost" data-dialog-close>Batal</button><button class="btn primary" type="submit" data-pending-text="Membuat…">Buat akun</button></div>
	</form>
</dialog>

<dialog id="dlg-edit-account" class="modalDlg narrow">
	<form method="post" class="fields" data-field-action>
		<?= csrf_field() ?>
		<div class="modalHead"><h3>Ubah akun</h3><button type="button" class="x" data-dialog-close aria-label="Tutup">×</button></div>
		<div class="field"><label for="u-name">Nama lengkap</label><input id="u-name" type="text" name="full_name" required maxlength="100"></div>
		<div class="field"><label for="u-role">Peran</label>
			<select id="u-role" name="role" required data-role-select><?php foreach (Role::ALL as $r): ?><option value="<?= e($r) ?>"><?= e(Role::label($r)) ?></option><?php endforeach; ?></select>
		</div>
		<div class="field" data-village-field><label for="u-village">Desa</label>
			<select id="u-village" name="village_id"><option value="">Pilih desa…</option><?php foreach ($villages as $v): ?><option value="<?= (int) $v['id'] ?>"><?= e($v['name']) ?></option><?php endforeach; ?></select>
		</div>
		<div class="modalFoot"><button type="button" class="btn ghost" data-dialog-close>Batal</button><button class="btn primary" type="submit" data-pending-text="Menyimpan…">Simpan</button></div>
	</form>
</dialog>

<dialog id="dlg-reset-password" class="modalDlg narrow">
	<form method="post" class="fields" data-field-action autocomplete="off">
		<?= csrf_field() ?>
		<div class="modalHead"><h3>Reset sandi</h3><button type="button" class="x" data-dialog-close aria-label="Tutup">×</button></div>
		<p class="sub" data-field="label"></p>
		<div class="field"><label for="r-pass">Sandi sementara baru <small class="sub">(kosongkan untuk dibuat acak)</small></label><input id="r-pass" type="text" name="password" minlength="8" maxlength="72" autocomplete="off"></div>
		<div class="modalFoot"><button type="button" class="btn ghost" data-dialog-close>Batal</button><button class="btn primary" type="submit" data-pending-text="Memproses…">Reset sandi</button></div>
	</form>
</dialog>
