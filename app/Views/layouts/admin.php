<?php
use App\Core\Hooks;
use App\Core\Theme;
$u = auth();
$groups = [
    'Overview' => [['Dashboard', '/admin', 'grid', 'admin.access'], ['Reports', '/admin/reports', 'chart', 'reports.view']],
    'Content' => [['Courses', '/admin/courses', 'book', 'courses.manage'], ['Videos', '/admin/videos', 'video', 'videos.manage']],
    'People' => [['Students', '/admin/students', 'users', 'students.manage'], ['Enrollments', '/admin/enrollments', 'layers', 'enrollments.manage'], ['CSV import', '/admin/import', 'upload', 'imports.run'], ['Team', '/admin/team', 'shield', 'admins.manage']],
    'Configure' => [['Email templates', '/admin/emails', 'mail', 'emails.manage'], ['Settings', '/admin/settings', 'settings', 'branding.manage'], ['Audit log', '/admin/logs', 'activity', 'logs.view'], ['System & updates', '/admin/system', 'package', 'settings.system']],];
$extra = Hooks::filter('admin.nav', []);
usort($extra, fn($a, $b) => ($a['order'] ?? 50) <=> ($b['order'] ?? 50));
foreach ($extra as $x) {
    $groups['Modules'][] = [$x['label'], $x['url'], $x['icon'] ?? 'package', $x['perm'] ?? 'admin.access'];}
?><!doctype html>
<html lang="en" data-theme="<?= e(Theme::current()) ?>">
<head><?php partial('head', ['title' => $title ?? 'Admin']); ?></head>
<body class="admin-body">
<a class="skip" href="#main">Skip to content</a>
<aside class="sidebar" id="sidebar">
<div class="sidebar-head"><?php partial('logo', ['href' => '/admin']); ?><span class="badge badge-accent">Admin</span></div>
<nav class="sidenav" aria-label="Admin">
<?php foreach ($groups as $g => $items): $vis = array_filter($items, fn($i) => can($i[3])); if (!$vis) continue; ?>
<div class="sidenav-group"><span class="sidenav-label"><?= e($g) ?></span>
<?php foreach ($vis as $i): $active = $i[1] === '/admin' ? is_path('/admin') && app()->request()->path() === '/admin' : is_path($i[1]); ?>
<a href="<?= e(url($i[1])) ?>" class="<?= $active ? 'active' : '' ?>"><?= icon($i[2]) ?><span><?= e($i[0]) ?></span></a>
<?php endforeach; ?></div>
<?php endforeach; ?>
</nav>
<div class="sidebar-foot"><a href="<?= e(url('/dashboard')) ?>" class="btn btn-ghost btn-sm btn-block"><?= icon('eye') ?>Student view</a><small class="muted">Zeroes v<?= e(\App\Core\App::version()) ?></small></div>
</aside>
<div class="sidebar-backdrop" data-sidebar-close></div>
<div class="admin-main">
<header class="admin-top">
<button type="button" class="btn-icon menu-btn" data-sidebar-toggle aria-label="Open menu"><?= icon('menu') ?></button>
<div class="admin-title"><?php if (!empty($crumbs)): ?><nav class="crumbs" aria-label="Breadcrumb"><?php foreach ($crumbs as $label => $href): ?><a href="<?= e(url($href)) ?>"><?= e($label) ?></a><?= icon('chevron-right') ?><?php endforeach; ?></nav><?php endif; ?><h1><?= e($title ?? 'Dashboard') ?></h1></div>
<div class="topbar-actions">
<?php if (!empty($actions)): ?><div class="page-actions"><?= $actions ?></div><?php endif; ?>
<?php partial('theme_toggle'); ?>
<div class="dropdown" data-dropdown>
<button type="button" class="avatar-btn" data-dropdown-toggle aria-label="Account menu"><?php partial('avatar', ['u' => $u]); ?></button>
<div class="dropdown-menu">
<div class="dropdown-head"><span><b><?= e($u['name']) ?></b><small class="muted"><?= e(\App\Core\Auth::ROLES[$u['role']] ?? $u['role']) ?></small></span></div>
<a href="<?= e(url('/profile')) ?>"><?= icon('user') ?>My profile</a>
<a href="<?= e(url('/dashboard')) ?>"><?= icon('eye') ?>Student view</a>
<form method="post" action="<?= e(url('/logout')) ?>"><?= csrf_field() ?><button type="submit"><?= icon('logout') ?>Sign out</button></form>
</div>
</div>
</div>
</header>
<main id="main" class="admin-content">
<?php if (is_file(STORAGE_PATH . '/maintenance')): ?><div class="alert alert-warning"><?= icon('alert') ?><span>Maintenance mode is on — students cannot access the site.</span></div><?php endif; ?>
<?php partial('flash'); ?>
<?= $content ?>
</main>
</div>
<script src="<?= e(asset('dist/admin.min.js')) ?>" defer></script>
</body>
</html>
