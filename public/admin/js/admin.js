/* The Cure Medicall Hall - admin interactions (no framework). */
(function () {
  'use strict';

  // ---- Sidebar (mobile)
  var sidebar = document.getElementById('sidebar');
  var backdrop = document.getElementById('sidebarBackdrop');
  function setSidebar(open) {
    if (!sidebar) return;
    sidebar.classList.toggle('open', open);
    if (backdrop) backdrop.classList.toggle('show', open);
  }
  document.querySelectorAll('[data-toggle-sidebar]').forEach(function (b) {
    b.addEventListener('click', function () { setSidebar(!sidebar.classList.contains('open')); });
  });
  if (backdrop) backdrop.addEventListener('click', function () { setSidebar(false); });
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape') setSidebar(false); });

  // Keep the active sidebar link in view.
  var active = document.querySelector('.nav-item-link.active');
  if (active && active.scrollIntoView) active.scrollIntoView({ block: 'nearest' });

  // ---- Confirm dialogs: <form data-confirm="Are you sure?">
  document.addEventListener('submit', function (e) {
    var msg = e.target.getAttribute && e.target.getAttribute('data-confirm');
    if (msg && !window.confirm(msg)) e.preventDefault();
  });

  // ---- Prevent double submits
  document.querySelectorAll('form[data-once]').forEach(function (f) {
    f.addEventListener('submit', function () {
      f.querySelectorAll('button[type=submit]').forEach(function (b) {
        setTimeout(function () { b.disabled = true; }, 0);
      });
    });
  });

  // ---- Bulk selection on list pages
  var master = document.getElementById('selectAll');
  var bar = document.getElementById('bulkBar');
  var count = document.getElementById('bulkCount');
  function refreshBulk() {
    var boxes = document.querySelectorAll('input.row-check');
    var n = 0;
    boxes.forEach(function (b) { if (b.checked) n++; });
    if (bar) bar.classList.toggle('show', n > 0);
    if (count) count.textContent = n + ' selected';
    if (master) master.checked = boxes.length > 0 && n === boxes.length;
  }
  if (master) master.addEventListener('change', function () {
    document.querySelectorAll('input.row-check').forEach(function (b) { b.checked = master.checked; });
    refreshBulk();
  });
  document.addEventListener('change', function (e) {
    if (e.target.classList && e.target.classList.contains('row-check')) refreshBulk();
  });
  var bulkForm = document.getElementById('bulkForm');
  if (bulkForm) bulkForm.addEventListener('submit', function (e) {
    var sel = bulkForm.querySelector('select[name=action]');
    if (!sel.value) { e.preventDefault(); return; }
    var label = sel.options[sel.selectedIndex].text;
    var destructive = /delete/i.test(label);
    if (destructive && !window.confirm('Delete the selected items? This cannot be undone.')) { e.preventDefault(); return; }
    document.querySelectorAll('input.row-check:checked').forEach(function (b) {
      var h = document.createElement('input');
      h.type = 'hidden'; h.name = 'ids[]'; h.value = b.value; bulkForm.appendChild(h);
    });
  });

  // ---- Image preview for file inputs
  document.querySelectorAll('input[type=file][data-preview]').forEach(function (input) {
    var dropzone = document.querySelector('[data-upload-for="' + input.id + '"]');
    input.addEventListener('change', function () {
      var holder = document.getElementById(input.getAttribute('data-preview'));
      var status = document.getElementById(input.getAttribute('data-file-status'));
      if (holder) {
        holder.innerHTML = '';
        Array.prototype.forEach.call(input.files, function (f) {
          if (!/^image\//.test(f.type)) return;
          var img = document.createElement('img');
          img.alt = '';
          img.src = URL.createObjectURL(f);
          holder.appendChild(img);
        });
      }
      if (status) {
        status.textContent = input.files.length
          ? input.files.length + (input.files.length === 1 ? ' image selected.' : ' images selected.')
          : 'No images selected.';
      }
      if (dropzone && input.files.length) {
        dropzone.querySelector('strong').textContent = input.files[0].name;
        dropzone.querySelector('small').textContent = input.files.length > 1 ? input.files.length + ' images selected' : 'Click to choose a different image';
      }
    });
    if (dropzone) {
      ['dragenter', 'dragover'].forEach(function (name) {
        dropzone.addEventListener(name, function (event) { event.preventDefault(); dropzone.classList.add('is-dragging'); });
      });
      ['dragleave', 'dragend'].forEach(function (name) {
        dropzone.addEventListener(name, function () { dropzone.classList.remove('is-dragging'); });
      });
      dropzone.addEventListener('drop', function (event) {
        event.preventDefault();
        dropzone.classList.remove('is-dragging');
        if (event.dataTransfer && event.dataTransfer.files.length) {
          input.files = event.dataTransfer.files;
          input.dispatchEvent(new Event('change', { bubbles: true }));
        }
      });
    }
  });

  // ---- Colour input mirrors a text box
  document.querySelectorAll('input[type=color][data-mirror]').forEach(function (c) {
    var t = document.getElementById(c.getAttribute('data-mirror'));
    if (!t) return;
    c.addEventListener('input', function () { t.value = c.value.toUpperCase(); });
    t.addEventListener('input', function () { if (/^#[0-9a-fA-F]{6}$/.test(t.value)) c.value = t.value; });
  });

  // ---- Permission matrix helpers
  document.querySelectorAll('[data-check-all]').forEach(function (b) {
    b.addEventListener('click', function (e) {
      e.preventDefault();
      var box = b.closest('.perm-box');
      var inputs = box.querySelectorAll('input[type=checkbox]');
      var allOn = Array.prototype.every.call(inputs, function (i) { return i.checked; });
      inputs.forEach(function (i) { i.checked = !allOn; });
    });
  });

  // ---- Auto-dismiss flash
  setTimeout(function () {
    document.querySelectorAll('.flash .alert-success').forEach(function (a) { a.classList.remove('show'); a.style.display = 'none'; });
  }, 6000);

  // ---- Charts (Chart.js is loaded only on the dashboard)
  window.cureChart = function (id, type, labels, datasets, opts) {
    var el = document.getElementById(id);
    if (!el || !window.Chart) return;
    var base = {
      responsive: true, maintainAspectRatio: false,
      plugins: { legend: { display: type === 'doughnut', position: 'bottom', labels: { boxWidth: 12, usePointStyle: true } } },
      scales: type === 'doughnut' ? {} : { y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: '#EDF2EF' } }, x: { grid: { display: false } } }
    };
    new window.Chart(el, { type: type, data: { labels: labels, datasets: datasets }, options: Object.assign(base, opts || {}) });
  };
})();
