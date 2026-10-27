<?php
$layout = 'admin';
$title = $t['name'];
$crumbs = ['Settings' => '/admin/settings', 'Email templates' => '/admin/emails'];
?>
<?php $tab = 'emails'; include \App\Core\View::find('admin/settings/_tabs'); ?>
<div class="form-layout">
<form method="post" action="<?= e(url('/admin/emails/' . $t['key'])) ?>" class="card stack" data-loading>
<?= csrf_field() ?>
<div class="field"><label>Subject</label><input name="subject" value="<?= e(old('subject', $t['subject'])) ?>" required maxlength="255"></div>
<div class="field"><label>Body</label><div class="editor" data-editor data-editor-vars><textarea name="body" hidden><?= e(old('body', $t['body'])) ?></textarea></div><small class="hint">Use <code>class="btn"</code> on a link to render it as a branded button. Variables are inserted as <code>{{name}}</code>.</small></div>
<label class="check"><input type="checkbox" name="enabled" value="1"<?= checked($t['enabled']) ?>><span>Template enabled (disabled templates are never sent)</span></label>
<div class="row gap-2"><button class="btn btn-primary"><?= icon('check') ?>Save template</button><a class="btn btn-ghost" href="<?= e(url('/admin/emails/' . $t['key'] . '/preview')) ?>" target="_blank" rel="noopener"><?= icon('eye') ?>Preview</a></div>
</form>
<aside class="stack-lg">
<div class="card">
<div class="card-head"><h3>Variables</h3></div>
<div class="var-list"><?php foreach ($vars as $vv): ?><button type="button" class="chip-btn" data-insert-var="{{<?= e($vv) ?>}}">{{<?= e($vv) ?>}}</button><?php endforeach; ?></div>
<p class="small muted mt-2">Click to insert at the cursor.</p>
</div>
<div class="card">
<div class="card-head"><h3>Send a test</h3></div>
<p class="small muted">Sends this template with sample data to <b><?= e(auth()['email']) ?></b>.</p>
<form method="post" action="<?= e(url('/admin/emails/' . $t['key'] . '/test')) ?>"><?= csrf_field() ?><button class="btn btn-ghost btn-block"><?= icon('mail') ?>Send test email</button></form>
</div>
</aside>
</div>
<?php include \App\Core\View::find('admin/settings/_end'); ?>
