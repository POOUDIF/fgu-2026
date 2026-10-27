<?php /** @var list $entries, $public_entries, $villages, $competitions; @var int|null $f_lomba, $f_desa */
$query = http_build_query(array_filter(array('lomba' => $f_lomba, 'desa' => $f_desa)));
?>
<?= page_head('Rekap Pendaftaran Lomba', 'Seluruh peserta yang diinput oleh Admin Desa dan kiriman karya publik.',
	'<a class="btn primary" data-export-btn href="'.e(site_url('admin/pendaftaran/export').($query ? '?'.$query : '')).'">⬇ Ekspor Excel</a>') ?>

<div class="card sectionCard">
	<div class="pageHead compact"><div><h2>Total Peserta Masuk</h2><p>Jumlah peserta aktif pada setiap lomba berdasarkan desa.</p></div></div>
	<div class="recapMatrixWrap">
		<table class="recapMatrixTable">
			<thead><tr><th>Lomba</th><?php foreach ($villages as $v): ?><th><?= e($v['name']) ?></th><?php endforeach; ?></tr></thead>
			<tbody>
			<?php foreach ($competitions as $c): ?>
				<tr>
					<td><b><?= e($c['name']) ?></b></td>
					<?php foreach ($villages as $v): $count = (int) ($participant_matrix[(int) $c['id']][(int) $v['id']] ?? 0); ?>
						<td class="recapMatrixValue<?= $count === 0 ? ' isZero' : '' ?>"><?= $count ?></td>
					<?php endforeach; ?>
				</tr>
			<?php endforeach; ?>
			<?php if (!$competitions): ?><tr><td class="empty" colspan="<?= count($villages) + 1 ?>">Belum ada lomba aktif.</td></tr><?php endif; ?>
			</tbody>
			<?php if ($competitions): ?>
				<tfoot><tr><th>Total Lomba Terdaftar</th>
					<?php foreach ($villages as $v): $competitionCount = 0; ?>
						<?php foreach ($competitions as $c): if (!empty($participant_matrix[(int) $c['id']][(int) $v['id']])) $competitionCount++; endforeach; ?>
						<th class="recapMatrixValue"><?= $competitionCount ?></th>
					<?php endforeach; ?>
				</tr></tfoot>
			<?php endif; ?>
		</table>
	</div>
</div>

<form method="get" action="<?= e(site_url('admin/peserta')) ?>" class="filters">
	<div class="field"><label for="f-lomba">Lomba</label>
		<select id="f-lomba" name="lomba"><option value="">Semua lomba</option>
			<?php foreach ($competitions as $c): ?><option value="<?= (int) $c['id'] ?>" <?= $f_lomba === (int) $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach; ?>
		</select>
	</div>
	<div class="field"><label for="f-desa">Desa</label>
		<select id="f-desa" name="desa"><option value="">Semua desa</option>
			<?php foreach ($villages as $v): ?><option value="<?= (int) $v['id'] ?>" <?= $f_desa === (int) $v['id'] ? 'selected' : '' ?>><?= e($v['name']) ?></option><?php endforeach; ?>
		</select>
	</div>
	<button class="btn soft" type="submit">Terapkan</button>
	<a class="btn ghost" href="<?= e(site_url('admin/peserta')) ?>">Reset</a>
</form>

