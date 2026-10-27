<?php /** Layout layar penuh untuk login dan ganti sandi. */
$pageTitle = !empty($title) ? $title.' · FGU 3.0' : 'FGU 3.0';
?>
<!doctype html>
<html lang="id">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="robots" content="noindex, nofollow">
	<title><?= e($pageTitle) ?></title>
	<link rel="icon" type="image/png" href="<?= e(asset('img/favicon.png')) ?>">
	<link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
	<script src="<?= e(asset('js/app.js')) ?>" defer></script>
</head>
<body>
<?php $this->load->view('partials/toast'); ?>
<?= $layout_content ?>
</body>
</html>
