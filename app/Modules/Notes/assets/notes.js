/* Notes module: debounced autosave of the lesson notes textarea. */
(function () {
  'use strict';
  var ta = document.querySelector('[data-note]');
  if (!ta || !window.Zx) { return; }
  var st = document.querySelector('[data-note-status]'), t = null, last = ta.value, busy = false, again = false;
  function save() {
    if (ta.value === last) { st.textContent = 'Saved'; return; }
    if (busy) { again = true; return; }
    busy = true; var val = ta.value; st.textContent = 'Saving…';
    Zx.post(ta.getAttribute('data-note'), { body: val }).then(function () { last = val; st.textContent = 'Saved'; }).catch(function (e) { st.textContent = 'Not saved'; Zx.toast(e.message, 'error'); }).then(function () { busy = false; if (again) { again = false; save(); } });
  }
  ta.addEventListener('input', function () { st.textContent = 'Typing…'; clearTimeout(t); t = setTimeout(save, 800); });
  ta.addEventListener('blur', function () { clearTimeout(t); save(); });
  window.addEventListener('pagehide', function () {
    if (ta.value !== last && navigator.sendBeacon) { var f = new FormData(); f.append('body', ta.value); f.append('_token', Zx.csrf); navigator.sendBeacon(ta.getAttribute('data-note'), f); }
  });
})();
