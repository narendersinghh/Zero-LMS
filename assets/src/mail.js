/* Zeroes LMS: Email delivery settings: provider-aware fields + "send a test". */
(function () {
  'use strict';
  var f = document.querySelector('[data-mail-form]');
  if (!f) { return; }
  var P = JSON.parse(f.getAttribute('data-providers') || '{}');
  var q = function (s) { return f.querySelector(s); }, qa = function (s) { return Array.prototype.slice.call(f.querySelectorAll(s)); };
  var show = function (el, on) { if (el) { el.hidden = !on; } };
  var sel = q('[name="mail_provider"]');
  function provider() { return sel ? sel.value : 'log'; }
  function method(p) { var r = q('[name="mail_method"]:checked'), m = r ? r.value : 'api'; return p.m.indexOf(m) > -1 ? m : p.m[0]; }
  function sync(changedProvider) {
    var k = provider(), p = P[k]; if (!p) { return; }
    var real = p.m.indexOf('api') > -1 || p.m.indexOf('smtp') > -1;
    if (changedProvider && p.m.indexOf('api') > -1) { var a = q('[name="mail_method"][value="api"]'); if (a) { a.checked = true; } }
    var m = method(p);
    qa('[data-connect]').forEach(function (n) { n.hidden = !real; });
    show(q('[data-nosetup]'), !real);
    qa('[data-pname]').forEach(function (n) { n.textContent = p.n; });
    show(q('[data-method-wrap]'), p.m.indexOf('api') > -1 && p.m.indexOf('smtp') > -1);
    show(q('[data-api-fields]'), m === 'api');
    show(q('[data-smtp-fields]'), m === 'smtp');
    var t = function (sel, v) { var n = q(sel); if (n) { n.textContent = v; } };
    t('[data-key-label]', p.kl || 'API key'); t('[data-secret-label]', p.sl || 'Secret key');
    show(q('[data-secret-field]'), !!p.sl); show(q('[data-domain-field]'), !!p.d);
    t('[data-user-label]', p.ul || 'SMTP username'); t('[data-pass-label]', p.pl || 'SMTP password');
    show(q('[data-user-field]'), !p.uf);
    show(q('[data-smtp-fixed]'), !p.he); show(q('[data-host-field]'), !!p.he);
    if (changedProvider) {
      var host = q('[name="smtp_host"]'); if (host && p.he) { host.value = p.h; }
      var port = q('[name="smtp_port"]'); if (port) { port.value = p.p; }
      var sec = q('[name="smtp_secure"]'); if (sec) { sec.value = p.s; }
      ['mail_api_key', 'mail_api_secret', 'smtp_pass'].forEach(function (n) { var i = q('[name="' + n + '"]'); if (i) { i.value = ''; i.placeholder = 'Paste your ' + (n === 'smtp_pass' ? 'password' : 'key'); } });
    }
    t('[data-host-text]', p.h); t('[data-port-text]', (q('[name="smtp_port"]') || {}).value || p.p);
    t('[data-secure-text]', ((q('[name="smtp_secure"]') || {}).value || p.s).toUpperCase());
    var o = sel.options[sel.selectedIndex], lg = q('[data-plogo]'), tg = q('[data-ptag]');
    if (lg) { lg.style.setProperty('--c', o.getAttribute('data-color')); lg.textContent = p.n.charAt(0).toUpperCase(); }
    if (tg) { tg.textContent = o.getAttribute('data-tag'); }
    qa('[data-steps-for]').forEach(function (n) { n.hidden = n.getAttribute('data-steps-for') !== k; });
  }
  f.addEventListener('change', function (e) {
    if (e.target.name === 'mail_provider') { sync(true); }
    else if (e.target.name === 'mail_method' || e.target.name === 'smtp_port' || e.target.name === 'smtp_secure') { sync(false); }
  });
  sync(false);
  var btn = q('[data-mail-test]'), out = q('[data-mail-result]');
  btn.addEventListener('click', function () {
    var fd = new FormData(f);
    btn.classList.add('is-loading'); out.hidden = true;
    Zx.post(f.getAttribute('data-test-url'), fd).then(function (j) { render(j); }).catch(function (er) { render({ ok: false, message: er.message }); }).then(function () { btn.classList.remove('is-loading'); });
  });
  function render(j) {
    out.hidden = false;
    out.className = 'mp-result ' + (j.ok ? 'ok' : 'err');
    out.textContent = '';
    var b = document.createElement('b'); b.textContent = j.ok ? '✓ ' + j.message : '✕ Sending failed'; out.appendChild(b);
    if (!j.ok) {
      var p = document.createElement('code'); p.textContent = j.message || 'Unknown error'; out.appendChild(p);
      if (j.tips && j.tips.length) { var ul = document.createElement('ul'); j.tips.forEach(function (t) { var li = document.createElement('li'); li.textContent = t; ul.appendChild(li); }); out.appendChild(ul); }
    }
  }
})();
