<?php use App\Modules\Resources\Controller as R; ?>
<section class="card mt-4" id="resources">
<div class="card-head"><div><span class="eyebrow"><?= icon('download') ?> Resources</span><h3 class="mt-1">Downloads for this lesson</h3></div></div>
<div class="res-list">
<?php foreach ($rows as $r): ?>
<a class="list-row res-row" href="<?= e(url('/resources/' . $r['id'] . '/download')) ?>"<?= $r['url'] ? ' target="_blank" rel="noopener"' : '' ?>>
<span class="list-ico"><?= icon(R::icon($r['mime'], $r['url'])) ?></span>
<span class="grow"><b><?= e($r['title']) ?></b><small class="muted"><?= $r['url'] ? e(parse_url($r['url'], PHP_URL_HOST)) : e(strtoupper(pathinfo((string)$r['original_name'], PATHINFO_EXTENSION)) . ' · ' . human_size($r['size'])) ?></small></span>
<span class="btn btn-ghost btn-sm"><?= icon($r['url'] ? 'external' : 'download') ?><span class="hide-xs"><?= $r['url'] ? 'Open' : 'Download' ?></span></span>
</a>
<?php endforeach; ?>
</div>
</section>
