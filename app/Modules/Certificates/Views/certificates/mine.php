<?php $layout = 'app'; $title = 'Certificates'; ?>
<div class="page-head"><div><h1>My certificates</h1><p class="muted">Finish every lesson of a course to earn a certificate you can print and share.</p></div></div>
<?php if (!$certs): ?>
<?php partial('empty', ['illus' => 'empty', 'icon' => 'trophy', 'heading' => 'No certificates yet', 'text' => 'Complete all lessons in a course and your certificate appears here automatically.', 'action' => '<a class="btn btn-primary" href="' . e(url('/courses')) . '">Continue learning</a>']); ?>
<?php else: ?>
<div class="cert-grid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:1.1rem">
<?php foreach ($certs as $i => $ct): $tint = ['yellow', 'blue', 'green', 'purple', 'pink', 'orange'][$i % 6]; ?>
<div class="card tint-<?= $tint ?> stack" style="gap:.6rem">
<span class="pill">Certificate of completion</span>
<h3 class="m-0"><?= e($ct['course_title']) ?></h3>
<p class="small muted m-0">Issued <?= e(dt($ct['issued_at'])) ?> · <code><?= e($ct['code']) ?></code></p>
<div class="row gap-2 wrap"><a class="btn btn-dark btn-sm" href="<?= e(url('/certificates/' . $ct['code'])) ?>"><?= icon('eye') ?>View & print</a><button type="button" class="btn btn-ghost btn-sm" data-copy="<?= e(url('/verify/' . $ct['code'], [], true)) ?>"><?= icon('copy') ?>Copy verify link</button></div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>
