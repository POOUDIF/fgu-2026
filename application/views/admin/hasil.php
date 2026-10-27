<?php /** @var list $rows, $audit, $result_blocks; @var bool $is_super */ ?>
<?= page_head('Hasil & Publikasi', 'Rekap nilai seluruh lomba dan kendalikan publikasi hasil.', '<a class="btn primary" data-export-btn href="'.e(site_url('admin/hasil/export')).'">⬇ Ekspor Excel</a>') ?>
<?php $roundLabels = array('babak_1' => 'Babak 1', 'babak_2' => 'Babak 2', 'babak_3' => 'Babak 3', 'babak_4' => 'Babak 4', 'babak_1_2' => 'Babak 1–2', 'final' => 'Final'); ?>

<div class="alert info">
	Nilai akhir mengikuti metode masing-masing lomba; Cerdas Cermat memakai papan nilai bersama per babak. Hasil baru terlihat oleh Admin Desa dan publik setelah dipublikasikan.
</div>

<?php foreach ($result_blocks as $block): $c = $block['competition']; $ranked = array_values(array_filter($block['results'], static fn ($r) => $r['rank'] !== NULL)); $isCerdasCermat = strtolower((string) $c['slug']) === 'cerdas-cermat'; ?>
	<div class="card resultCompetition">
		<div class="resultCompetitionHead">
			<div class="withIcon"><?= comp_icon($c, 'compImg sm') ?><div><h2><?= e($c['name']) ?></h2><small class="muted"><?= $isCerdasCermat ? count($ranked).' desa di papan peringkat · ' : count($ranked).'/'.count(array_filter($block['results'], static fn ($r) => $r['status'] === 'registered')).' pendaftaran dinilai · ' ?>pemenang <?= (int) $c['winner_count'] ?> teratas<?= $c['results_published'] ? ' · ' : '' ?></small><?php if ($c['results_published']): ?><?= tag('Terpublikasi', 'blue') ?><?php endif; ?></div></div>
			<div class="tdActions">
				<a class="btn white sm" href="<?= e(site_url('admin/hasil/'.$c['slug'])) ?>">Lihat rincian nilai</a>
				<a class="btn soft sm" href="<?= e(site_url('admin/pemenang')) ?>">Penalti &amp; pemenang</a>
				<form method="post" action="<?= e(site_url('admin/pemenang/'.(int) $c['id'].'/publikasi')) ?>">
					<?= csrf_field() ?><input type="hidden" name="published" value="<?= $c['results_published'] ? '0' : '1' ?>">
					<button type="submit" class="btn <?= $c['results_published'] ? 'soft' : 'primary' ?> sm"><?= $c['results_published'] ? 'Batalkan publikasi' : 'Publikasikan hasil' ?></button>
				</form>
			</div>
		</div>
		<?php if (!$ranked): ?><p class="muted resultEmpty">Belum ada nilai masuk.</p>
		<?php else: ?>
			<div class="tableWrap">
				<table class="resultSummaryTable">
					<thead><tr><th>#</th><th>Desa</th><th>Peserta</th><?php if ($isCerdasCermat): ?><th>Babak 1</th><th>Babak 2</th><th>Babak 3</th><th>Babak 4</th><th>Pengurangan</th><th>Total sementara</th><?php else: ?><th>Rata-rata juri</th><th>Pengurangan</th><th>Nilai akhir</th><th>Juri</th><?php endif; ?><?php if ($is_super): ?><th></th><?php endif; ?></tr></thead>
					<tbody>
					<?php foreach ($ranked as $r): ?>
						<tr class="<?= $r['is_winner'] ? 'winner' : '' ?>">
							<td><span class="rank <?= $r['rank'] <= 3 ? 'r'.(int) $r['rank'] : '' ?>"><?= (int) $r['rank'] ?></span></td>
							<td><?= e($r['village_name']) ?></td><td><?= e($r['label']) ?></td>
							<?php if ($isCerdasCermat): ?>
								<?php foreach ($r['round_scores'] as $roundScore): ?><td><?= e(fmt_score($roundScore)) ?></td><?php endforeach; ?>
								<td><?= $r['penalty'] > 0 ? '−'.e(fmt_score($r['penalty'])) : '—' ?></td>
								<td><b><?= e(fmt_score($r['final_score'])) ?></b></td>
							<?php else: ?>
								<td><?= $r['judge_count'] ? e(fmt_score($r['avg_total'])) : '—' ?></td>
								<td><?= $r['penalty'] > 0 ? '−'.e(fmt_score($r['penalty'])) : '—' ?></td>
								<td><b><?= $r['final_score'] !== NULL ? e(fmt_score($r['final_score'])) : '—' ?></b></td>
								<td><span class="tag <?= $r['judge_count'] < $r['expected_judges'] ? 'yellow' : '' ?>"><?= (int) $r['judge_count'] ?>/<?= (int) $r['expected_judges'] ?></span></td>
							<?php endif; ?>
							<?php if ($is_super): ?>
								<td class="resultPenaltyActions">
									<button type="button" class="btn soft sm" data-dialog-open="dlg-result-penalty" data-fill="<?= json_attr(array('entry_id' => $r['id'], 'label' => $r['label'].' — '.$r['village_name'])) ?>">Penalti</button>
									<?php foreach ($r['penalty_rows'] as $pen): ?>
										<form method="post" action="<?= e(site_url('admin/pemenang/penalti/'.$pen['id'].'/hapus')) ?>" class="inlineForm" data-confirm="Hapus penalti ini?">
											<?= csrf_field() ?><small>−<?= e(fmt_score($pen['points'])) ?> <?= !empty($pen['round_key']) ? e($roundLabels[$pen['round_key']] ?? 'Babak').' · ' : '' ?><?= e($pen['reason']) ?></small>
											<button class="btn danger sm" type="submit" aria-label="Hapus penalti">×</button>
										</form>
									<?php endforeach; ?>
								</td>
							<?php endif; ?>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		<?php endif; ?>
	</div>
