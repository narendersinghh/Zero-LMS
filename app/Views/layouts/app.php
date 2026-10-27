<?php
use App\Core\Hooks;
use App\Core\Theme;
use App\Services\Notifier;
use App\Services\Progress;
$u = auth();
$uid = (int)$u['id'];
$unread = Notifier::unreadCount($uid);
$learn = [['label' => 'Dashboard', 'url' => '/dashboard', 'icon' => 'home'], ['label' => 'My courses', 'url' => '/courses', 'icon' => 'book'], ['label' => 'Explore', 'url' => '/explore', 'icon' => 'search']];
$extra = Hooks::filter('student.nav', []);
usort($extra, fn($a, $b) => ($a['order'] ?? 50) <=> ($b['order'] ?? 50));
$account = [['label' => 'Orders', 'url' => '/orders', 'icon' => 'key'], ['label' => 'Notifications', 'url' => '/notifications', 'icon' => 'bell', 'count' => $unread], ['label' => 'Profile', 'url' => '/profile', 'icon' => 'user']];
$streak = Progress::streak($uid);
$navLink = function (array $n) {
    return '<a href="' . e(url($n['url'])) . '" class="' . (is_path($n['url']) ? 'active' : '') . '">' . icon($n['icon'] ?? 'package') . '<span>' . e($n['label']) . '</span>' . (!empty($n['count']) ? '<span class="count">' . ($n['count'] > 9 ? '9+' : (int)$n['count']) . '</span>' : '') . '</a>';};
?><!doctype html>
<html lang="en" data-theme="<?= e(Theme::current()) ?>">
<head><?php partial('head', ['title' => $title ?? '']); ?></head>
<body class="app-body<?= isset($bodyClass) ? ' ' . e($bodyClass) : '' ?>">
<a class="skip" href="#main">Skip to content</a>
<div class="app-shell">
<aside class="side" aria-label="Main navigation">
<?php partial('logo', ['href' => '/dashboard']); ?>
<span class="side-label">Learn</span>
<nav class="side-nav"><?php foreach ($learn as $n) echo $navLink($n); ?></nav>
<?php if ($extra): ?><span class="side-label">Tools</span><nav class="side-nav"><?php foreach ($extra as $n) echo $navLink($n); ?></nav><?php endif; ?>
<span class="side-label">Account</span>
<nav class="side-nav"><?php foreach ($account as $n) echo $navLink($n); ?><?php if (can('admin.access')): ?><a href="<?= e(url('/admin')) ?>"><?= icon('grid') ?><span>Admin panel</span></a><?php endif; ?></nav>
<a class="side-card" href="<?= e(url('/dashboard')) ?>"><b><?= (int)$streak ?> day<?= $streak === 1 ? '' : 's' ?></b><small><?= $streak ? 'Learning streak, keep it alive today!' : 'Watch a lesson today to start a streak.' ?></small><img src="<?= e(asset('img/illus/streak.svg')) ?>" alt=""></a>
</aside>
<div class="app-main">
<header class="app-top">
<?php partial('logo', ['href' => '/dashboard']); ?>
<form class="top-search" action="<?= e(url('/courses')) ?>" method="get" role="search"><?= icon('search') ?><input type="search" name="q" placeholder="Search your courses…" aria-label="Search your courses" value="<?= e($_GET['q'] ?? '') ?>"></form>
<div class="top-actions">
<?php partial('theme_toggle'); ?>
<div class="dropdown" data-dropdown>
<button type="button" class="btn-icon bell" data-dropdown-toggle aria-label="Notifications"><?= icon('bell') ?><?php if ($unread): ?><span class="dot"><?= $unread > 9 ? '9+' : $unread ?></span><?php endif; ?></button>
<div class="dropdown-menu dropdown-wide">
<div class="dropdown-head"><strong>Notifications</strong><?php if ($unread): ?><button type="button" class="link-btn" data-read-all>Mark all read</button><?php endif; ?></div>
<?php $recentN = Notifier::recent($uid, 6); if (!$recentN): ?><div class="dropdown-empty muted">You're all caught up ✨</div><?php endif; ?>
<?php foreach ($recentN as $n): ?><a class="notif<?= $n['read_at'] ? '' : ' unread' ?>" href="<?= e(url('/notifications/' . $n['id'] . '/open')) ?>"><span class="notif-ico"><?= icon(['lesson' => 'play', 'enrollment' => 'book', 'access' => 'clock', 'achievement' => 'trophy', 'order' => 'key'][$n['type']] ?? 'bell') ?></span><span><b><?= e($n['title']) ?></b><small><?= e($n['body']) ?></small><small class="muted"><?= e(time_ago($n['created_at'])) ?></small></span></a><?php endforeach; ?>
<a class="dropdown-foot" href="<?= e(url('/notifications')) ?>">View all notifications</a>
</div>
</div>
<div class="dropdown" data-dropdown>
<button type="button" class="avatar-btn" data-dropdown-toggle aria-label="Account menu"><?php partial('avatar', ['u' => $u]); ?></button>
<div class="dropdown-menu">
<div class="dropdown-head"><span><b><?= e($u['name']) ?></b><small class="muted"><?= e($u['email']) ?></small></span></div>
<a href="<?= e(url('/profile')) ?>"><?= icon('user') ?>Profile & settings</a>
<a href="<?= e(url('/orders')) ?>"><?= icon('key') ?>Orders & payments</a>
<?php foreach ($extra as $n): ?><a href="<?= e(url($n['url'])) ?>"><?= icon($n['icon'] ?? 'package') ?><?= e($n['label']) ?></a><?php endforeach; ?>
<?php if (can('admin.access')): ?><a href="<?= e(url('/admin')) ?>"><?= icon('grid') ?>Admin panel</a><?php endif; ?>
<form method="post" action="<?= e(url('/logout')) ?>"><?= csrf_field() ?><button type="submit"><?= icon('logout') ?>Sign out</button></form>
</div>
</div>
</div>
</header>
<main id="main" class="app-content">
<?php partial('flash'); ?>
<?= $content ?>
</main>
<footer class="app-foot"><span>© <?= gmdate('Y') ?> <?= e(setting('site_name')) ?></span><?php if (setting('support_email')): ?><a href="mailto:<?= e(setting('support_email')) ?>">Need help? <?= e(setting('support_email')) ?></a><?php endif; ?></footer>
</div>
</div>
<nav class="tabbar" aria-label="Mobile navigation">
<?php foreach ([['/dashboard', 'home', 'Home'], ['/courses', 'book', 'Courses'], ['/explore', 'search', 'Explore'], ['/notifications', 'bell', 'Alerts'], ['/profile', 'user', 'Profile']] as [$h, $i, $l]): ?>
<a href="<?= e(url($h)) ?>" class="<?= is_path($h) ? 'active' : '' ?>"><span class="tb-ico"><?= icon($i) ?></span><span><?= $l ?></span><?php if ($h === '/notifications' && $unread): ?><i class="dot-sm"></i><?php endif; ?></a>
<?php endforeach; ?>
</nav>
<?php if (!empty($scripts)): foreach ((array)$scripts as $s): ?><script src="<?= e(asset($s)) ?>" defer></script><?php endforeach; endif; ?>
</body>
</html>
