<?php
/** @var list<string> $teams; @var string $sound_url */
$colors = array('red', 'blue', 'green', 'orange', 'purple', 'teal');
?>
<?= page_head('Buzzer Cerdas Cermat', 'Peserta yang pertama menekan tombol berhak menjawab. Tombol terkunci sampai panitia menekan Reset.') ?>

<section class="buzzer" data-buzzer data-sound="<?= e($sound_url) ?>">
	<div class="buzzerBar">
		<span class="buzzerHint">Pintasan: tombol <kbd>1</kbd>–<kbd>6</kbd> menekan buzzer, <kbd>Spasi</kbd> / <kbd>Esc</kbd> reset.</span>
		<div class="buzzerActions">
			<button type="button" class="btn ghost sm" data-buzzer-rename>Ubah nama peserta</button>
			<button type="button" class="btn ghost sm" data-buzzer-fullscreen>Layar penuh</button>
			<button type="button" class="btn primary sm" data-buzzer-reset>Reset</button>
		</div>
	</div>

	<div class="buzzerGrid">
		<?php foreach ($teams as $i => $team): ?>
			<div class="buzzerCell">
				<button type="button" class="buzzerBtn <?= e($colors[$i]) ?>" data-buzzer-btn="<?= (int) $i ?>" aria-label="Buzzer peserta <?= (int) $i + 1 ?>"><span><?= (int) $i + 1 ?></span></button>
				<input class="buzzerName" type="text" maxlength="40" value="<?= e($team) ?>" data-buzzer-name="<?= (int) $i ?>" aria-label="Nama peserta <?= (int) $i + 1 ?>" readonly>
			</div>
		<?php endforeach; ?>
	</div>

	<div class="buzzerOverlay" data-buzzer-overlay hidden role="alert" aria-live="assertive">
		<div class="buzzerWinner" data-buzzer-winner>
			<small>YANG BERHAK MENJAWAB</small>
			<b class="buzzerWinnerNo" data-buzzer-winner-no></b>
			<h2 data-buzzer-winner-name></h2>
			<button type="button" class="btn primary" data-buzzer-reset>Pertanyaan berikutnya</button>
		</div>
	</div>
</section>
