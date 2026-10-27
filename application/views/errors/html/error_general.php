<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= htmlspecialchars($heading, ENT_QUOTES, 'UTF-8') ?></title>
<style>
body{margin:0;min-height:100vh;display:grid;place-items:center;font-family:system-ui,-apple-system,"Segoe UI",sans-serif;background:#f4f7fb;color:#12263a}
.box{max-width:32rem;padding:2rem;text-align:center}
h1{font-size:1.6rem;margin:0 0 1rem;color:#1677c8}
a{color:#1677c8}
</style>
</head>
<body>
<div class="box">
	<h1><?= htmlspecialchars($heading, ENT_QUOTES, 'UTF-8') ?></h1>
	<?= $message /* sudah dibungkus <p> oleh show_error(); teks dari kode kami sendiri */ ?>
	<p><a href="/">Kembali ke beranda</a></p>
</div>
</body>
</html>
