<?php
$pal = (array)setting('palette_light', []);
$accent = preg_match('/^#[0-9a-f]{6}$/i', (string)($pal['accent'] ?? '')) ? $pal['accent'] : '#f5d708';
$ink = '#141414';
$site = (string)setting('site_name', 'Zeroes');
$logo = setting('logo_light') ?: setting('logo');
$verify = url('/verify/' . $cert['code'], [], true);
$hf = (string)setting('heading_font', 'Inter Tight');
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Certificate · <?= e($cert['user_name']) ?> · <?= e($cert['course_title']) ?></title>
<meta name="robots" content="noindex,nofollow">
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=<?= e(str_replace(' ', '+', $hf)) ?>:wght@500;700;800&family=Inter:wght@400;500;600&family=Playfair+Display:ital,wght@1,600&display=swap">
<style>
*{box-sizing:border-box}html,body{margin:0}body{font-family:Inter,system-ui,sans-serif;background:#ecebe6;color:<?= $ink ?>;min-height:100vh;display:flex;flex-direction:column;align-items:center;padding:24px 12px}
.bar{width:100%;max-width:1060px;display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:16px;flex-wrap:wrap}
.bar a,.bar button{font:600 14px Inter,sans-serif;border-radius:999px;padding:10px 18px;border:1px solid #d6d4cc;background:#fff;color:<?= $ink ?>;text-decoration:none;cursor:pointer}
.bar button{background:<?= $ink ?>;color:#fff;border-color:<?= $ink ?>}
.wrap{width:100%;max-width:1060px}
.cert{position:relative;aspect-ratio:1.414/1;background:#fffdf7;border-radius:6px;box-shadow:0 30px 80px rgba(0,0,0,.18);overflow:hidden;container-type:inline-size}
.frame{position:absolute;inset:2.2cqw;border:.35cqw solid <?= $ink ?>;border-radius:.6cqw}
.frame::after{content:"";position:absolute;inset:.9cqw;border:.12cqw solid <?= $accent ?>}
.corner{position:absolute;width:22cqw;height:22cqw;border-radius:50%;background:<?= $accent ?>;opacity:.9}
.c1{left:-9cqw;top:-9cqw}.c2{right:-11cqw;bottom:-11cqw;width:28cqw;height:28cqw;opacity:.35}
.inner{position:absolute;inset:6cqw 8cqw;display:flex;flex-direction:column;align-items:center;text-align:center}
.brand{display:flex;align-items:center;gap:1cqw;font:800 2cqw '<?= e($hf) ?>',sans-serif;letter-spacing:-.02em}.brand img{height:3.2cqw;width:auto}
.mark{width:3.2cqw;height:3.2cqw;border-radius:.8cqw;background:<?= $accent ?>;display:grid;place-items:center;font-size:2cqw}
.kicker{margin-top:3.2cqw;font:600 1.25cqw Inter,sans-serif;letter-spacing:.35em;text-transform:uppercase;color:#6b6a63}
h1{margin:.6cqw 0 0;font:800 5.2cqw/1 '<?= e($hf) ?>',sans-serif;letter-spacing:-.03em}
.pres{margin-top:2.4cqw;font-size:1.5cqw;color:#55544e}
.name{margin:.8cqw 0 0;font:italic 600 5cqw/1.1 'Playfair Display',serif;padding:0 2cqw .4cqw;border-bottom:.2cqw solid <?= $accent ?>}
.course{margin-top:2cqw;font-size:1.5cqw;color:#55544e;max-width:70cqw}.course b{display:block;margin-top:.5cqw;font:700 2.4cqw/1.2 '<?= e($hf) ?>',sans-serif;color:<?= $ink ?>}
.foot{margin-top:auto;width:100%;display:flex;justify-content:space-between;align-items:flex-end;gap:2cqw}
.sig{text-align:left;font-size:1.15cqw;color:#55544e;min-width:22cqw}.sig b{display:block;font-size:1.5cqw;color:<?= $ink ?>;border-top:.12cqw solid <?= $ink ?>;padding-top:.6cqw;margin-top:.6cqw}
.sig.r{text-align:right}
.badge{width:12cqw;height:12cqw;flex:none}
.code{font:600 1.05cqw ui-monospace,monospace;color:#6b6a63;margin-top:.4cqw}
.revoked{position:absolute;inset:0;display:grid;place-items:center;font:800 9cqw '<?= e($hf) ?>',sans-serif;color:rgba(220,38,38,.22);transform:rotate(-18deg);pointer-events:none}
.hint{margin-top:14px;font-size:13px;color:#6b6a63;text-align:center}
@media print{@page{size:A4 landscape;margin:0}body{background:#fff;padding:0;display:block}.bar,.hint{display:none}.wrap{max-width:none;width:297mm}.cert{box-shadow:none;border-radius:0;width:297mm;height:210mm;aspect-ratio:auto}}
</style>
</head>
<body>
<div class="bar"><a href="<?= e(url('/certificates')) ?>">← My certificates</a><button type="button" data-print>Print / Save as PDF</button></div>
<div class="wrap"><div class="cert">
<span class="corner c1"></span><span class="corner c2"></span><div class="frame"></div>
<div class="inner">
<div class="brand"><?php if ($logo): ?><img src="<?= e(upload_url($logo)) ?>" alt="<?= e($site) ?>"><?php else: ?><span class="mark">★</span><?= e($site) ?><?php endif; ?></div>
<div class="kicker">Certificate of completion</div>
<h1>Well done!</h1>
<div class="pres">This certifies that</div>
<div class="name"><?= e($cert['user_name']) ?></div>
<div class="course">has successfully completed the course<b><?= e($cert['course_title']) ?></b></div>
<div class="foot">
<div class="sig">Date of completion<b><?= e(dt($cert['issued_at'], 'F j, Y')) ?></b></div>
<svg class="badge" viewBox="0 0 120 120" aria-hidden="true"><g fill="<?= $accent ?>" stroke="<?= $ink ?>" stroke-width="3" stroke-linejoin="round"><path d="M38 78 26 112l16-7 9 14 11-34"/><path d="M82 78l12 34-16-7-9 14-11-34"/></g><circle cx="60" cy="54" r="40" fill="<?= $accent ?>" stroke="<?= $ink ?>" stroke-width="3"/><circle cx="60" cy="54" r="30" fill="#fffdf7" stroke="<?= $ink ?>" stroke-width="2.5" stroke-dasharray="4 4"/><path d="m60 34 6.2 12.6 13.8 2-10 9.8 2.4 13.8L60 65.7l-12.4 6.5L50 58.4l-10-9.8 13.8-2z" fill="<?= $ink ?>"/></svg>
<div class="sig r"><?= $cert['instructor_name'] ? 'Instructor' : 'Issued by' ?><b><?= e($cert['instructor_name'] ?: $site) ?></b><div class="code">Verify: <?= e(preg_replace('#^https?://#', '', $verify)) ?></div></div>
</div>
</div>
<?php if ($cert['revoked_at']): ?><div class="revoked">REVOKED</div><?php endif; ?>
</div></div>
<p class="hint">Tip: choose “Save as PDF” in the print dialog, landscape orientation. Certificate code <?= e($cert['code']) ?>.</p>
<script src="<?= e(module_asset('Certificates', 'print.js')) ?>" defer></script>
</body>
</html>
