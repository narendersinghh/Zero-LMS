<?php
use App\Core\Hooks;
use App\Core\Theme;
use App\Services\Notifier;
$u = auth();
$unread = $u ? Notifier::unreadCount((int)$u['id']) : 0;
$nav = Hooks::filter('student.nav', [['label' => 'Dashboard', 'url' => '/dashboard', 'icon' => 'home', 'order' => 1], ['label' => 'My courses', 'url' => '/courses', 'icon' => 'book', 'order' => 2]]);
usort($nav, fn($a, $b) => ($a['order'] ?? 50) <=> ($b['order'] ?? 50));
?><!doctype html>
<html lang="en" data-theme="<?= e(Theme::current()) ?>">
<head><?php partial('head', ['title' => $title ?? '']); ?></head>
<body class="app-body<?= isset($bodyClass) ? ' ' . e($bodyClass) : '' ?>">
<a class="skip" href="#main">Skip to content</a>
<header class="topbar">
<div class="topbar-in container">
<?php partial('logo', ['href' => '/dashboard']); ?>
<nav class="topnav" aria-label="Main">
<?php foreach ($nav as $n): ?><a href="<?= e(url($n['url'])) ?>" class="<?= is_path($n['url']) ? 'active' : '' ?>"><?= icon($n['icon'] ?? 'package') ?><span><?= e($n['label']) ?></span></a><?php endforeach; ?>
</nav>
<div class="topbar-actions">
<?php if (can('admin.access')): ?><a class="btn btn-ghost btn-sm hide-sm" href="<?= e(url('/admin')) ?>"><?= icon('grid') ?>Admin</a><?php endif; ?>
<?php partial('theme_toggle'); ?>
<div class="dropdown" data-dropdown>
<button type="button" class="btn-icon bell" data-dropdown-toggle aria-label="Notifications"><?= icon('bell') ?><?php if ($unread): ?><span class="dot"><?= $unread > 9 ? '9+' : $unread ?></span><?php endif; ?></button>
<div class="dropdown-menu dropdown-wide" data-notif-menu>
<div class="dropdown-head"><strong>Notifications</strong><?php if ($unread): ?><button type="button" class="link-btn" data-read-all>Mark all read</button><?php endif; ?></div>
<?php $recentN = $u ? Notifier::recent((int)$u['id'], 6) : []; if (!$recentN): ?><div class="dropdown-empty muted">You're all caught up.</div><?php endif; ?>
<?php foreach ($recentN as $n): ?><a class="notif<?= $n['read_at'] ? '' : ' unread' ?>" href="<?= e(url('/notifications/' . $n['id'] . '/open')) ?>"><span class="notif-ico"><?= icon(['lesson' => 'play', 'enrollment' => 'book', 'access' => 'clock', 'achievement' => 'trophy'][$n['type']] ?? 'bell') ?></span><span><b><?= e($n['title']) ?></b><small><?= e($n['body']) ?></small><small class="muted"><?= e(time_ago($n['created_at'])) ?></small></span></a><?php endforeach; ?>
<a class="dropdown-foot" href="<?= e(url('/notifications')) ?>">View all notifications</a>
</div>
</div>
<div class="dropdown" data-dropdown>
<button type="button" class="avatar-btn" data-dropdown-toggle aria-label="Account menu"><?php partial('avatar', ['u' => $u]); ?></button>
<div class="dropdown-menu">
<div class="dropdown-head"><span><b><?= e($u['name']) ?></b><small class="muted"><?= e($u['email']) ?></small></span></div>
<a href="<?= e(url('/profile')) ?>"><?= icon('user') ?>Profile & settings</a>
<a href="<?= e(url('/notifications')) ?>"><?= icon('bell') ?>Notifications</a>
<?php if (can('admin.access')): ?><a href="<?= e(url('/admin')) ?>"><?= icon('grid') ?>Admin panel</a><?php endif; ?>
<form method="post" action="<?= e(url('/logout')) ?>"><?= csrf_field() ?><button type="submit"><?= icon('logout') ?>Sign out</button></form>
</div>
</div>
</div>
</div>
</header>
<main id="main" class="main container">
<?php partial('flash'); ?>
<?= $content ?>
</main>
<nav class="tabbar" aria-label="Mobile">
<a href="<?= e(url('/dashboard')) ?>" class="<?= is_path('/dashboard') ? 'active' : '' ?>"><?= icon('home') ?><span>Home</span></a>
<a href="<?= e(url('/courses')) ?>" class="<?= is_path('/courses') ? 'active' : '' ?>"><?= icon('book') ?><span>Courses</span></a>
<a href="<?= e(url('/notifications')) ?>" class="<?= is_path('/notifications') ? 'active' : '' ?>"><?= icon('bell') ?><span>Alerts<?php if ($unread): ?> <i class="dot-sm"></i><?php endif; ?></span></a>
<a href="<?= e(url('/profile')) ?>" class="<?= is_path('/profile') ? 'active' : '' ?>"><?= icon('user') ?><span>Profile</span></a>
</nav>
<footer class="footer container muted"><span>© <?= gmdate('Y') ?> <?= e(setting('site_name')) ?></span><?php if (setting('support_email')): ?><a href="mailto:<?= e(setting('support_email')) ?>">Support</a><?php endif; ?></footer>
<?php if (!empty($scripts)): foreach ((array)$scripts as $s): ?><script src="<?= e(asset($s)) ?>" defer></script><?php endforeach; endif; ?>
</body>
</html>
