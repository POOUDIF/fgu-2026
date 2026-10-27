<?php
/** @var array $competition; @var array $board; @var string $feed_url */
$roundCount = count($board['round_labels']);
?>
<main class="spectatorBoard" data-scoreboard data-feed-url="<?= e($feed_url) ?>">
	<header class="spectatorHeader">
		<a class="spectatorBrand" href="<?= e(site_url('/')) ?>">FGU 3.0 <span>FESTIVAL GENERASI UNGGUL</span></a>
		<div class="spectatorLive"><i aria-hidden="true"></i><span data-connection>LIVE · MENYAMBUNG</span></div>
	</header>
	<section class="spectatorIntro">
		<p class="spectatorEyebrow">PAPAN NILAI LANGSUNG</p>
		<h1><?= e($competition['name']) ?></h1>
		<div class="spectatorRound" data-round aria-live="polite"></div>
		<div class="spectatorProgress" data-round-progress aria-label="Progres babak">
			<?php foreach ($board['round_labels'] as $index => $label): ?><span class="<?= $index + 1 === $board['current_round'] ? 'active' : '' ?>" data-round-indicator="<?= (int) $index + 1 ?>"><b><?= (int) $index + 1 ?></b><small><?= e($label) ?></small></span><?php endforeach; ?>
		</div>
	</section>
	<section class="spectatorTableCard" aria-label="Nilai setiap desa">
		<div class="spectatorTableScroll">
			<table class="spectatorTable">
				<thead><tr><th class="spectatorRank">PERINGKAT</th><th class="spectatorVillage">DESA</th><?php foreach ($board['round_labels'] as $index => $label): ?><th data-round-column="<?= (int) $index + 1 ?>"><?= e($label) ?></th><?php endforeach; ?><th class="spectatorTotal">TOTAL</th></tr></thead>
				<tbody data-score-rows>
				<?php foreach ($board['results'] as $result): ?>
					<tr><td><span class="spectatorRankBadge"><?= (int) $result['rank'] ?></span></td><td class="spectatorVillage"><?= e($result['village_name']) ?></td><?php for ($round = 1; $round <= $roundCount; $round++): ?><td data-round-column="<?= $round ?>"><?= e(fmt_score($result['round_scores'][$round])) ?></td><?php endfor; ?><td class="spectatorTotal"><?= e(fmt_score($result['final_score'])) ?></td></tr>
				<?php endforeach; ?>
				<?php if (!$board['results']): ?><tr><td colspan="<?= $roundCount + 3 ?>" class="spectatorEmpty">Menunggu desa yang hadir untuk mulai bertanding.</td></tr><?php endif; ?>
				</tbody>
			</table>
		</div>
	</section>
	<footer class="spectatorFooter"><span>Nilai diperbarui otomatis saat juri melakukan penilaian</span><span data-updated>Memuat pembaruan…</span></footer>
</main>
