/* Zeroes LMS — protected video player: custom controls, resume, progress tracking, moving watermark, no download UI. */
(function () {
  'use strict';
  var root = document.querySelector('[data-player]');
  var embed = document.querySelector('[data-embed]');
  function wmMove(el) { if (!el) { return; } setInterval(function () { el.style.left = (5 + Math.random() * 60) + '%'; el.style.top = (6 + Math.random() * 78) + '%'; }, 7000); }
  if (embed) { wmMove(embed.querySelector('.watermark')); }
  if (!root) { return; }
  var v = root.querySelector('video'), q = function (n) { return root.querySelector('[data-p="' + n + '"]'); };
  var seek = q('seek'), fill = seek.querySelector('.pfill'), buf = seek.querySelector('.pbuf'), knob = seek.querySelector('.pknob'), time = q('time');
  var progressUrl = root.getAttribute('data-progress'), resume = +root.getAttribute('data-resume') || 0, done = root.getAttribute('data-done') === '1';
  var speeds = [1, 1.25, 1.5, 1.75, 2, 0.75], si = 0, lastSent = 0, idleT, started = false;
  /* Load source only now (not present in page HTML). The URL is a short-lived signed token bound to this session. */
  var src = document.createElement('source'); src.src = root.getAttribute('data-src'); src.type = root.getAttribute('data-mime') || 'video/mp4'; v.appendChild(src); v.load();
  v.controls = false; v.setAttribute('controlsList', 'nodownload noremoteplayback noplaybackrate'); v.disablePictureInPicture = true;
  ['contextmenu', 'dragstart', 'selectstart'].forEach(function (ev) { root.addEventListener(ev, function (e) { e.preventDefault(); }); });
  function fmt(s) { s = Math.max(0, Math.floor(s || 0)); var h = Math.floor(s / 3600), m = Math.floor(s % 3600 / 60), x = s % 60; return (h ? h + ':' + (m < 10 ? '0' : '') : '') + m + ':' + (x < 10 ? '0' : '') + x; }
  function toggle() { if (v.paused) { var p = v.play(); if (p && p.catch) { p.catch(function () {}); } } else { v.pause(); } }
  function send(force) {
    if (!v.duration || !isFinite(v.duration)) { return; }
    var now = Date.now();
    if (!force && now - lastSent < 15000) { return; }
    lastSent = now;
    var data = { position: Math.floor(v.currentTime), duration: Math.floor(v.duration) };
    if (force && navigator.sendBeacon) { var f = new FormData(); f.append('position', data.position); f.append('duration', data.duration); f.append('_token', Zx.csrf); navigator.sendBeacon(progressUrl, f); return; }
    Zx.post(progressUrl, data).then(function (j) { if (j.completed && !done) { done = true; Zx.markComplete(true); } }).catch(function () {});
  }
  function idle() { root.classList.remove('idle'); clearTimeout(idleT); idleT = setTimeout(function () { if (!v.paused) { root.classList.add('idle'); } }, 2500); }
  v.addEventListener('loadedmetadata', function () { if (resume > 5 && resume < v.duration - 10) { v.currentTime = resume; } time.textContent = fmt(v.currentTime) + ' / ' + fmt(v.duration); });
  v.addEventListener('play', function () { root.classList.add('playing'); started = true; idle(); });
  v.addEventListener('pause', function () { root.classList.remove('playing', 'idle'); send(false); });
  v.addEventListener('ended', function () { root.classList.remove('playing'); lastSent = 0; send(false); });
  v.addEventListener('timeupdate', function () {
    var p = v.duration ? v.currentTime / v.duration * 100 : 0;
    fill.style.width = p + '%'; knob.style.left = p + '%'; time.textContent = fmt(v.currentTime) + ' / ' + fmt(v.duration); send(false);
  });
  v.addEventListener('progress', function () { if (v.buffered.length && v.duration) { buf.style.width = (v.buffered.end(v.buffered.length - 1) / v.duration * 100) + '%'; } });
  v.addEventListener('volumechange', function () { root.classList.toggle('muted', v.muted || v.volume === 0); });
  v.addEventListener('error', function () {
    var m = document.createElement('div'); m.className = 'player-msg';
    m.innerHTML = '<div><b>Video could not be loaded.</b><br><small>Your secure playback link may have expired. Reload the page to continue.</small></div>';
    root.appendChild(m);
  }, true);
  root.addEventListener('mousemove', idle); root.addEventListener('touchstart', idle, { passive: true });
  root.querySelector('.player-shield').addEventListener('click', toggle);
  root.querySelector('.player-shield').addEventListener('dblclick', function () { q('fs').click(); });
  q('play').addEventListener('click', toggle); q('toggle').addEventListener('click', toggle);
  q('back').addEventListener('click', function () { v.currentTime = Math.max(0, v.currentTime - 10); });
  q('fwd').addEventListener('click', function () { v.currentTime = Math.min(v.duration || 0, v.currentTime + 10); });
  q('mute').addEventListener('click', function () { v.muted = !v.muted; });
  q('speed').addEventListener('click', function () { si = (si + 1) % speeds.length; v.playbackRate = speeds[si]; this.textContent = speeds[si] + '×'; });
  q('fs').addEventListener('click', function () {
    if (document.fullscreenElement || document.webkitFullscreenElement) { (document.exitFullscreen || document.webkitExitFullscreen).call(document); }
    else if (root.requestFullscreen) { root.requestFullscreen(); } else if (root.webkitRequestFullscreen) { root.webkitRequestFullscreen(); } else if (v.webkitEnterFullscreen) { v.webkitEnterFullscreen(); }
  });
  function seekTo(e) { var r = seek.getBoundingClientRect(), x = ((e.touches ? e.touches[0].clientX : e.clientX) - r.left) / r.width; if (v.duration) { v.currentTime = Math.max(0, Math.min(1, x)) * v.duration; } }
  var dragging = false;
  seek.addEventListener('pointerdown', function (e) { dragging = true; seek.setPointerCapture(e.pointerId); seekTo(e); });
  seek.addEventListener('pointermove', function (e) { if (dragging) { seekTo(e); } });
  seek.addEventListener('pointerup', function () { dragging = false; });
  seek.addEventListener('keydown', function (e) { if (e.key === 'ArrowRight') { v.currentTime += 5; } if (e.key === 'ArrowLeft') { v.currentTime -= 5; } });
  document.addEventListener('keydown', function (e) {
    if (/INPUT|TEXTAREA|SELECT/.test(document.activeElement.tagName)) { return; }
    if (e.key === ' ' || e.key === 'k') { e.preventDefault(); toggle(); } else if (e.key === 'ArrowRight') { v.currentTime += 5; } else if (e.key === 'ArrowLeft') { v.currentTime -= 5; } else if (e.key === 'f') { q('fs').click(); } else if (e.key === 'm') { v.muted = !v.muted; }
  });
  document.addEventListener('visibilitychange', function () { if (document.hidden && started) { send(true); } });
  window.addEventListener('pagehide', function () { if (started) { send(true); } });
  wmMove(root.querySelector('.watermark'));
})();
