<?php /** @var list $blocks; @var bool $is_super */ ?>
<?= page_head('Daftar Pemenang', 'Nilai akhir adalah rata-rata nilai juri dikurangi penalti.') ?>
<?php $roundLabels = array('babak_1' => 'Babak 1', 'babak_2' => 'Babak 2', 'babak_3' => 'Babak 3', 'babak_4' => 'Babak 4', 'babak_1_2' => 'Babak 1–2', 'final' => 'Final'); ?>

<div class="cards">
	<?php foreach ($blocks as $b):
		$c = $b['competition'];
		$ranked = array_values(array_filter($b['results'], static fn ($r) => $r['rank'] !== null));
		$shown = array_slice($ranked, 0, max(5, $c['winner_count']));
	?>
		<div class="card">
			<div class="top between">
				<div class="withIcon"><?= comp_icon($c, 'compImg sm') ?><div><h3><?= e($c['name']) ?></h3><small class="muted"><?= e($c['type_label']) ?> • <?= e($c['age_label']) ?></small></div></div>
				<?= tag(count($ranked).' dinilai', 'yellow') ?>
			</div>
			<form method="post" action="<?= e(site_url('admin/pemenang/'.(int) $c['id'].'/publikasi')) ?>" class="inlineForm">
				<?= csrf_field() ?>
				<input type="hidden" name="published" value="<?= $c['results_published'] ? '0' : '1' ?>">
				<span class="muted"><?= $c['results_published'] ? 'Hasil telah dipublikasikan.' : 'Hasil belum dipublikasikan.' ?></span>
				<button class="btn <?= $c['results_published'] ? 'soft' : 'primary' ?> sm" type="submit"><?= $c['results_published'] ? 'Batalkan publikasi' : 'Publikasikan hasil' ?></button>
			</form>
			<?php if ($shown): ?>
				<div class="tableWrap flush"><table class="compact">
					<thead><tr><th>Rank</th><th>Peserta</th><th class="num">Nilai Akhir</th><?php if ($is_super): ?><th></th><?php endif; ?></tr></thead>
					<tbody>
					<?php foreach ($shown as $r): ?>
						<tr class="<?= $r['is_winner'] ? 'winner' : '' ?>">
							<td><b><?= e(medal((int) $r['rank'])) ?></b></td>
							<td><?= e($r['label']) ?><br><small class="muted"><?= e($r['village_name']) ?></small></td>
							<td class="num"><b><?= e(fmt_score($r['final_score'])) ?></b>
								<br><small class="muted"><?= (int) $r['judge_count'] ?>/<?= (int) $r['expected_judges'] ?> juri<?= $r['penalty'] > 0 ? ' • penalti −'.e(fmt_score($r['penalty'])) : '' ?></small></td>
							<?php if ($is_super): ?>
								<td>
									<button type="button" class="btn soft sm" data-dialog-open="dlg-penalty" data-fill="<?= json_attr(array('entry_id' => $r['id'], 'label' => $r['label'].' — '.$r['village_name'])) ?>">Penalti</button>
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
				</table></div>
			<?php else: ?>
				<p class="muted">Belum ada nilai dari juri.</p>
			<?php endif; ?>
		</div>
	<?php endforeach; ?>
</div>

<?php if ($is_super): ?>
	<dialog id="dlg-penalty" class="modalDlg narrow">
		<form method="post" action="<?= e(site_url('admin/pemenang/penalti')) ?>" class="fields">
			<?= csrf_field() ?>
			<input type="hidden" name="entry_id" value="">
			<div class="modalHead"><h3>Tambah penalti</h3><button type="button" class="x" data-dialog-close aria-label="Tutup">×</button></div>
			<p class="sub" data-field="label"></p>
			<div class="field"><label for="pn-points">Poin pengurangan</label><input id="pn-points" type="text" inputmode="decimal" name="points" required pattern="\d+([.,]\d{1,2})?" placeholder="Contoh: 1"></div>
			<div class="field"><label for="pn-reason">Alasan</label><input id="pn-reason" type="text" name="reason" maxlength="500" placeholder="Contoh: terlambat / melanggar disiplin"></div>
			<div class="modalFoot"><button type="button" class="btn ghost" data-dialog-close>Batal</button><button class="btn primary" type="submit">Tambahkan</button></div>
		</form>
	</dialog>
<?php endif; ?>
