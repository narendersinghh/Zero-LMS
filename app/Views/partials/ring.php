<?php $pct = max(0, min(100, (int)$pct)); $sz = (int)($size ?? 56); $r = 15.915; ?>
<span class="ring" style="--sz:<?= $sz ?>px" role="img" aria-label="<?= $pct ?>% complete"><svg viewBox="0 0 36 36"><circle class="ring-bg" cx="18" cy="18" r="<?= $r ?>"/><circle class="ring-fg" cx="18" cy="18" r="<?= $r ?>" stroke-dasharray="<?= $pct ?> 100"/></svg><b><?= $pct ?>%</b></span>
