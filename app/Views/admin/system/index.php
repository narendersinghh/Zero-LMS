<?php
$layout = 'admin';
$title = 'System & updates';
$avail = is_array($last) && !empty($last['available']);
$repo = (string)($s['update_repo'] ?? '');
$cronCmd = '*/5 * * * * php ' . BASE_PATH . '/cron.php >/dev/null 2>&1';
$cronUrl = url('/cron/run', ['key' => $s['cron_secret']], true);
$healthTxt = $score['bad'] ? $score['bad'] . ' problem' . ($score['bad'] === 1 ? '' : 's') : ($score['warn'] ? $score['warn'] . ' suggestion' . ($score['warn'] === 1 ? '' : 's') : 'All systems healthy');
?>
<section class="sys-hero<?= $avail ? ' is-update' : '' ?>">
<div class="sys-ver">
<span class="sys-badge"><?= icon($avail ? 'download' : 'shield') ?></span>
<div>
<span class="eyebrow"><?= $avail ? 'Update available' : 'Zeroes LMS' ?></span>
<h2><?= $avail ? 'Version ' . e($last['latest']) . ' is ready' : 'You\'re on v' . e($version) ?></h2>
<p><?= $avail ? 'Installed: v' . e($version) . '. A code backup is created automatically before updating.' : ($lastCheck ? 'Up to date as of ' . e(time_ago($lastCheck)) . '.' : 'Check GitHub to see if a newer version is available.') ?></p>
<div class="sys-meta">
<span class="pill"><?= icon('package') ?><?= e($repo ?: 'No repository set') ?></span>
<span class="pill"><?= ($s['update_channel'] ?? 'release') === 'branch' ? 'Branch: ' . e($s['update_branch'] ?? 'main') : 'Stable releases' ?></span>
<?php if (!empty($last['published_at'])): ?><span class="pill"><?= icon('calendar') ?>Published <?= e(dt($last['published_at'])) ?></span><?php endif; ?>
</div>
</div>
</div>
<?php if (can('updates.run')): ?>
<div class="sys-btns">
<form method="post" action="<?= e(url('/admin/system/update-check')) ?>" data-loading><?= csrf_field() ?><button class="btn btn-ghost"><?= icon('refresh') ?>Check now</button></form>
<?php if ($avail): ?><form method="post" action="<?= e(url('/admin/system/update')) ?>" data-loading><?= csrf_field() ?><button class="btn btn-primary" data-confirm="Install v<?= e($last['latest']) ?> now? A backup of the current code is created first and the site is in maintenance mode for a few seconds."><?= icon('download') ?>Install update</button></form><?php endif; ?>
<?php if ($repo): ?><a class="btn btn-ghost" href="https://github.com/<?= e($repo) ?>/releases" target="_blank" rel="noopener"><?= icon('external') ?>GitHub</a><?php endif; ?>
</div>
<?php endif; ?>
<?php if ($avail && !empty($last['notes'])): ?><pre class="sys-notes"><?= e(str_limit($last['notes'], 1500)) ?></pre><?php endif; ?>
</section>

<section class="sys-section">
<div class="sys-section-head"><h3 class="m-0">Health</h3><span class="badge badge-<?= $score['bad'] ? 'danger' : ($score['warn'] ? 'warning' : 'success') ?>"><?= e($healthTxt) ?></span></div>
<div class="health">
<?php foreach ($health as [$state, $name, $detail]): ?><div class="hl <?= e($state) ?>"><span class="hl-dot"></span><div class="grow"><b><?= e($name) ?></b><small><?= e($detail) ?></small></div></div><?php endforeach; ?>
</div>
</section>

