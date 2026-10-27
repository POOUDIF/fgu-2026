<?php /** @var list $blocks [competition, entries, scored, drafted] */ ?>
<?= page_head('Kontrol Lomba Juri', 'Hanya lomba yang ditugaskan kepada '.$current_user['full_name'].'.') ?>

<div class="controlGrid">
	<?php foreach ($blocks as $b): $c = $b['competition']; ?>
		<div class="controlCard">
			<div class="withIcon"><?= comp_icon($c, 'compImg sm') ?><div><h3><?= e($c['name']) ?></h3><p><?= count($b['entries']) ?> peserta<?= $b['cc_state'] ? ' • 4 babak • nilai bersama' : ' • '.count($c['criteria']).' kriteria • '.($c['submission_mode'] === 'online' ? 'Online' : 'Offline') ?></p></div></div>
			<?php if ($b['cc_state']): ?>
				<div class="mt"><?= $b['cc_state']['is_finished'] ? pill('Selesai', 'ok') : pill('Babak '.(int) $b['cc_state']['current_round'].'/4', 'info') ?></div>
				<a class="btn primary mt" href="<?= e(site_url('juri/nilai/'.$c['slug'])) ?>">Buka papan nilai</a>
			<?php else: ?>
			<div class="mt">
				<?php foreach ($b['entries'] as $e):
					$id = (int) $e['id'];
					$done = isset($b['scored'][$id]);
					$drafted = !$done && isset($b['drafted'][$id]);
				?>
					<div class="statusRow"><span><?= e($e['label']) ?><small class="block muted"><?= e($e['village_name']) ?></small></span><?= $done ? pill('Sudah disubmit', 'ok') : ($drafted ? pill('Draft tersimpan', 'info') : pill('Belum dinilai', 'warn')) ?></div>
				<?php endforeach; ?>
				<?php if (!$b['entries']): ?><div class="empty">Belum ada peserta.</div><?php endif; ?>
			</div>
			<?php endif; ?>
		</div>
	<?php endforeach; ?>
	<?php if (!$blocks): ?><div class="controlCard"><b>Belum ada penugasan lomba.</b></div><?php endif; ?>
</div>
