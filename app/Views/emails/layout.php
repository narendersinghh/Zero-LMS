<?php
$p = (array)setting('palette_light', []);
$accent = $p['accent'] ?? '#f5d708';
$contrast = $p['accent_contrast'] ?? '#121212';
$logo = setting('logo_light') ?: setting('logo');
$name = (string)setting('site_name', 'Zeroes');
$body = str_replace('class="btn"', 'class="btn" style="display:inline-block;background:' . e($accent) . ';color:' . e($contrast) . ';text-decoration:none;font-weight:700;padding:14px 26px;border-radius:10px"', $body);
?><!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?= e($subject) ?></title>
<style>body{margin:0;background:#f4f4f1;font-family:-apple-system,'Segoe UI',Inter,Roboto,Helvetica,Arial,sans-serif;color:#18181b}a{color:#7a6a00}h1{font-size:24px;line-height:1.25;margin:0 0 16px;color:#0a0a0b}p{font-size:15px;line-height:1.65;margin:0 0 16px}.btn-wrap{margin:24px 0}@media(max-width:600px){.card{padding:28px 22px!important}}</style></head>
<body><div style="display:none;max-height:0;overflow:hidden"><?= e($subject) ?></div>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f1;padding:32px 12px"><tr><td align="center">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:580px">
<tr><td style="padding:0 6px 20px"><?php if ($logo): ?><img src="<?= e(base_url() . '/uploads/' . $logo) ?>" alt="<?= e($name) ?>" style="max-height:36px"><?php else: ?><span style="display:inline-block;background:<?= e($accent) ?>;color:<?= e($contrast) ?>;font-weight:800;border-radius:8px;padding:6px 10px;font-size:15px"><?= e($name) ?></span><?php endif; ?></td></tr>
<tr><td class="card" style="background:#fff;border-radius:16px;padding:40px;border:1px solid #e7e7e2;border-top:4px solid <?= e($accent) ?>"><?= $body ?></td></tr>
<tr><td style="padding:22px 6px;color:#71717a;font-size:12px;line-height:1.6">You're receiving this email because you have an account on <?= e($name) ?>.<?php if (setting('support_email')): ?> Questions? Contact <a href="mailto:<?= e(setting('support_email')) ?>" style="color:#71717a"><?= e(setting('support_email')) ?></a>.<?php endif; ?><br>© <?= gmdate('Y') ?> <?= e($name) ?></td></tr>
</table></td></tr></table></body></html>
