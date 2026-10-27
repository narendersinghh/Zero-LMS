<?php /** "Courses & videos" section switcher: courses and the reusable video library live together. */ ?>
<div class="lib-tabs">
<a class="<?= ($section ?? '') === 'courses' ? 'active' : '' ?>" href="<?= e(url('/admin/courses')) ?>"><span class="lt-ico" style="background:var(--p-yellow)"><?= icon('book') ?></span><span><b>Courses</b><small>Structure, schedule, pricing</small></span></a>
<?php if (can('videos.manage')): ?><a class="<?= ($section ?? '') === 'videos' ? 'active' : '' ?>" href="<?= e(url('/admin/videos')) ?>"><span class="lt-ico" style="background:var(--p-blue)"><?= icon('video') ?></span><span><b>Video library</b><small>Upload once, reuse in any lesson</small></span></a><?php endif; ?>
</div>
