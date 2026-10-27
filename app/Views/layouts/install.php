<!doctype html>
<html lang="en" data-theme="dark">
<head><?php partial('head', ['title' => 'Install Zeroes']); ?></head>
<body class="install-body">
<div class="install">
<div class="install-head"><?php partial('logo', ['href' => '/install']); ?><span class="muted">Installer · v<?= e(\App\Core\App::version()) ?></span></div>
<?php partial('flash'); ?>
<?= $content ?>
</div>
</body>
</html>
