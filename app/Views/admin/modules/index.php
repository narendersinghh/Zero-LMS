<?php
$layout = 'admin';
$title = 'Modules';
$crumbs = ['Settings' => '/admin/settings'];
$onCount = count(array_intersect(array_keys($modules), $enabled));
$list = $cat !== '' ? array_filter($modules, fn($m) => ($m['category'] ?? 'Other') === $cat) : $modules;
?>
<section class="mod-intro">
<div>
<h2>Add features when you need them</h2>
<p>Modules are optional features — turn them on or off at any time. Disabling a module hides it from students and staff but keeps its data, so you can switch it back on later.</p>
<div class="mod-stats"><span><?= $onCount ?> of <?= count($modules) ?> enabled</span><span><?= count($cats) ?> categories</span></div>
</div>
<?= illus('explore') ?>
</section>

<?php if (count($cats) > 1): ?>
<div class="tabs tabs-pill mod-cats"><a class="tab<?= $cat === '' ? ' active' : '' ?>" href="?">All</a><?php foreach ($cats as $c): ?><a class="tab<?= $cat === $c ? ' active' : '' ?>" href="?cat=<?= e(urlencode($c)) ?>"><?= e($c) ?></a><?php endforeach; ?></div>
<?php endif; ?>

<div class="mod-grid">
<?php foreach ($list as $key => $m): $on = in_array($key, $enabled, true); $color = preg_replace('/[^a-z]/', '', (string)($m['color'] ?? 'yellow')) ?: 'yellow'; ?>
<article class="mod<?= $on ? ' is-on' : '' ?>">
<div class="mod-top">
<span class="mod-ico" style="background:var(--p-<?= e($color) ?>)"><?= icon((string)($m['icon'] ?? 'package')) ?></span>
<div class="grow"><h3><?= e($m['name']) ?></h3><div class="row"><span class="pill"><?= e($m['category'] ?? 'Other') ?></span><span class="muted small">v<?= e($m['version']) ?></span></div></div>
<form method="post" action="<?= e(url('/admin/modules')) ?>" data-module-toggle><?= csrf_field() ?><input type="hidden" name="module" value="<?= e($key) ?>"><input type="hidden" name="enable" value="<?= $on ? '0' : '1' ?>">
<label class="switch" title="<?= $on ? 'Disable' : 'Enable' ?> <?= e($m['name']) ?>"><input type="checkbox" <?= $on ? 'checked' : '' ?> data-autosubmit aria-label="<?= e(($on ? 'Disable ' : 'Enable ') . $m['name']) ?>"><span></span></label></form>
</div>
<div class="mod-body">
<p><?= e($m['description']) ?></p>
<?php if (!empty($m['features']) && is_array($m['features'])): ?><ul class="mod-feats"><?php foreach ($m['features'] as $f): ?><li><?= icon('check') ?><?= e($f) ?></li><?php endforeach; ?></ul><?php endif; ?>
</div>
<div class="mod-foot">
<span class="badge badge-<?= $on ? 'success' : 'muted' ?>"><?= $on ? icon('check') . 'Enabled' : 'Disabled' ?></span>
<?php if ($on && !empty($m['settings'])): ?><a class="btn btn-ghost btn-sm" href="<?= e(url((string)$m['settings'])) ?>"><?= icon('settings') ?><?= e($m['settings_label'] ?? 'Open') ?></a><?php elseif (!$on): ?><span class="small muted">Turn on to use</span><?php endif; ?>
</div>
</article>
<?php endforeach; ?>
</div>

<div class="card mod-dev small"><?= icon('info') ?><div><b>For developers</b><p class="muted m-0">Add a folder to <code>app/Modules/</code> with <code>module.json</code> and <code>Module.php</code> to build your own module (routes, hooks, views, migrations). See the README section “Extending Zeroes”.</p></div></div>
