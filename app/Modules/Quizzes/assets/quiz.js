/* Quizzes module: one question per screen with progress (falls back to a plain list without JS). */
(function () {
  'use strict';
  var f = document.querySelector('[data-quiz]');
  if (!f) { return; }
  var steps = Array.prototype.slice.call(f.querySelectorAll('[data-quiz-step]'));
  if (steps.length < 2) { return; }
  var i = 0, prev = f.querySelector('[data-quiz-prev]'), next = f.querySelector('[data-quiz-next]'), sub = f.querySelector('[data-quiz-submit]'), bar = f.querySelector('[data-quiz-bar]'), cnt = f.querySelector('[data-quiz-count]');
  f.querySelector('.quiz-progress').hidden = false;
  function answered(s) { return !!s.querySelector('input:checked'); }
  function show() {
    steps.forEach(function (s, n) { s.hidden = n !== i; });
    prev.hidden = i === 0; next.hidden = i === steps.length - 1; sub.hidden = i !== steps.length - 1;
    next.disabled = !answered(steps[i]);
    bar.style.width = ((i + 1) / steps.length * 100) + '%';
    cnt.textContent = 'Question ' + (i + 1) + ' of ' + steps.length;
  }
  f.addEventListener('change', function () { next.disabled = !answered(steps[i]); });
  next.addEventListener('click', function () { if (answered(steps[i]) && i < steps.length - 1) { i++; show(); } });
  prev.addEventListener('click', function () { if (i > 0) { i--; show(); } });
  f.addEventListener('submit', function (e) {
    var miss = steps.findIndex(function (s) { return !answered(s); });
    if (miss > -1) { e.preventDefault(); i = miss; show(); if (window.Zx) { Zx.toast('Please answer every question.', 'warning'); } }
  });
  show();
})();
