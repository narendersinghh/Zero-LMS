<?php
$layout = auth() ? 'app' : 'public';
$title = setting('store_title', 'Explore our courses');
$link = fn(array $x) => url('/store', array_filter(array_merge(['q' => $q, 'category' => $cat, 'sort' => $sort === 'featured' ? '' : $sort], $x)));
?>
<?php if (!setting('store_enabled')): ?><div class="alert alert-warning"><?= icon('eye') ?><span>Store preview. The store is turned off, so only staff can see this page. Turn it on in <a href="<?= e(url('/admin/settings/store')) ?>">Settings → Store</a>.</span></div><?php endif; ?>
<section class="explore-hero tint-purple">
<div><span class="eyebrow"><?= icon('sparkles') ?> <?= (int)$count ?> course<?= $count === 1 ? '' : 's' ?></span><h1><?= e($title) ?></h1><p class="lead"><?= e(setting('store_subtitle', '')) ?></p>
<form class="search search-lg" method="get" action="<?= e(url('/store')) ?>"><?= icon('search') ?><input type="search" name="q" value="<?= e($q) ?>" placeholder="Search courses…" aria-label="Search courses"><?php if ($cat): ?><input type="hidden" name="category" value="<?= e($cat) ?>"><?php endif; ?></form></div>
<?= illus('explore', 'explore-illus') ?>
</section>
<div class="store-bar">
<?php if ($cats): ?><div class="store-cats"><a class="<?= $cat === '' ? 'active' : '' ?>" href="<?= e($link(['category' => ''])) ?>">All</a><?php foreach ($cats as $c): ?><a class="<?= $cat === $c ? 'active' : '' ?>" href="<?= e($link(['category' => $c])) ?>"><?= e($c) ?></a><?php endforeach; ?></div><?php else: ?><span></span><?php endif; ?>
<form method="get" action="<?= e(url('/store')) ?>"><?php foreach (['q' => $q, 'category' => $cat] as $k => $v): if ($v !== ''): ?><input type="hidden" name="<?= $k ?>" value="<?= e($v) ?>"><?php endif; endforeach; ?><select name="sort" data-autosubmit aria-label="Sort"><?php foreach (['featured' => 'Featured', 'new' => 'Newest', 'price_low' => 'Price: low to high', 'price_high' => 'Price: high to low'] as $k => $v): ?><option value="<?= $k ?>"<?= selected($k, $sort) ?>><?= $v ?></option><?php endforeach; ?></select></form>
</div>
<?php if (!$courses): ?><?php partial('empty', ['illus' => 'empty', 'heading' => $q || $cat ? 'No courses match' : 'New courses are coming soon', 'text' => $q || $cat ? 'Try another search or category.' : 'Check back shortly.']); ?>
<?php else: ?>
<div class="shop-grid">
<?php foreach ($courses as $c): $p = $c['_price']; $owned = isset($mine[$c['id']]); ?>
<a class="shop-card" href="<?= e(url($owned ? '/courses/' . $c['slug'] : '/c/' . $c['slug'])) ?>">
<?= course_visual($c, 'shop-cv') ?>
<div class="shop-body">
<div class="row between gap-2"><?php if ($c['category']): ?><span class="pill"><?= e($c['category']) ?></span><?php else: ?><span></span><?php endif; ?><?php if ($p['base'] > $p['final'] && $p['base'] > 0): ?><span class="badge badge-danger"><?= (int)round(($p['base'] - $p['final']) / $p['base'] * 100) ?>% off</span><?php endif; ?></div>
<h3><?= e($c['title']) ?></h3>
<?php if ($c['subtitle']): ?><p class="small muted m-0 shop-sub"><?= e(str_limit($c['subtitle'], 110)) ?></p><?php endif; ?>
<div class="shop-meta small muted"><span><?= icon('list') ?><?= (int)$c['lesson_count'] ?> lessons</span><?php if ((int)$c['total_secs']): ?><span><?= icon('clock') ?><?= e(human_duration((int)$c['total_secs'])) ?></span><?php endif; ?><?php if ($c['instructor_name']): ?><span><?= icon('user') ?><?= e($c['instructor_name']) ?></span><?php endif; ?></div>
<div class="shop-foot"><?php if ($owned): ?><span class="badge badge-success"><?= icon('check') ?>Enrolled</span><b>Open</b><?php else: ?><span class="price-sm"><?php if ($p['base'] > $p['final']): ?><s><?= e(money($p['base'], $p['currency'])) ?></s><?php endif; ?></span><b class="price-tag"><?= $p['free'] ? 'Free' : e(money($p['final'], $p['currency'])) ?></b><?php endif; ?></div>
</div>
</a>
<?php endforeach; ?>
</div>
<?php endif; ?>
