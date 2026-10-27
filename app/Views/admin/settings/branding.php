<?php
use App\Core\Defaults;
use App\Core\Theme;
$layout = 'admin';
$title = 'Settings';
include \App\Core\View::find('admin/settings/_tabs');
$groups = [
    'Surfaces' => ['bg' => 'Page background', 'surface' => 'Cards', 'surface_2' => 'Raised surface', 'border' => 'Borders', 'input_bg' => 'Inputs', 'sidebar_bg' => 'Sidebar'],
    'Text' => ['text' => 'Text', 'muted' => 'Muted text'],
    'Brand' => ['accent' => 'Accent / buttons', 'accent_contrast' => 'Text on accent', 'accent_text' => 'Links & accent text', 'hero_from' => 'Banner gradient start', 'hero_to' => 'Banner gradient end'],
    'Status' => ['success' => 'Success', 'warning' => 'Warning', 'danger' => 'Danger'],];
?>
<div class="settings-hero tint-pink"><span class="sh-ico"><?= icon('palette') ?></span><div><h2>Branding & theme</h2><p>Make Zeroes look like your brand, no code needed. Colors preview live as you change them.</p></div></div>
<form method="post" action="<?= e(url('/admin/settings/branding')) ?>" enctype="multipart/form-data" class="stack-lg" data-loading data-theme-editor>
<?= csrf_field() ?>
<section class="set-sec">
<div class="set-desc"><h3>Logo & favicon</h3><p>PNG, JPG, WebP or SVG. Logos display 34px tall. Upload a light-theme version if your logo is dark.</p></div>
<div class="card set-fields">
<div class="grid-3">
<?php foreach (['logo' => 'Logo · dark theme', 'logo_light' => 'Logo · light theme', 'favicon' => 'Favicon'] as $k => $lbl): ?>
<div class="field"><label><?= e($lbl) ?></label>
<div class="logo-preview <?= $k === 'logo_light' ? 'on-light' : '' ?>" data-image-preview<?= $s[$k] ? ' style="background-image:url(\'' . e(upload_url($s[$k])) . '\')"' : '' ?>><?= $s[$k] ? '' : '<span class="muted small">None</span>' ?></div>
<input type="file" name="<?= $k ?>" accept="image/png,image/jpeg,image/webp,image/svg+xml,image/x-icon" data-preview-input>
<?php if ($s[$k]): ?><label class="check"><input type="checkbox" name="remove_<?= $k ?>" value="1"><span>Remove</span></label><?php endif; ?></div>
<?php endforeach; ?>
</div>
</div>
</section>
<section class="set-sec">
<div class="set-desc"><h3>Typography</h3><p>Fonts load from Google Fonts. Default: Inter Tight for headings, Inter for body.</p></div>
<div class="card set-fields">
<div class="grid-2">
<div class="field"><label>Heading font</label><select name="heading_font"><?php foreach (Theme::FONTS as $f): ?><option<?= selected($f, $s['heading_font']) ?>><?= e($f) ?></option><?php endforeach; ?></select></div>
<div class="field"><label>Body font</label><select name="body_font"><?php foreach (Theme::FONTS as $f): ?><option<?= selected($f, $s['body_font']) ?>><?= e($f) ?></option><?php endforeach; ?></select></div>
<div class="field"><label>Heading weight</label><select name="heading_weight"><?php foreach (['500' => 'Medium', '600' => 'Semibold', '700' => 'Bold', '800' => 'Extra bold'] as $k => $lbl): ?><option value="<?= $k ?>"<?= selected($k, $s['heading_weight']) ?>><?= $lbl ?></option><?php endforeach; ?></select></div>
<div class="field"><label>Base font size (px)</label><input type="number" min="13" max="18" name="base_font_size" value="<?= e($s['base_font_size']) ?>" data-live-var="--fs" data-unit="px"></div>
</div>
</div>
</section>
<section class="set-sec">
<div class="set-desc"><h3>Shape & style</h3><p>Corner roundness, button style and depth.</p></div>
<div class="card set-fields">
<div class="grid-2">
<div class="field"><label>Card radius</label><input type="range" min="0" max="28" name="radius" value="<?= e($s['radius']) ?>" data-live-var="--radius" data-unit="px"></div>
<div class="field"><label>Button radius</label><input type="range" min="0" max="40" name="button_radius" value="<?= e($s['button_radius']) ?>" data-live-var="--btn-radius" data-unit="px"></div>
<div class="field"><label>Button style</label><select name="button_style"><option value="solid"<?= selected('solid', $s['button_style']) ?>>Solid</option><option value="outline"<?= selected('outline', $s['button_style']) ?>>Outline</option></select></div>
<div class="field"><label>Shadows</label><select name="shadow"><?php foreach (['none' => 'None (flat)', 'soft' => 'Soft', 'strong' => 'Strong'] as $k => $lbl): ?><option value="<?= $k ?>"<?= selected($k, $s['shadow']) ?>><?= $lbl ?></option><?php endforeach; ?></select></div>
</div>
<div class="row gap-2 wrap"><span class="btn btn-primary btn-sm">Primary</span><span class="btn btn-sm">Secondary</span><span class="btn btn-ghost btn-sm">Ghost</span><span class="badge badge-success">Badge</span></div>
</div>
</section>
<section class="set-sec">
<div class="set-desc"><h3>Theme mode</h3><p>Which theme new visitors see, and whether they can switch.</p></div>
<div class="card set-fields">
<div class="field"><label>Default theme</label><div class="seg" data-seg="default_theme"><label><input type="radio" name="default_theme" value="dark"<?= checked($s['default_theme'] !== 'light') ?>><span><?= icon('moon') ?> Dark</span></label><label><input type="radio" name="default_theme" value="light"<?= checked($s['default_theme'] === 'light') ?>><span><?= icon('sun') ?> Light</span></label></div></div>
<div class="set-row"><div><b>Let users switch theme</b><small>Shows the sun/moon toggle in the header.</small></div><label class="switch"><input type="checkbox" name="allow_theme_toggle" value="1"<?= checked($s['allow_theme_toggle']) ?>><span></span></label></div>
</div>
</section>
<?php foreach (['dark' => ['Dark palette', 'moon'], 'light' => ['Light palette', 'sun']] as $mode => [$lbl, $ic]): $pal = array_merge(Defaults::palettes()[$mode], (array)$s['palette_' . $mode]); ?>
<section class="set-sec">
<div class="set-desc"><h3><?= icon($ic) ?> <?= e($lbl) ?></h3><p>Click a swatch to change it.</p><button type="button" class="btn btn-ghost btn-sm mt-3" data-preview-mode="<?= $mode ?>"><?= icon('eye') ?>Preview <?= $mode ?></button></div>
<div class="card set-fields">
<?php foreach ($groups as $g => $keys): ?>
<div><span class="settings-label" style="padding:0 0 .4rem;display:block"><?= e($g) ?></span><div class="palette">
<?php foreach ($keys as $k => $l): ?><label class="color-field"><input type="color" name="palette_<?= $mode ?>_<?= $k ?>" value="<?= e($pal[$k]) ?>" data-color-var="--<?= str_replace('_', '-', $k) ?>" data-mode="<?= $mode ?>"><span><?= e($l) ?><code><?= e($pal[$k]) ?></code></span></label><?php endforeach; ?>
</div></div>
<?php endforeach; ?>
</div>
</section>
<?php endforeach; ?>
<section class="set-sec">
<div class="set-desc"><h3>Custom CSS</h3><p>Advanced. Added after the theme on every page.</p></div>
<div class="card set-fields"><textarea name="custom_css" rows="6" class="mono" spellcheck="false" placeholder=".ccard h3{letter-spacing:-.01em}"><?= e($s['custom_css']) ?></textarea></div>
</section>
<div class="set-actions"><button class="btn btn-ghost" name="reset_palettes" value="1" data-confirm="Reset both palettes to the default Zeroes colors?"><?= icon('refresh') ?>Reset colors</button><button class="btn btn-primary"><?= icon('check') ?>Save branding</button></div>
</form>
<?php include \App\Core\View::find('admin/settings/_end'); ?>
