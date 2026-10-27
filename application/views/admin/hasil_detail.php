<?php /** @var array $competition; @var list $results, $judge_names; @var array $score_map; */ ?>
<?= page_head('Rincian Nilai — '.$competition['name'], 'Rincian nilai akhir, penalti, dan nilai kriteria dari setiap juri.', '<a class="btn white" href="'.e(site_url('admin/hasil')).'">Kembali ke hasil</a>') ?>
<?php $roundLabels = array('babak_1' => 'Babak 1', 'babak_2' => 'Babak 2', 'babak_3' => 'Babak 3', 'babak_4' => 'Babak 4', 'babak_1_2' => 'Babak 1–2', 'final' => 'Final'); $isCerdasCermat = strtolower((string) $competition['slug']) === 'cerdas-cermat'; ?>

<div class="card resultDetailIntro">
	<div class="withIcon"><?= comp_icon($competition, 'compImg sm') ?><div><h2><?= e($competition['name']) ?></h2><p class="muted"><?= e($competition['type_label']) ?> · <?= e($competition['age_label']) ?> · <?= e($competition['scoring_method'] === 'weighted' ? 'Rata-rata berbobot' : 'Akumulasi poin') ?></p></div></div>
</div>

<?php if ($isCerdasCermat): ?>
	<div class="tableWrap">
		<table class="resultSummaryTable">
			<thead><tr><th>#</th><th>Desa</th><th>Peserta</th><th>Babak 1</th><th>Babak 2</th><th>Babak 3</th><th>Babak 4</th><th>Pengurangan</th><th>Total</th></tr></thead>
			<tbody>
			<?php foreach ($results as $r): if ($r['rank'] === NULL) continue; ?>
				<tr class="<?= $r['is_winner'] ? 'winner' : '' ?>">
					<td><span class="rank <?= $r['rank'] <= 3 ? 'r'.(int) $r['rank'] : '' ?>"><?= (int) $r['rank'] ?></span></td>
					<td><?= e($r['village_name']) ?></td><td><?= e($r['label']) ?></td>
					<?php foreach ($r['round_scores'] as $roundScore): ?><td><?= e(fmt_score($roundScore)) ?></td><?php endforeach; ?>
					<td><?= $r['penalty'] > 0 ? '−'.e(fmt_score($r['penalty'])) : '—' ?></td>
					<td><b><?= e(fmt_score($r['final_score'])) ?></b></td>
				</tr>
			<?php endforeach; ?>
			<?php if (!array_filter($results, static fn ($r) => $r['rank'] !== NULL)): ?><tr><td colspan="9" class="empty">Belum ada nilai masuk.</td></tr><?php endif; ?>
			</tbody>
		</table>
	</div>
	<?php foreach ($results as $r): if ($r['rank'] === NULL || !$r['penalty_rows']) continue; ?>
		<section class="card resultEntryDetail">
			<div class="resultEntryHead"><div><h2><?= e($r['village_name']) ?> — <?= e($r['label']) ?></h2><p class="muted">Total sementara <b><?= e(fmt_score($r['final_score'])) ?></b></p></div><span class="tag yellow"><?= count($r['penalty_rows']) ?> pengurangan · −<?= e(fmt_score($r['penalty'])) ?></span></div>
			<div class="resultPenaltyNotes"><b>Catatan pengurangan</b><?php foreach ($r['penalty_rows'] as $pen): ?><span>−<?= e(fmt_score($pen['points'])) ?><?= !empty($pen['round_key']) ? ' · '.e($roundLabels[$pen['round_key']] ?? 'Babak') : '' ?><?= $pen['reason'] ? ' — '.e($pen['reason']) : '' ?></span><?php endforeach; ?></div>
		</section>
	<?php endforeach; ?>
