<?php $layout = 'admin'; $title = 'Settings'; include \App\Core\View::find('admin/settings/_tabs'); $storeUrl = url('/store', [], true); ?>
<div class="settings-hero tint-green"><span class="sh-ico"><?= icon('grid') ?></span><div><h2>Store</h2><p>A public page listing your courses. Visitors open a course page, create an account and enroll, paying first when the course has a price.</p></div><span class="badge badge-<?= $s['store_enabled'] ? 'success' : 'muted' ?>"><?= $s['store_enabled'] ? 'Live' : 'Off' ?></span></div>
<form method="post" action="<?= e(url('/admin/settings/store')) ?>" class="stack-lg" data-loading>
<?= csrf_field() ?>
<section class="set-sec">
<div class="set-desc"><h3>Visibility</h3><p>When the store is off, course links you share still work. Only the listing page is hidden.</p></div>
<div class="card set-fields">
<div class="set-row"><div><b>Enable store</b><small>Publishes the course listing at the link below.</small></div><label class="switch"><input type="checkbox" name="store_enabled" value="1"<?= checked($s['store_enabled']) ?>><span></span></label></div>
<div class="set-row"><div><b>Use as home page</b><small>Visitors who are not signed in land on the store instead of the sign-in page.</small></div><label class="switch"><input type="checkbox" name="store_home" value="1"<?= checked($s['store_home']) ?>><span></span></label></div>
<div class="copy-box"><code><?= e($storeUrl) ?></code><button type="button" class="btn-icon" data-copy="<?= e($storeUrl) ?>" title="Copy"><?= icon('copy') ?></button><a class="btn-icon" href="<?= e($storeUrl) ?>" target="_blank" rel="noopener" title="Open"><?= icon('external') ?></a></div>
</div>
</section>
<section class="set-sec">
<div class="set-desc"><h3>Heading</h3><p>Shown at the top of the store page.</p></div>
<div class="card set-fields">
<div class="field"><label for="store_title">Title</label><input id="store_title" name="store_title" value="<?= e($s['store_title']) ?>" maxlength="80" required></div>
<div class="field"><label for="store_subtitle">Subtitle</label><input id="store_subtitle" name="store_subtitle" value="<?= e($s['store_subtitle']) ?>" maxlength="200"></div>
</div>
</section>
<section class="set-sec">
<div class="set-desc"><h3>Courses in the store</h3><p>Ticked courses get a public page and appear in the store once published. Set prices in each course's Details & schedule.</p></div>
<div class="card set-fields p-0">
<?php if (!$courses): ?><p class="muted small m-0" style="padding:var(--s5)">No courses yet.</p><?php endif; ?>
<?php foreach ($courses as $c): $p = \App\Services\Payments\Payments::price($c); ?>
<label class="store-row"><input type="checkbox" name="listed[]" value="<?= (int)$c['id'] ?>"<?= checked($c['is_public']) ?>><span class="grow"><b><?= e($c['title']) ?></b><small class="muted"><?= $c['status'] === 'published' ? 'Published' : 'Draft, publish it to show in the store' ?><?= $c['category'] ? ' · ' . e($c['category']) : '' ?></small></span><span class="price-tag small"><?= $p['free'] ? 'Free' : e(money($p['final'], $p['currency'])) ?></span><a class="btn-icon" href="<?= e(url('/admin/courses/' . $c['id'] . '/edit')) ?>" title="Edit course"><?= icon('edit') ?></a></label>
<?php endforeach; ?>
</div>
</section>
<div class="set-actions"><a class="btn btn-ghost" href="<?= e(url('/store')) ?>" target="_blank" rel="noopener"><?= icon('eye') ?>Preview store</a><button class="btn btn-primary"><?= icon('check') ?>Save store settings</button></div>
</form>
<?php include \App\Core\View::find('admin/settings/_end'); ?>
