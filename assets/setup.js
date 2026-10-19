(() => {
 'use strict';
 const form = document.querySelector('.psm-form-review-hub');
 const tools = form?.querySelector('[data-psm-selection-tools]');
 if (!tools) return;
 const rows = [...form.querySelectorAll('[data-psm-selection-row]')];
 const search = tools.querySelector('[data-psm-group-search]');
 const filter = tools.querySelector('[data-psm-group-filter]');
 const all = form.querySelector('[data-psm-mark-visible]');
 const status = tools.querySelector('[data-psm-selection-summary]');
 const normalize = value => value.toLocaleLowerCase().normalize('NFKD').replace(/[\u0300-\u036f]/g, '');
 const mode = row => row.querySelector('select[name^="groups["]');
 const marked = row => row.querySelector('[data-psm-mark]');
 form.querySelectorAll('.psm-mark-column').forEach(cell => { cell.hidden = false; });
 tools.hidden = false;
 form.classList.add('psm-selection-enhanced');
 const saved = new Map([...form.elements].filter(el => el.name && !['hidden','submit'].includes(el.type)).map(el => [el, el.type === 'checkbox' || el.type === 'radio' ? el.checked : el.value]));
 const update = () => {
  const dirty = [...saved].some(([el, value]) => (el.type === 'checkbox' || el.type === 'radio' ? el.checked : el.value) !== value);
  const indicator = tools.querySelector('[data-psm-unsaved]');
  indicator.textContent = dirty ? window.psmSetupLabels?.dirty : window.psmSetupLabels?.clean;
  indicator.classList.toggle('psm-has-changes', dirty);
  const reasonBox = tools.querySelector('[data-psm-template-reasons]');
  const reasons = JSON.parse(tools.querySelector('[data-psm-template]').selectedOptions[0]?.dataset.reasons || '[]');
  reasonBox.hidden = !tools.querySelector('[data-psm-template]').value;
  reasonBox.querySelector('ul').replaceChildren(...reasons.map(reason => { const li = document.createElement('li'); li.textContent = reason; return li; }));
  const words = normalize(search.value).trim().split(/\s+/).filter(Boolean);
  for (const row of rows) {
   const chosen = Boolean(mode(row).value);
   row.hidden = !words.every(word => normalize(row.dataset.search).includes(word)) || (filter.value === 'selected' && !chosen) || (filter.value === 'unselected' && chosen) || (filter.value === 'review' && row.dataset.review !== '1');
  }
  const visible = rows.filter(row => !row.hidden);
  visible.forEach((row, index) => row.classList.toggle('psm-visible-odd', index % 2 === 0));
  tools.querySelector('[data-psm-template-mark]').disabled = !tools.querySelector('[data-psm-template]').value;
  const count = rows.filter(row => marked(row).checked).length;
  const template = window.psmSetupLabels?.summary || '%1$d / %2$d / %3$d / %4$d';
  status.textContent = template.replace('%1$d', rows.filter(row => mode(row).value).length).replace('%2$d', rows.length).replace('%3$d', visible.length).replace('%4$d', count);
  all.checked = visible.length > 0 && visible.every(row => marked(row).checked);
  all.indeterminate = visible.some(row => marked(row).checked) && !all.checked;
  all.disabled = visible.length === 0;
  tools.querySelector('[data-psm-selection-empty]').hidden = visible.length > 0;
  tools.querySelectorAll('[data-psm-bulk]').forEach(button => { button.disabled = count === 0; });
 };
 search.addEventListener('input', update);
 search.addEventListener('keydown', event => { if (event.key === 'Enter') event.preventDefault(); if (event.key === 'Escape') { event.preventDefault(); search.value = ''; update(); } });
 filter.addEventListener('change', update);
 tools.querySelector('[data-psm-template]').addEventListener('change', update);
 all.addEventListener('change', () => { for (const row of rows.filter(row => !row.hidden)) marked(row).checked = all.checked; update(); });
 form.addEventListener('change', update);
 form.querySelector('[name="excluded_users"]')?.addEventListener('input', update);
 for (const button of tools.querySelectorAll('[data-psm-bulk]')) button.addEventListener('click', () => {
  const action = button.dataset.psmBulk;
  for (const row of rows) {
   if (marked(row).checked) mode(row).value = action === 'exclude' ? '' : action === 'only' ? (mode(row).value || 'link') : action;
   else if (action === 'only') mode(row).value = '';
  }
  update();
 });
 tools.querySelector('[data-psm-template-mark]').addEventListener('click', () => {
  const selected = tools.querySelector('[data-psm-template]').selectedOptions[0];
  const ids = JSON.parse(selected.dataset.groups || '[]');
  for (const row of rows) marked(row).checked = ids.includes(row.dataset.psmSelectionRow);
  update();
  status.textContent += ' ' + (window.psmSetupLabels?.template || '%d').replace('%d', ids.length);
 });
 update();
})();
