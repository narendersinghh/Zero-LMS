<?php
use App\Core\Defaults;
use App\Core\Theme;
$layout = 'admin';
$title = 'Settings';
include \App\Core\View::find('admin/settings/_tabs');
$labels = ['bg' => 'Page background', 'surface' => 'Cards / surfaces', 'surface_2' => 'Raised surface', 'border' => 'Borders', 'text' => 'Text', 'muted' => 'Muted text', 'accent' => 'Accent / buttons', 'accent_contrast' => 'Text on accent', 'accent_text' => 'Accent text & links', 'success' => 'Success', 'warning' => 'Warning', 'danger' => 'Danger', 'input_bg' => 'Inputs', 'sidebar_bg' => 'Sidebar', 'hero_from' => 'Hero gradient start', 'hero_to' => 'Hero gradient end'];
?>
<form method="post" action="<?= e(url('/admin/settings/branding')) ?>" enctype="multipart/form-data" class="stack-lg" data-loading data-theme-editor>
<?= csrf_field() ?>
<div class="card stack">
<div class="card-head"><div><h3>Logo & favicon</h3><small class="muted">PNG, JPG, WebP or SVG. Logos display at 32px height.</small></div></div>
<div class="grid-3">
<?php foreach (['logo' => 'Logo (dark theme)', 'logo_light' => 'Logo (light theme)', 'favicon' => 'Favicon'] as $k => $lbl): ?>
<div class="field"><label><?= e($lbl) ?></label>
<div class="logo-preview <?= $k === 'logo_light' ? 'on-light' : '' ?>" data-image-preview<?= $s[$k] ? ' style="background-image:url(\'' . e(upload_url($s[$k])) . '\')"' : '' ?>><?= $s[$k] ? '' : '<span class="muted small">None</span>' ?></div>
<input type="file" name="<?= $k ?>" accept="image/png,image/jpeg,image/webp,image/svg+xml,image/x-icon" data-preview-input>
<?php if ($s[$k]): ?><label class="check"><input type="checkbox" name="remove_<?= $k ?>" value="1"><span>Remove</span></label><?php endif; ?></div>
<?php endforeach; ?>
</div>
</div>
<div class="card stack">
<div class="card-head"><h3>Typography & shape</h3></div>
<div class="grid-3">
<div class="field"><label>Heading font</label><select name="heading_font"><?php foreach (Theme::FONTS as $f): ?><option<?= selected($f, $s['heading_font']) ?>><?= e($f) ?></option><?php endforeach; ?></select></div>
<div class="field"><label>Body font</label><select name="body_font"><?php foreach (Theme::FONTS as $f): ?><option<?= selected($f, $s['body_font']) ?>><?= e($f) ?></option><?php endforeach; ?></select></div>
<div class="field"><label>Heading weight</label><select name="heading_weight"><?php foreach (['500' => 'Medium', '600' => 'Semibold', '700' => 'Bold', '800' => 'Extra bold'] as $k => $lbl): ?><option value="<?= $k ?>"<?= selected($k, $s['heading_weight']) ?>><?= $lbl ?></option><?php endforeach; ?></select></div>
<div class="field"><label>Base font size (px)</label><input type="number" min="13" max="18" name="base_font_size" value="<?= e($s['base_font_size']) ?>" data-live-var="--fs" data-unit="px"></div>
<div class="field"><label>Card radius (px)</label><input type="range" min="0" max="28" name="radius" value="<?= e($s['radius']) ?>" data-live-var="--radius" data-unit="px"></div>
<div class="field"><label>Button radius (px)</label><input type="range" min="0" max="40" name="button_radius" value="<?= e($s['button_radius']) ?>" data-live-var="--btn-radius" data-unit="px"></div>
<div class="field"><label>Button style</label><select name="button_style"><option value="solid"<?= selected('solid', $s['button_style']) ?>>Solid</option><option value="outline"<?= selected('outline', $s['button_style']) ?>>Outline</option></select></div>
<div class="field"><label>Shadows</label><select name="shadow"><?php foreach (['none' => 'None (flat)', 'soft' => 'Soft', 'strong' => 'Strong'] as $k => $lbl): ?><option value="<?= $k ?>"<?= selected($k, $s['shadow']) ?>><?= $lbl ?></option><?php endforeach; ?></select></div>
<div class="field"><label>Default theme</label><select name="default_theme"><option value="dark"<?= selected('dark', $s['default_theme']) ?>>Dark</option><option value="light"<?= selected('light', $s['default_theme']) ?>>Light</option></select></div>
</div>
<label class="check"><input type="checkbox" name="allow_theme_toggle" value="1"<?= checked($s['allow_theme_toggle']) ?>><span>Let users switch between light and dark</span></label>
</div>
<div class="grid-2">
<?php foreach (['dark' => 'Dark theme palette', 'light' => 'Light theme palette'] as $mode => $lbl): $pal = array_merge(Defaults::palettes()[$mode], (array)$s['palette_' . $mode]); ?>
<div class="card stack">
<div class="card-head"><h3><?= e($lbl) ?></h3><button type="button" class="btn btn-ghost btn-sm" data-preview-mode="<?= $mode ?>"><?= icon('eye') ?>Preview</button></div>
<div class="palette">
<?php foreach ($labels as $k => $l): ?><label class="color-field"><input type="color" name="palette_<?= $mode ?>_<?= $k ?>" value="<?= e($pal[$k]) ?>" data-color-var="--<?= str_replace('_', '-', $k) ?>" data-mode="<?= $mode ?>"><span><?= e($l) ?><code><?= e($pal[$k]) ?></code></span></label><?php endforeach; ?>
</div>
</div>
<?php endforeach; ?>
</div>
<div class="card stack">
<div class="card-head"><div><h3>Custom CSS</h3><small class="muted">Advanced. Added after the theme on every page.</small></div></div>
<textarea name="custom_css" rows="6" class="mono" spellcheck="false" placeholder=".course-card h3{letter-spacing:-.01em}"><?= e($s['custom_css']) ?></textarea>
</div>
<div class="sticky-actions"><button class="btn btn-primary"><?= icon('check') ?>Save branding</button><button class="btn btn-ghost" name="reset_palettes" value="1" data-confirm="Reset both palettes to the default Zeroes colors?"><?= icon('refresh') ?>Reset colors</button></div>
</form>