<?php endforeach; ?>

<?php if ($is_super): ?>
	<dialog id="dlg-result-penalty" class="modalDlg narrow">
		<form method="post" action="<?= e(site_url('admin/pemenang/penalti')) ?>" class="fields">
			<?= csrf_field() ?>
			<input type="hidden" name="entry_id" value="">
			<div class="modalHead"><h3>Tambah penalti</h3><button type="button" class="x" data-dialog-close aria-label="Tutup">×</button></div>
			<p class="sub" data-field="label"></p>
			<div class="field"><label for="hr-points">Poin pengurangan</label><input id="hr-points" type="text" inputmode="decimal" name="points" required pattern="\d+([.,]\d{1,2})?" placeholder="Contoh: 1"></div>
			<div class="field"><label for="hr-reason">Alasan</label><input id="hr-reason" type="text" name="reason" maxlength="500" placeholder="Contoh: terlambat / melanggar disiplin"></div>
			<div class="modalFoot"><button type="button" class="btn ghost" data-dialog-close>Batal</button><button class="btn primary" type="submit">Tambahkan</button></div>
		</form>
	</dialog>
<?php endif; ?>

<div class="card">
	<h2>Penilaian tersimpan per juri</h2>
	<p class="muted">Detail nilai kriteria dan riwayat koreksi tersedia di bawah.</p>
</div>

<div class="tableWrap">
	<table>
		<thead><tr><th>No</th><th>Lomba</th><th>Peserta</th><th>Juri</th><th>Detail</th><th class="num">Total</th><th>Status</th><?php if ($is_super): ?><th>Koreksi</th><?php endif; ?></tr></thead>
		<tbody>
		<?php foreach ($rows as $i => $s): ?>
			<tr>
				<td><?= $i + 1 ?></td>
				<td><?= e($s['competition_name']) ?></td>
				<td><b><?= e($s['entry_label']) ?></b><br><small class="muted"><?= e($s['village_name']) ?></small></td>
				<td><?= e($s['judge_name']) ?></td>
				<td class="wrap"><?php foreach ($s['details'] as $k => $d): ?><?= $k ? ' • ' : '' ?><?= e($d['name']) ?>: <b><?= e(fmt_score($d['score'])) ?></b><?php endforeach; ?></td>
				<td class="num"><b><?= e(fmt_score($s['total'])) ?></b></td>
				<td><?= tag('Tersimpan 🔒') ?></td>
				<?php if ($is_super): ?>
					<td><div class="tdActions">
						<button type="button" class="btn soft sm" data-dialog-open="dlg-edit-score" data-fill="<?= json_attr(array(
							'entry_id' => $s['entry_id'], 'judge_id' => $s['judge_id'], 'label' => $s['judge_name'].' → '.$s['entry_label'],
							'scores' => array_map(static fn ($d) => array('id' => $d['criterion_id'], 'name' => $d['name'], 'value' => $d['score'], 'max' => $d['max_score']), $s['details']),
						)) ?>">Koreksi</button>
						<button type="button" class="btn danger sm" data-dialog-open="dlg-reset-judge" data-fill="<?= json_attr(array(
							'entry_id' => $s['entry_id'], 'judge_id' => $s['judge_id'], 'label' => $s['judge_name'].' → '.$s['entry_label'],
						)) ?>">Reset</button>
					</div></td>
				<?php endif; ?>
			</tr>
		<?php endforeach; ?>
		<?php if (!$rows): ?><tr><td colspan="<?= $is_super ? 8 : 7 ?>" class="empty">Belum ada penilaian tersimpan.</td></tr><?php endif; ?>
		</tbody>
	</table>
