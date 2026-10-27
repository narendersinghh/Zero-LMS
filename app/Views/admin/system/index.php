<?php
$layout = 'admin';
$title = 'System & updates';
$cronCmd = 'php ' . BASE_PATH . '/cron.php >/dev/null 2>&1';
$cronUrl = url('/cron/run', ['key' => $s['cron_secret']], true);
?>
<div class="grid-2">
<div class="card stack">
<div class="card-head"><div><h3><?= icon('refresh') ?> Updates</h3><small class="muted">Installed version <b>v<?= e($version) ?></b><?= $lastCheck ? ' · last checked ' . e(time_ago($lastCheck)) : '' ?></small></div></div>
<?php if (is_array($last) && !empty($last['latest'])): ?>
<div class="update-box<?= !empty($last['available']) ? ' available' : '' ?>">
<div><b><?= !empty($last['available']) ? 'Update available: ' . e($last['latest']) : 'Up to date (' . e($last['latest']) . ')' ?></b><?php if (!empty($last['published_at'])): ?><small class="muted d-block">Published <?= e(dt($last['published_at'])) ?></small><?php endif; ?></div>
<?php if (!empty($last['notes'])): ?><pre class="notes"><?= e(str_limit($last['notes'], 1200)) ?></pre><?php endif; ?>
</div>
<?php endif; ?>
<?php if (can('updates.run')): ?>
<div class="row gap-2 wrap">
<form method="post" action="<?= e(url('/admin/system/update-check')) ?>"><?= csrf_field() ?><button class="btn btn-ghost"><?= icon('search') ?>Check for updates</button></form>
<form method="post" action="<?= e(url('/admin/system/update')) ?>" data-loading><?= csrf_field() ?><button class="btn btn-primary" data-confirm="Install the latest version now? A backup of the current code is created first. The site is in maintenance mode for a few seconds."><?= icon('download') ?>Install update</button></form>
</div>
<details class="mt-2"><summary class="small">Update source</summary>
<form method="post" action="<?= e(url('/admin/system/updater')) ?>" class="stack mt-3"><?= csrf_field() ?>
<div class="field"><label>GitHub repository</label><input name="update_repo" value="<?= e($s['update_repo']) ?>" placeholder="owner/repo" required></div>
<div class="grid-2"><div class="field"><label>Channel</label><select name="update_channel"><option value="release"<?= selected('release', $s['update_channel']) ?>>Latest release (stable)</option><option value="branch"<?= selected('branch', $s['update_channel']) ?>>Branch head (latest commit)</option></select></div><div class="field"><label>Branch</label><input name="update_branch" value="<?= e($s['update_branch']) ?>"></div></div>
<div class="field"><label>Access token (private repos)</label><input type="password" name="update_token" placeholder="<?= $s['update_token'] ? '•••••••• (saved, encrypted)' : 'github_pat_… (read-only Contents permission)' ?>" autocomplete="new-password"><?php if ($s['update_token']): ?><label class="check mt-1"><input type="checkbox" name="clear_token" value="1"><span>Remove token</span></label><?php endif; ?></div>
<div><button class="btn btn-ghost btn-sm">Save source</button></div>
</form></details>
<?php if ($backups): ?>
<details><summary class="small">Code backups (<?= count($backups) ?>)</summary>
<div class="mt-2"><?php foreach ($backups as $b): ?><form method="post" action="<?= e(url('/admin/system/rollback')) ?>" class="list-row"><?= csrf_field() ?><input type="hidden" name="backup" value="<?= e($b['name']) ?>"><span class="grow small"><b><?= e($b['name']) ?></b><small class="muted d-block"><?= e(human_size($b['size'])) ?> · <?= e(dtime($b['time'])) ?></small></span><button class="btn btn-ghost btn-sm" data-confirm="Restore code from this backup? Database changes are not reverted.">Restore</button></form><?php endforeach; ?></div>
</details>
<?php endif; ?>
<?php endif; ?>
</div>