<div class="tableWrap">
	<table>
		<thead><tr><th>No</th><th>Desa</th><th>Lomba</th><th>Peserta</th><th>Jenis</th><th>Karya</th><th>Status</th></tr></thead>
		<tbody>
		<?php foreach ($entries as $i => $e): ?>
			<tr>
				<td><?= $i + 1 ?></td>
				<td><?= e($e['village_name']) ?></td>
				<td><?= e($e['competition_name']) ?></td>
				<td><?php if ($e['members']): ?><?php foreach ($e['members'] as $m): ?><b><?= e($m['name']) ?></b><?= $m['age'] ? ' <small class="muted">('.(int) $m['age'].' th)</small>' : '' ?><br><small class="muted"><?= $m['gender'] === 'L' ? 'Putra' : ($m['gender'] === 'P' ? 'Putri' : '') ?><?= $m['education_level'] ? ' • '.e($m['education_level']).($m['grade'] ? ' kelas '.(int) $m['grade'] : '') : '' ?></small><?php if ($m['parent_name']): ?><br><small class="muted">Orang tua: <?= e($m['parent_name']) ?></small><?php endif; ?><br><?php endforeach; ?><?php else: ?><b><?= e($e['label']) ?></b><?php endif; ?>
					<?php if ($e['parent_name']): ?><br><small class="muted">Orang tua: <?= e($e['parent_name']) ?></small><?php endif; ?>
					<?php if ($e['ig_username']): ?><br><small class="muted">Instagram: @<?= e($e['ig_username']) ?></small><?php endif; ?>
					<?php if ($e['members_note']): ?><br><small class="muted">Anggota: <?= e($e['members_note']) ?></small><?php endif; ?>
					<?= $e['source'] === 'public' ? tag('Kiriman publik', 'blue') : '' ?></td>
				<td><?= e($e['entry_type'] === 'team' ? 'Tim' : ($e['entry_type'] === 'individual' ? 'Individu' : $e['type_label'])) ?></td>
				<td><?= $e['submission_url'] ? '<a class="tag blue" target="_blank" rel="noopener noreferrer" href="'.e($e['submission_url']).'">Buka</a>' : '—' ?></td>
				<td>
					<?= $e['status'] === 'registered' ? tag('Terdaftar') : tag('Didiskualifikasi', 'red') ?>
					<?php if ($e['disqualified_reason']): ?><br><small class="muted"><?= e($e['disqualified_reason']) ?></small><?php endif; ?>
					<form method="post" action="<?= e(site_url('admin/pendaftaran/status/'.$e['id'])) ?>" class="inlineForm">
						<?= csrf_field() ?>
						<input type="hidden" name="f_lomba" value="<?= (int) $f_lomba ?>"><input type="hidden" name="f_desa" value="<?= (int) $f_desa ?>">
						<?php if ($e['status'] === 'registered'): ?>
							<input type="hidden" name="status" value="disqualified">
							<input type="text" name="reason" placeholder="Alasan (opsional)" maxlength="500" aria-label="Alasan diskualifikasi">
							<button class="btn danger sm" type="submit" data-confirm-btn="Diskualifikasi peserta ini?">Diskualifikasi</button>
						<?php else: ?>
							<input type="hidden" name="status" value="registered">
							<button class="btn soft sm" type="submit">Pulihkan</button>
						<?php endif; ?>
					</form>
				</td>
			</tr>
		<?php endforeach; ?>
		<?php if (!$entries): ?><tr><td colspan="7" class="empty">Belum ada pendaftaran peserta.</td></tr><?php endif; ?>
		</tbody>
	</table>
</div>

<div class="card sectionCard">
	<div class="pageHead compact"><div><h3>Pengumpulan Karya Online</h3><p>Kiriman karya yang masuk melalui halaman publik.</p></div><?= tag(count($public_entries).' kiriman', 'blue') ?></div>
	<div class="tableWrap">
		<table>
			<thead><tr><th>No</th><th>Lomba</th><th>Nama</th><th>Desa</th><th>Link Karya</th><th>Waktu</th></tr></thead>
			<tbody>
			<?php foreach ($public_entries as $i => $e): ?>
				<tr>
					<td><?= $i + 1 ?></td>
					<td><b><?= e($e['competition_name']) ?></b></td>
					<td><?= e($e['label']) ?></td>
					<td><?= e($e['village_name']) ?></td>
					<td><?= $e['submission_url'] ? '<a class="tag blue" target="_blank" rel="noopener noreferrer" href="'.e($e['submission_url']).'">Buka Karya</a>' : '—' ?></td>
					<td><?= e(fmt_datetime($e['created_at'])) ?></td>
				</tr>
			<?php endforeach; ?>
			<?php if (!$public_entries): ?><tr><td colspan="6" class="empty">Belum ada pengumpulan karya online.</td></tr><?php endif; ?>
			</tbody>
		</table>
	</div>
</div>