</div>

<div class="card sectionCard">
	<div class="pageHead compact"><div><h3>Riwayat koreksi (50 terbaru)</h3><p>Setiap koreksi nilai atau reset juri tercatat beserta alasannya.</p></div></div>
	<div class="tableWrap">
		<table>
			<thead><tr><th>Waktu</th><th>Aksi</th><th>Peserta</th><th>Juri</th><th>Kriteria</th><th class="num">Lama → Baru</th><th>Alasan</th><th>Oleh</th></tr></thead>
			<tbody>
			<?php foreach ($audit as $a): ?>
				<tr>
					<td><?= e(fmt_datetime($a['changed_at'])) ?></td>
					<td><?= $a['action'] === 'edit' || $a['action'] === 'manager_edit' ? tag('Koreksi', 'yellow') : ($a['action'] === 'manager_input' ? tag('Input Admin Lomba', 'blue') : tag('Reset juri', 'red')) ?></td>
					<td><?= e($a['competition_name']) ?><br><small class="muted"><?= e($a['village_name']) ?> — <?= e($a['entry_label']) ?></small></td>
					<td><?= e($a['judge_name']) ?></td>
					<td><?= e($a['criterion_name'] ?? '–') ?></td>
					<td class="num"><?= $a['old_score'] !== null ? e(fmt_score($a['old_score'])).' → '.e(fmt_score($a['new_score'])) : '–' ?></td>
					<td class="wrap"><?= e($a['reason']) ?></td>
					<td><?= e($a['changed_by_name']) ?></td>
				</tr>
			<?php endforeach; ?>
			<?php if (!$audit): ?><tr><td colspan="8" class="empty">Belum ada koreksi.</td></tr><?php endif; ?>
			</tbody>
		</table>
	</div>
</div>

<?php if ($is_super): ?>
	<dialog id="dlg-edit-score" class="modalDlg narrow">
		<form method="post" action="<?= e(site_url('admin/hasil/koreksi')) ?>" class="fields">
			<?= csrf_field() ?>
			<input type="hidden" name="entry_id" value=""><input type="hidden" name="judge_id" value="">
			<div class="modalHead"><h3>Koreksi nilai</h3><button type="button" class="x" data-dialog-close aria-label="Tutup">×</button></div>
			<p class="sub" data-field="label"></p>
			<div class="fields" data-score-fields></div>
			<div class="field"><label for="es-reason">Alasan (wajib)</label><textarea id="es-reason" name="reason" required maxlength="500" rows="3"></textarea></div>
			<div class="modalFoot"><button type="button" class="btn ghost" data-dialog-close>Batal</button><button class="btn primary" type="submit" data-pending-text="Menyimpan…">Simpan koreksi</button></div>
		</form>
	</dialog>
	<dialog id="dlg-reset-judge" class="modalDlg narrow">
		<form method="post" action="<?= e(site_url('admin/hasil/reset')) ?>" class="fields">
			<?= csrf_field() ?>
			<input type="hidden" name="entry_id" value=""><input type="hidden" name="judge_id" value="">
			<div class="modalHead"><h3>Reset input juri</h3><button type="button" class="x" data-dialog-close aria-label="Tutup">×</button></div>
			<p class="sub" data-field="label"></p>
			<div class="alert err">Seluruh nilai juri ini untuk peserta tersebut akan dihapus agar dapat dinilai ulang.</div>
			<div class="field"><label for="rj-reason">Alasan (wajib)</label><textarea id="rj-reason" name="reason" required maxlength="500" rows="3"></textarea></div>
			<div class="modalFoot"><button type="button" class="btn ghost" data-dialog-close>Batal</button><button class="btn danger" type="submit">Reset</button></div>
		</form>
	</dialog>
<?php endif; ?>