<div class="card stack">
<div class="card-head"><h3><?= icon('clock') ?> Scheduled tasks (cron)</h3></div>
<p class="small muted m-0">Sends queued emails, lesson-unlock notifications and expiry reminders. Add this in cPanel → Cron Jobs, every 5 minutes:</p>
<div class="copy-box"><code><?= e('*/5 * * * * ' . $cronCmd) ?></code><button type="button" class="btn-icon" data-copy="<?= e('*/5 * * * * ' . $cronCmd) ?>" title="Copy"><?= icon('copy') ?></button></div>
<p class="small muted m-0">No CLI cron? Use an external pinger (e.g. cron-job.org) with this secret URL:</p>
<div class="copy-box"><code><?= e($cronUrl) ?></code><button type="button" class="btn-icon" data-copy="<?= e($cronUrl) ?>" title="Copy"><?= icon('copy') ?></button></div>
<div class="row gap-2 wrap"><form method="post" action="<?= e(url('/admin/system/cron')) ?>"><?= csrf_field() ?><button class="btn btn-ghost btn-sm"><?= icon('play') ?>Run now</button></form><span class="small muted">Last run: <?= $s['cron_last_run'] ? e(time_ago($s['cron_last_run'])) : 'never' ?></span></div>
</div>
</div>

<div class="grid-2 mt-4">
<div class="card stack">
<div class="card-head"><h3><?= icon('package') ?> Modules</h3></div>
<?php if (!$modules): ?><p class="small muted">No modules installed. Add folders to <code>app/Modules/</code>.</p><?php else: ?>
<form method="post" action="<?= e(url('/admin/system/modules')) ?>" class="stack"><?= csrf_field() ?>
<?php foreach ($modules as $m): ?><label class="check"><input type="checkbox" name="modules[]" value="<?= e($m['key']) ?>"<?= checked(in_array($m['key'], $enabled, true)) ?>><span><b><?= e($m['name']) ?></b> <small class="muted">v<?= e($m['version']) ?></small><small class="muted d-block"><?= e($m['description']) ?></small></span></label><?php endforeach; ?>
<div><button class="btn btn-ghost btn-sm">Save modules</button></div>
</form>
<?php endif; ?>
</div>
<div class="card stack">
<div class="card-head"><h3><?= icon('settings') ?> Maintenance</h3></div>
<div class="row gap-2 wrap">
<form method="post" action="<?= e(url('/admin/system/maintenance')) ?>"><?= csrf_field() ?><button class="btn btn-ghost btn-sm"><?= icon('lock') ?><?= $maintenance ? 'Disable' : 'Enable' ?> maintenance mode</button></form>
<form method="post" action="<?= e(url('/admin/system/cache')) ?>"><?= csrf_field() ?><button class="btn btn-ghost btn-sm"><?= icon('refresh') ?>Clear caches</button></form>
<form method="post" action="<?= e(url('/admin/system/migrate')) ?>"><?= csrf_field() ?><button class="btn btn-ghost btn-sm"><?= icon('layers') ?>Run migrations<?= $pending ? ' (' . count($pending) . ' pending)' : '' ?></button></form>
</div>
<div class="table-wrap"><table class="table table-sm"><tbody>
<?php foreach ($info as $k => $v): ?><tr><td class="muted"><?= e($k) ?></td><td><?= e($v) ?></td></tr><?php endforeach; ?>
<?php foreach ($writable as $p => $ok): ?><tr><td class="muted"><code><?= e($p) ?></code> writable</td><td><?= $ok ? '<span class="badge badge-success">Yes</span>' : '<span class="badge badge-' . (in_array($p, ['app', 'assets', '.env'], true) ? 'warning' : 'danger') . '">No</span>' ?></td></tr><?php endforeach; ?>
</tbody></table></div>
</div>
</div>
