/* Small, dependency-free helpers. Everything works without JavaScript
   except the convenience features listed here. */
(function () {
  'use strict';

  // Mobile navigation (public site + admin sidebar)
  var pub = document.querySelector('[data-nav-toggle]');
  if (pub) {
    pub.addEventListener('click', function () {
      var nav = document.getElementById('site-nav');
      var open = nav.classList.toggle('open');
      pub.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
  }
  var side = document.querySelector('[data-side-toggle]');
  if (side) {
    side.addEventListener('click', function () {
      var bar = document.getElementById('sidebar');
      var open = bar.classList.toggle('open');
      side.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
    document.addEventListener('click', function (e) {
      var bar = document.getElementById('sidebar');
      if (bar.classList.contains('open') && !bar.contains(e.target) && e.target !== side) {
        bar.classList.remove('open');
        side.setAttribute('aria-expanded', 'false');
      }
    });
  }

  // Dismiss flash messages
  document.addEventListener('click', function (e) {
    var b = e.target.closest('[data-dismiss]');
    if (b && b.parentNode) { b.parentNode.remove(); }
  });

  // Ask before risky actions: <form data-confirm="Are you sure?">
  document.addEventListener('submit', function (e) {
    var msg = e.target.getAttribute && e.target.getAttribute('data-confirm');
    if (msg && !window.confirm(msg)) { e.preventDefault(); }
  });

  // Print buttons
  document.addEventListener('click', function (e) {
    if (e.target.closest('[data-print]')) { window.print(); }
  });

  // Payment form: choosing a reservation reloads the form with its balance filled in
  var pick = document.querySelector('[data-autosubmit-get]');
  if (pick) {
    pick.addEventListener('change', function () {
      if (pick.value) {
        var base = window.location.pathname;
        window.location.href = base + '?reservation_id=' + encodeURIComponent(pick.value);
      }
    });
  }

  // Reservation form: fill rent / deposit / advance from the chosen room and number of beds
  var form = document.getElementById('res-form');
  if (form) {
    var room = form.querySelector('[data-room-select]');
    var beds = form.querySelector('[data-beds]');
    var rate = form.querySelector('[data-rate]');
    var dep = form.querySelector('[data-deposit]');
    var adv = form.querySelector('[data-advance]');
    var touched = { rate: rate.value !== '', dep: dep.value !== '', adv: adv.value !== '' };
    rate.addEventListener('input', function () { touched.rate = true; });
    dep.addEventListener('input', function () { touched.dep = true; });
    adv.addEventListener('input', function () { touched.adv = true; });

    var fmt = function (n) { return (Math.round(n * 100) / 100).toFixed(2); };
    var refresh = function () {
      if (!room || room.disabled) { return; }
      var opt = room.options[room.selectedIndex];
      if (!opt || !opt.dataset.rate) { return; }
      var n = Math.max(1, parseInt(beds.value, 10) || 1);
      var cap = parseInt(opt.dataset.capacity, 10) || 1;
      beds.max = cap;
      var r = parseFloat(opt.dataset.rate) * n;
      var d = parseFloat(opt.dataset.deposit) * n;
      var months = parseInt(adv.getAttribute('data-advance-months'), 10) || 0;
      if (!touched.rate) { rate.value = fmt(r); }
      if (!touched.dep) { dep.value = fmt(d); }
      if (!touched.adv) { adv.value = fmt((touched.rate ? parseFloat(rate.value) || r : r) * months); }
    };
    if (room) { room.addEventListener('change', refresh); }
    if (beds) { beds.addEventListener('input', refresh); }
    rate.addEventListener('input', function () { if (!touched.adv) { refresh(); } });
    if (!rate.value && room && room.value) { refresh(); }
  }
})();
