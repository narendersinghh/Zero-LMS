<?php
/** Per-course section bar, shown on every page it links to. Needs $course and $sub (details|curriculum|enrollments|report). */
$cid = (int)$course['id'];
$items = ['details' => ['Details & schedule', '/admin/courses/' . $cid . '/edit', 'courses.manage'], 'curriculum' => ['Curriculum', '/admin/courses/' . $cid . '/curriculum', 'courses.manage'], 'enrollments' => ['Enrollments', '/admin/enrollments?course=' . $cid, 'enrollments.manage'], 'report' => ['Report', '/admin/reports/courses/' . $cid, 'reports.view']];
?>
<div class="subnav"><?php foreach ($items as $k => [$label, $href, $perm]): if (!can($perm)) continue; ?><a class="<?= ($sub ?? '') === $k ? 'active' : '' ?>" href="<?= e(url($href)) ?>"><?= e($label) ?></a><?php endforeach; ?><?php foreach (\App\Core\Hooks::filter('admin.course_tabs', [], $course) as $tl => $tu): ?><a class="<?= is_path(strtok($tu, '?')) ? 'active' : '' ?>" href="<?= e(url($tu)) ?>"><?= e($tl) ?></a><?php endforeach; ?></div>
