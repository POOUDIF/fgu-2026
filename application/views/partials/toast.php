<?php
/** Pesan flash: pesan pendek tampil sebagai toast; pesan panjang (mis. sandi sementara) tetap tampil sebagai alert. */
$flash = $this->session->flashdata('flash');
if (is_array($flash) && !empty($flash['message'])):
	$ok = $flash['type'] === 'ok';
	if (mb_strlen($flash['message']) > 110): ?>
		<div class="alert flashAlert <?= $ok ? 'ok' : 'err' ?>" role="<?= $ok ? 'status' : 'alert' ?>"><?= e($flash['message']) ?></div>
	<?php else: ?>
		<div id="toast" class="toast show <?= $ok ? 'ok' : 'err' ?>" role="<?= $ok ? 'status' : 'alert' ?>" data-toast><?= e($flash['message']) ?></div>
	<?php endif;
endif; ?>
