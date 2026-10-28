<?php /** Students section switcher: the student list and CSV import live together. */ ?>
<div class="lib-tabs">
<a class="<?= ($section ?? '') === 'students' ? 'active' : '' ?>" href="<?= e(url('/admin/students')) ?>"><span class="lt-ico" style="background:var(--p-green)"><?= icon('users') ?></span><b>All students</b></a>
<?php if (can('imports.run')): ?><a class="<?= ($section ?? '') === 'import' ? 'active' : '' ?>" href="<?= e(url('/admin/import')) ?>"><span class="lt-ico" style="background:var(--p-purple)"><?= icon('upload') ?></span><b>Import CSV</b></a><?php endif; ?>
</div>