<?php else: ?>
<div class="tableWrap">
	<table class="resultSummaryTable">
		<thead><tr><th>#</th><th>Desa</th><th>Peserta</th><th>Rata-rata juri</th><th>Pengurangan</th><th>Nilai akhir</th><th>Juri</th></tr></thead>
		<tbody>
		<?php foreach ($results as $r): if ($r['rank'] === NULL) continue; ?>
			<tr class="<?= $r['is_winner'] ? 'winner' : '' ?>">
				<td><span class="rank <?= $r['rank'] <= 3 ? 'r'.(int) $r['rank'] : '' ?>"><?= (int) $r['rank'] ?></span></td>
				<td><?= e($r['village_name']) ?></td>
				<td><?= e($r['label']) ?><?php if (!empty($r['slot_label'])): ?><br><small class="muted"><?= e($r['slot_label']) ?></small><?php endif; ?></td>
				<td><?= e(fmt_score($r['avg_total'])) ?></td>
				<td><?= $r['penalty'] > 0 ? '−'.e(fmt_score($r['penalty'])) : '—' ?></td>
				<td><b><?= e(fmt_score($r['final_score'])) ?></b></td>
				<td><?= (int) $r['judge_count'] ?>/<?= (int) $r['expected_judges'] ?></td>
			</tr>
		<?php endforeach; ?>
		<?php if (!array_filter($results, static fn ($r) => $r['rank'] !== NULL)): ?><tr><td colspan="7" class="empty">Belum ada nilai masuk.</td></tr><?php endif; ?>
		</tbody>
	</table>
</div>

<?php foreach ($results as $r): if ($r['rank'] === NULL) continue; ?>
	<section class="card resultEntryDetail">
		<div class="resultEntryHead">
			<div><h2><span class="rank <?= $r['rank'] <= 3 ? 'r'.(int) $r['rank'] : '' ?>"><?= (int) $r['rank'] ?></span> <?= e($r['label']) ?></h2><p class="muted"><?= e($r['village_name']) ?> · Nilai akhir <b><?= e(fmt_score($r['final_score'])) ?></b></p></div>
			<?php if ($r['penalty_rows']): ?><span class="tag yellow"><?= count($r['penalty_rows']) ?> penalti · −<?= e(fmt_score($r['penalty'])) ?></span><?php endif; ?>
		</div>
		<div class="tableWrap flush">
			<table class="resultCriteriaTable">
				<thead><tr><th>Juri</th><th>Kriteria</th><th>Nilai</th><th>Nilai maks.</th><th>Total juri</th></tr></thead>
				<tbody>
				<?php foreach ($judge_names as $judgeId => $judgeName):
					$submission = $score_map[$r['id']][$judgeId] ?? NULL;
				?>
					<?php if ($submission): foreach ($submission['details'] as $i => $detail): ?>
						<tr>
							<?php if ($i === 0): ?><td rowspan="<?= count($submission['details']) ?>"><b><?= e($judgeName) ?></b></td><?php endif; ?>
							<td><?= e($detail['name']) ?></td><td><?= e(fmt_score($detail['score'])) ?></td><td><?= e(fmt_score($detail['max_score'])) ?></td>
							<?php if ($i === 0): ?><td rowspan="<?= count($submission['details']) ?>"><b><?= e(fmt_score($submission['total'])) ?></b></td><?php endif; ?>
						</tr>
					<?php endforeach; else: ?>
						<tr><td><b><?= e($judgeName) ?></b></td><td colspan="3" class="muted">Belum menilai</td><td>—</td></tr>
					<?php endif; ?>
				<?php endforeach; ?>
				<?php if (!$judge_names): ?><tr><td colspan="5" class="empty">Belum ada nilai juri.</td></tr><?php endif; ?>
				</tbody>
			</table>
		</div>
		<?php if ($r['penalty_rows']): ?><div class="resultPenaltyNotes"><b>Catatan penalti</b><?php foreach ($r['penalty_rows'] as $pen): ?><span>−<?= e(fmt_score($pen['points'])) ?><?= !empty($pen['round_key']) ? ' · '.e($roundLabels[$pen['round_key']] ?? 'Babak') : '' ?><?= $pen['reason'] ? ' — '.e($pen['reason']) : '' ?></span><?php endforeach; ?></div><?php endif; ?>
	</section>
<?php endforeach; ?>
<?php endif; ?>
