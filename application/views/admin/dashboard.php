<?php /** @var int $participant_count, $entry_count, $village_count, $submission_count, $score_percent, $score_done, $score_expected, $recent_score_count; @var list $recent_scores, $progress_rows */ ?>

<div class="adminStats">
	<div class="stat"><b><?= (int) $participant_count ?></b><span>Peserta terdata</span></div>
	<div class="stat"><b><?= (int) $entry_count ?></b><span>Pendaftaran lomba · <?= (int) $village_count ?>/9 desa</span></div>
	<div class="stat"><b><?= (int) $submission_count ?></b><span>Penilaian juri masuk</span></div>
	<div class="stat"><b><?= (int) $score_percent ?>%</b><span>Progres penilaian (<?= (int) $score_done ?>/<?= (int) $score_expected ?>)</span></div>
</div>

<div class="card liveScores" data-live-feed data-feed-url="<?= e(site_url('admin/feed')) ?>" data-total="<?= (int) $recent_score_count ?>">
	<div class="pageHead compact"><div><h2>Penilaian masuk (live)</h2><p><span class="dot"></span> Live — nilai baru muncul otomatis</p></div><button type="button" class="btn soft sm" data-feed-refresh>Muat ulang</button></div>
	<div class="feedList" data-feed-rows>
		<?php foreach ($recent_scores as $i => $s): ?>
			<div class="feedItem">
				<span class="feedRank"><?= $i + 1 ?></span>
				<div class="feedInfo"><b><?= e($s['competition_name']) ?></b><span><?= e($s['village_name']) ?> · <?= e($s['entry_label']) ?></span><small>Juri <?= e($s['judge_name']) ?> · <?= e(fmt_datetime($s['submitted_at'])) ?></small></div>
				<div class="feedScore"><b><?= e(fmt_score($s['total'])) ?></b><small><?= (int) $s['criteria_count'] ?> kriteria</small></div>
			</div>
		<?php endforeach; ?>
		<?php if (!$recent_scores): ?><p class="muted" data-feed-empty>Belum ada penilaian masuk.</p><?php endif; ?>
	</div>
	<div class="feedPagination" data-feed-pagination hidden>
		<span class="muted small" data-feed-page></span>
		<div class="row"><button type="button" class="btn white sm" data-feed-prev>← Sebelumnya</button><button type="button" class="btn white sm" data-feed-next>Berikutnya →</button></div>
	</div>
	<p class="muted small" data-feed-status aria-live="polite"></p>
</div>

<div class="card progressCard">
	<h2>Progres per lomba &amp; juri</h2>
	<?php if (!$progress_rows): ?>
		<p class="muted">Belum ada juri yang ditugaskan.</p>
	<?php else: ?>
		<div class="tableWrap">
			<table class="progressTable">
				<thead><tr><th>Lomba</th><th>Juri</th><th>Dinilai</th></tr></thead>
				<tbody>
				<?php foreach ($progress_rows as $progress): $percent = $progress['total_entries'] ? (int) round(100 * $progress['scored_entries'] / $progress['total_entries']) : 0; ?>
					<tr>
						<td><?= e($progress['competition_name']) ?></td>
						<td><?= e($progress['judge_name'] ?: '—') ?></td>
						<td class="progressValue"><b><?= (int) $progress['scored_entries'] ?>/<?= (int) $progress['total_entries'] ?></b><div class="progressTrack"><span style="width:<?= $percent ?>%"></span></div></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	<?php endif; ?>
</div>

<?php if ($cc_competition): ?>
	<div class="card progressCard">
		<div class="pageHead compact"><div><h2>Papan nilai Cerdas Cermat</h2><p><?= e($cc_competition['name']) ?> · <?= $cc_state['is_finished'] ? 'selesai · peringkat akhir' : 'Babak '.(int) $cc_state['current_round'].'/4 · peringkat sementara' ?> berdasarkan total 4 babak.</p></div><a class="btn white sm" href="<?= e(site_url('admin/hasil/'.$cc_competition['slug'])) ?>">Rincian nilai</a></div>
		<div class="tableWrap">
			<table class="resultSummaryTable">
				<thead><tr><th>#</th><th>Desa</th><th>Babak 1</th><th>Babak 2</th><th>Babak 3</th><th>Babak 4</th><th>Total sementara</th></tr></thead>
				<tbody>
				<?php foreach ($cc_results as $result): if ($result['rank'] === NULL) continue; ?>
					<tr>
						<td><span class="rank <?= $result['rank'] <= 3 ? 'r'.(int) $result['rank'] : '' ?>"><?= (int) $result['rank'] ?></span></td>
						<td><b><?= e($result['village_name']) ?></b></td>
						<?php foreach ($result['round_scores'] as $roundScore): ?><td><?= e(fmt_score($roundScore)) ?></td><?php endforeach; ?>
						<td><b><?= e(fmt_score($result['final_score'])) ?></b></td>
					</tr>
				<?php endforeach; ?>
				<?php if (!array_filter($cc_results, static fn ($result) => $result['rank'] !== NULL)): ?><tr><td colspan="7" class="empty">Belum ada desa hadir.</td></tr><?php endif; ?>
				</tbody>
			</table>
		</div>
	</div>
<?php endif; ?>
