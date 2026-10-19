(() => {
 'use strict';
 let opener = null;
 const toggle = document.querySelector('[data-psm-show-inactive]');
 if (toggle) {
  const update = () => {
   document.querySelectorAll('[data-psm-inactive]').forEach(row => { row.hidden = !toggle.checked; });
   const empty = document.querySelector('[data-psm-empty]');
   if (empty) empty.hidden = toggle.checked || !!document.querySelector('.psm-table tbody tr:not([data-psm-inactive])');
  }; toggle.addEventListener('change', update); update();
 }
 document.querySelectorAll('[data-psm-dialog]').forEach(link => link.addEventListener('click', event => {
  const dialog = document.getElementById(link.dataset.psmDialog);
  if (!dialog || typeof HTMLDialogElement === 'undefined') return;
  try { dialog.hidden = false; HTMLDialogElement.prototype.showModal.call(dialog); opener = link; event.preventDefault(); }
  catch (_) { dialog.hidden = true; }
 }));
 document.querySelectorAll('.psm-dialog').forEach(dialog => {
  dialog.addEventListener('close', () => { dialog.hidden = true; opener?.focus(); });
  dialog.addEventListener('click', event => { if (event.target === dialog) HTMLDialogElement.prototype.close.call(dialog); });
 });
 document.querySelector('[data-psm-copy]')?.addEventListener('click', () => { const report = document.getElementById('psm-diagnostics'); report.focus(); report.select(); });
})();
