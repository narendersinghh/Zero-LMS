<?php
use App\Core\Hooks;
use App\Core\Theme;
$u = auth();
$groups = [
    'Overview' => [['Dashboard', '/admin', 'grid', 'admin.access'], ['Reports', '/admin/reports', 'chart', 'reports.view']],
    'Manage' => [['Courses & videos', '/admin/courses', 'book', 'courses.manage'], ['Students', '/admin/students', 'users', 'students.manage'], ['Enrollments', '/admin/enrollments', 'layers', 'enrollments.manage'], ['Orders', '/admin/orders', 'key', 'orders.manage']],
    'Configure' => [['Settings', '/admin/settings', 'settings', 'branding.manage'], ['Modules', '/admin/modules', 'package', 'modules.manage'], ['System & updates', '/admin/system', 'refresh', 'settings.system']],];
// Pages without their own sidebar item highlight their parent section.
$alias = ['/admin/courses' => ['/admin/videos', '/admin/lessons'], '/admin/students' => ['/admin/import'], '/admin/settings' => ['/admin/emails', '/admin/team', '/admin/logs', '/admin/mail-log']];
$extra = Hooks::filter('admin.nav', []);
$badges = ['/admin/orders' => can('orders.manage') ? (int)\App\Core\DB::val("SELECT COUNT(*) FROM {orders} WHERE status = 'submitted'") : 0];
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
<?php $fold = $g === 'Modules'; $open = $fold && array_filter($vis, fn($i) => is_path($i[1])); ?>
<?= $fold ? '<details class="sidenav-group sidenav-fold"' . ($open ? ' open' : '') . '><summary class="sidenav-label">' . e($g) . ' <small>' . count($vis) . '</small>' . icon('chevron-down') . '</summary>' : '<div class="sidenav-group"><span class="sidenav-label">' . e($g) . '</span>' ?>
<?php foreach ($vis as $i): $active = $i[1] === '/admin' ? app()->request()->path() === '/admin' : (is_path($i[1]) || array_filter($alias[$i[1]] ?? [], 'is_path')); ?>
<a href="<?= e(url($i[1])) ?>" class="<?= $active ? 'active' : '' ?>"><?= icon($i[2]) ?><span><?= e($i[0]) ?></span><?php if (!empty($badges[$i[1]])): ?><span class="count"><?= (int)$badges[$i[1]] ?></span><?php endif; ?></a>
<?php endforeach; ?><?= $fold ? '</details>' : '</div>' ?>
<?php endforeach; ?>
</nav>
<div class="sidebar-foot"><a href="<?= e(url('/dashboard')) ?>" class="btn btn-ghost btn-sm btn-block"><?= icon('eye') ?>Student view</a><small class="muted">Zeroes v<?= e(\App\Core\App::version()) ?></small></div>
</aside>
<div class="sidebar-backdrop" data-sidebar-close></div>
<div class="admin-main">
<header class="admin-top"><div class="admin-top-in">
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
</div></header>
<main id="main" class="admin-content">
<?php if (is_file(STORAGE_PATH . '/maintenance')): ?><div class="alert alert-warning"><?= icon('alert') ?><span>Maintenance mode is on. Students cannot access the site.</span></div><?php endif; ?>
<?php partial('flash'); ?>
<?= $content ?>
</main>
</div>
<script src="<?= e(asset('dist/admin.min.js')) ?>" defer></script>
</body>
</html>