<div class="sys-grid">
<div class="stack-lg">
<div class="card" id="cron">
<div class="card-head"><div><h3><?= icon('clock') ?> Scheduled tasks</h3><small class="muted">Sends queued emails, lesson-unlock notifications and expiry reminders.</small></div><span class="badge badge-<?= $cronAge === null ? 'danger' : ($cronAge > 86400 ? 'warning' : 'success') ?>"><?= $cronLast ? e(time_ago($cronLast)) : 'Never run' ?></span></div>
<div class="cron-box">
<div class="field"><label>cPanel → Cron Jobs (every 5 minutes)</label><div class="copy-box"><code><?= e($cronCmd) ?></code><button type="button" class="btn-icon" data-copy="<?= e($cronCmd) ?>" title="Copy"><?= icon('copy') ?></button></div></div>
<div class="field"><label>No cron access? Ping this secret URL (e.g. cron-job.org)</label><div class="copy-box"><code><?= e($cronUrl) ?></code><button type="button" class="btn-icon" data-copy="<?= e($cronUrl) ?>" title="Copy"><?= icon('copy') ?></button></div></div>
<p class="small muted m-0"><?= !empty($s['web_cron']) ? 'Backup: tasks also run automatically after page visits.' : 'Web cron is off — a real cron job is required.' ?></p>
</div>
</div>
<div class="card">
<div class="card-head"><h3><?= icon('settings') ?> Maintenance tools</h3><a href="<?= e(url('/admin/modules')) ?>">Modules (<?= e($moduleCount) ?>) <?= icon('arrow-right') ?></a></div>
<div class="tools">
<form method="post" action="<?= e(url('/admin/system/maintenance')) ?>" class="tool<?= $maintenance ? ' on' : '' ?>"><?= csrf_field() ?><button <?= $maintenance ? '' : 'data-confirm="Turn on maintenance mode? Students will see a maintenance page until you turn it off."' ?>><span class="tool-ico" style="background:var(--p-orange)"><?= icon('lock') ?></span><b><?= $maintenance ? 'Turn off maintenance' : 'Maintenance mode' ?></b><small><?= $maintenance ? 'Site is offline for students right now.' : 'Temporarily take the site offline for students.' ?></small></button></form>
<form method="post" action="<?= e(url('/admin/system/cache')) ?>" class="tool"><?= csrf_field() ?><button><span class="tool-ico" style="background:var(--p-blue)"><?= icon('refresh') ?></span><b>Clear caches</b><small>Use after editing files or settings by hand.</small></button></form>
<form method="post" action="<?= e(url('/admin/system/migrate')) ?>" class="tool"><?= csrf_field() ?><button><span class="tool-ico" style="background:var(--p-purple)"><?= icon('layers') ?></span><b>Run migrations<?= $pending ? ' (' . count($pending) . ')' : '' ?></b><small><?= $pending ? 'Database changes are waiting.' : 'Database is up to date.' ?></small></button></form>
<form method="post" action="<?= e(url('/admin/system/cron')) ?>" class="tool"><?= csrf_field() ?><button><span class="tool-ico" style="background:var(--p-green)"><?= icon('play') ?></span><b>Run scheduled tasks</b><small>Send queued emails & unlock notices now.</small></button></form>
</div>
</div>
</div>
<div class="stack-lg">
<?php if (can('updates.run')): ?>
<div class="card">
<div class="card-head"><div><h3><?= icon('package') ?> Update source</h3><small class="muted">Where Zeroes looks for new versions.</small></div></div>
<form method="post" action="<?= e(url('/admin/system/updater')) ?>" class="stack"><?= csrf_field() ?>
<div class="field"><label>GitHub repository</label><input name="update_repo" value="<?= e($repo) ?>" placeholder="owner/repo" required></div>
<div class="grid-2"><div class="field"><label>Channel</label><select name="update_channel"><option value="release"<?= selected('release', $s['update_channel']) ?>>Stable releases</option><option value="branch"<?= selected('branch', $s['update_channel']) ?>>Latest commit on a branch</option></select></div><div class="field"><label>Branch</label><input name="update_branch" value="<?= e($s['update_branch']) ?>"></div></div>
<div class="field"><label>Access token <span class="muted">(private repositories only)</span></label><input type="password" name="update_token" placeholder="<?= $s['update_token'] ? '•••••••• saved (encrypted)' : 'github_pat_… with read-only Contents access' ?>" autocomplete="new-password"><?php if ($s['update_token']): ?><label class="check mt-1"><input type="checkbox" name="clear_token" value="1"><span>Remove saved token</span></label><?php endif; ?></div>
<div class="actions"><button class="btn btn-primary"><?= icon('check') ?>Save source</button></div>
</form>
</div>
<div class="card p-0">
<div class="card-head" style="padding:var(--s5) var(--s6) 0"><div><h3><?= icon('archive') ?> Code backups</h3><small class="muted">Created automatically before each update (last 5 kept).</small></div></div>
<?php if (!$backups): ?><p class="muted small" style="padding:0 var(--s6) var(--s5)">No backups yet.</p><?php else: ?>
<div class="table-wrap"><table class="table table-sm"><thead><tr><th>Backup</th><th>Size</th><th>Created</th><th></th></tr></thead><tbody>
<?php foreach ($backups as $b): ?><tr><td class="small mono-wrap"><?= e($b['name']) ?></td><td class="small nowrap"><?= e(human_size($b['size'])) ?></td><td class="small muted nowrap"><?= e(dtime($b['time'])) ?></td><td class="right"><form method="post" action="<?= e(url('/admin/system/rollback')) ?>"><?= csrf_field() ?><input type="hidden" name="backup" value="<?= e($b['name']) ?>"><button class="btn btn-ghost btn-sm" data-confirm="Restore code from <?= e($b['name']) ?>? Database changes are not reverted."><?= icon('refresh') ?>Restore</button></form></td></tr><?php endforeach; ?>
</tbody></table></div>
<?php endif; ?>
</div>
<?php endif; ?>
</div>
</div>
