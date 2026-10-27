<?php
$pageTitle = !empty($title) ? $title.' · FGU 3.0' : 'Papan Nilai · FGU 3.0';
?>
<!doctype html>
<html lang="id">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="description" content="Papan nilai langsung Lomba Cerdas Cermat FGU 3.0.">
	<meta name="theme-color" content="#071629">
	<title><?= e($pageTitle) ?></title>
	<link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
	<link rel="stylesheet" href="<?= e(asset('css/scoreboard.css')) ?>">
	<script src="<?= e(asset('js/scoreboard.js')) ?>" defer></script>
</head>
<body class="scoreboardBody">
	<?= $layout_content ?>
</body>
</html>
