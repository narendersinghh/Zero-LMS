/* Zeroes LMS — shared UI behaviours (no dependencies). */
(function () {
  'use strict';
  var d = document, $ = function (s, c) { return (c || d).querySelector(s); }, $$ = function (s, c) { return Array.prototype.slice.call((c || d).querySelectorAll(s)); };
  var csrf = ($('meta[name="csrf-token"]') || {}).content || '';
  window.Zx = {
    $: $, $$: $$, csrf: csrf,
    post: function (url, data, json) {
      var opt = { method: 'POST', credentials: 'same-origin', headers: { 'X-CSRF-Token': csrf, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } };
      if (json) { opt.headers['Content-Type'] = 'application/json'; opt.body = JSON.stringify(data || {}); }
      else if (data instanceof FormData) { opt.body = data; }
      else { var f = new FormData(); Object.keys(data || {}).forEach(function (k) { f.append(k, data[k]); }); opt.body = f; }
      return fetch(url, opt).then(function (r) { return r.json().catch(function () { return {}; }).then(function (j) { if (!r.ok) { throw new Error(j.error || ('Request failed (' + r.status + ')')); } return j; }); });
    },
    toast: function (msg, type) {
      var box = $('.toasts');
      if (!box) { box = d.createElement('div'); box.className = 'toasts'; d.body.appendChild(box); }
      var t = d.createElement('div'); t.className = 'toast toast-' + (type || 'info'); t.textContent = msg; box.appendChild(t);
      setTimeout(function () { t.classList.add('out'); setTimeout(function () { t.remove(); }, 300); }, 4000);
    }
  };
  /* Theme toggle (cookie so the server renders the right theme with no flash) */
  function setTheme(t) {
    d.documentElement.setAttribute('data-theme', t);
    d.cookie = 'zx_theme=' + t + ';path=/;max-age=31536000;samesite=lax';
    $$('[data-set-theme]').forEach(function (b) { b.classList.toggle('active', b.getAttribute('data-set-theme') === t); });
  }
  d.addEventListener('click', function (e) {
    var t = e.target.closest('[data-theme-toggle]');
    if (t) { setTheme(d.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark'); return; }
    var st = e.target.closest('[data-set-theme]');
    if (st) { setTheme(st.getAttribute('data-set-theme')); return; }
    /* Dropdowns */
    var tog = e.target.closest('[data-dropdown-toggle]');
    $$('[data-dropdown].open').forEach(function (x) { if (!tog || x !== tog.parentNode) { x.classList.remove('open'); } });
    if (tog) { e.preventDefault(); tog.parentNode.classList.toggle('open'); return; }
    /* Toast dismiss */
    var dis = e.target.closest('[data-dismiss]');
    if (dis) { dis.parentNode.remove(); return; }
    /* Confirm */
    var c = e.target.closest('[data-confirm]');
    if (c && !window.confirm(c.getAttribute('data-confirm'))) { e.preventDefault(); e.stopPropagation(); return; }
    /* Modals */
    var mo = e.target.closest('[data-modal-open]');
    if (mo) { var m = d.getElementById(mo.getAttribute('data-modal-open')); if (m && m.showModal) { m.showModal(); m.dispatchEvent(new CustomEvent('zx:open')); } return; }
    var mc = e.target.closest('[data-modal-close]');
    if (mc) { mc.closest('dialog').close(); return; }
    if (e.target.tagName === 'DIALOG') { var r = e.target.getBoundingClientRect(); if (e.clientX < r.left || e.clientX > r.right || e.clientY < r.top || e.clientY > r.bottom) { e.target.close(); } return; }
    /* Show password */
    var sp = e.target.closest('[data-toggle-password]');
    if (sp) { var inp = sp.parentNode.querySelector('input'); inp.type = inp.type === 'password' ? 'text' : 'password'; return; }
    /* Mark all notifications read */
    var ra = e.target.closest('[data-read-all]');
    if (ra) { Zx.post((($('meta[name="base-url"]') || {}).content || '/').replace(/\/$/, '') + '/notifications/read-all', {}).then(function () { $$('.notif.unread').forEach(function (n) { n.classList.remove('unread'); }); var dot = $('.bell .dot'); if (dot) { dot.remove(); } ra.remove(); }); return; }
    /* Copy */
    var cp = e.target.closest('[data-copy]');
    if (cp) { navigator.clipboard && navigator.clipboard.writeText(cp.getAttribute('data-copy')).then(function () { Zx.toast('Copied to clipboard', 'success'); }); return; }
    var bk = e.target.closest('[data-back]');
    if (bk) { e.preventDefault(); history.back(); return; }
    /* Tabs (client side) */
    var tb = e.target.closest('[data-tab]');
    if (tb) {
      var wrap = tb.closest('[data-tabs]');
      $$('[data-tab]', wrap).forEach(function (x) { x.classList.toggle('active', x === tb); var p = d.getElementById(x.getAttribute('data-tab')); if (p) { p.hidden = x !== tb; } });
    }
  });
  d.addEventListener('keydown', function (e) { if (e.key === 'Escape') { $$('[data-dropdown].open').forEach(function (x) { x.classList.remove('open'); }); } });
  /* Auto submit selects/file inputs */
  d.addEventListener('change', function (e) { if (e.target.matches('[data-autosubmit]')) { e.target.form.submit(); } });
  /* Loading state on submit */
  d.addEventListener('submit', function (e) {
    var f = e.target;
    if (f.hasAttribute('data-loading')) { var b = e.submitter || $('button[type=submit],button:not([type])', f); if (b) { setTimeout(function () { b.classList.add('is-loading'); }, 10); } }
  });
  /* Toast auto dismiss */
  $$('.toast').forEach(function (t, i) { setTimeout(function () { t.classList.add('out'); setTimeout(function () { t.remove(); }, 300); }, 5000 + i * 600); });
  /* Countdowns */
  function fmt(s) { if (s <= 0) { return 'now'; } var dd = Math.floor(s / 86400), h = Math.floor(s % 86400 / 3600), m = Math.floor(s % 3600 / 60); return dd ? dd + 'd ' + h + 'h' : (h ? h + 'h ' + m + 'm' : Math.max(1, m) + 'm'); }
  var cds = $$('[data-countdown]');
  if (cds.length) { var tick = function () { var now = Date.now() / 1000; cds.forEach(function (c) { var s = +c.getAttribute('data-countdown') - now; c.textContent = s <= 0 ? 'Unlocked — refresh' : fmt(s); }); }; tick(); setInterval(tick, 30000); }
  /* Password strength */
  $$('[data-strength]').forEach(function (i) {
    var bar = i.closest('.field').querySelector('.strength i');
    if (!bar) { return; }
    i.addEventListener('input', function () {
      var v = i.value, s = 0;
      if (v.length >= 8) { s++; } if (v.length >= 12) { s++; } if (/[A-Z]/.test(v) && /[a-z]/.test(v)) { s++; } if (/\d/.test(v)) { s++; } if (/[^A-Za-z0-9]/.test(v)) { s++; }
      bar.style.width = (s / 5 * 100) + '%'; bar.style.background = s < 2 ? 'var(--danger)' : (s < 4 ? 'var(--warning)' : 'var(--success)');
    });
  });
  $$('[data-set-theme]').forEach(function (b) { b.classList.toggle('active', b.getAttribute('data-set-theme') === d.documentElement.getAttribute('data-theme')); });
  /* Lesson complete toggle */
  var cb = $('[data-complete]');
  if (cb) {
    cb.addEventListener('click', function () {
      var done = cb.getAttribute('data-done') === '1';
      cb.classList.add('is-loading');
      Zx.post(cb.getAttribute('data-complete'), done ? { undo: '1' } : {}).then(function (j) { Zx.markComplete(j.completed, j.course); }).catch(function (er) { Zx.toast(er.message, 'error'); }).then(function () { cb.classList.remove('is-loading'); });
    });
  }
  Zx.markComplete = function (done, course) {
    if (!cb) { return; }
    cb.setAttribute('data-done', done ? '1' : '0');
    cb.classList.toggle('btn-success', !!done); cb.classList.toggle('btn-primary', !done);
    cb.querySelector('span').textContent = done ? 'Completed' : 'Mark as complete';
    var cur = $('.side-lesson.current'); if (cur) { cur.classList.toggle('done', !!done); }
    if (course) {
      var p = $('[data-course-progress]'); if (p) { p.textContent = course.completed + '/' + course.total + ' complete'; }
      var ring = $('.side-curriculum .ring'); if (ring) { ring.querySelector('.ring-fg').setAttribute('stroke-dasharray', course.percent + ' 100'); ring.querySelector('b').textContent = course.percent + '%'; }
    }
    if (done) { Zx.toast('Lesson completed — nice work!', 'success'); }
  };
})();
