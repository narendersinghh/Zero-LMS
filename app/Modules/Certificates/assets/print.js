(function () {
  'use strict';
  var b = document.querySelector('[data-print]');
  if (b) { b.addEventListener('click', function () { window.print(); }); }
})();
