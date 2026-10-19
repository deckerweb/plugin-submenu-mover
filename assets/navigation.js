(() => {
 'use strict';
 document.querySelector('[data-psm-switch]')?.addEventListener('change', event => {
  if (event.target.value) window.location.assign(event.target.value);
 });
 const root = document.querySelector('.psm-navigation');
 if (!root) return;
 const input = root.querySelector('[data-psm-search]');
 const cards = [...root.querySelectorAll('[data-psm-card]')];
 const openState = new Map();
 const normalize = text => text.toLocaleLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
 const filter = () => {
  const query = normalize(input.value.trim());
  const terms = query.split(/\s+/).filter(Boolean); let count = 0;
  cards.forEach(card => {
   let matches = false;
   card.querySelectorAll('[data-psm-page]').forEach(row => {
    const label = normalize(row.querySelector('[data-psm-destination]').dataset.psmLabel + ' ' + (row.querySelector('[data-psm-destination]').dataset.psmKeywords || ''));
    row.hidden = !terms.every(term => label.includes(term));
    matches ||= !row.hidden;
   });
   card.hidden = !matches;
   if (matches) count++;
   card.querySelectorAll('details').forEach(details => {
    if (query) { if (!openState.has(details)) openState.set(details, details.open); details.open = true; }
    else if (openState.has(details)) { details.open = openState.get(details); openState.delete(details); }
   });
  });
  root.querySelector('[data-psm-clear]').hidden = !query;
  root.querySelector('[data-psm-empty]').hidden = count !== 0 || !query;
  root.querySelector('[data-psm-search-status]').textContent = (window.psmNavigationLabels?.matches || '%d').replace('%d', String(count));
 };
 const clear = () => { input.value = ''; filter(); input.focus(); };
 input?.addEventListener('input', filter);
 input?.addEventListener('keydown', event => {
  if (event.key === 'Escape' && input.value) { event.preventDefault(); event.stopPropagation(); clear(); }
  if (event.key === 'Enter' && input.value.trim()) {
   event.preventDefault();
   const card = cards.find(item => !item.hidden);
   const row = card && [...card.querySelectorAll('[data-psm-page]')].find(item => !item.hidden);
   if (row) window.location.assign(row.querySelector('[data-psm-destination]').href);
  }
 });
 root.querySelector('[data-psm-clear]')?.addEventListener('click', clear);
 root.querySelector('[data-psm-reset]')?.addEventListener('click', clear);
 let pending = false;
 root.addEventListener('submit', async event => {
  const form = event.target;
  if (!window.fetch || form.elements.psm_navigation?.value !== 'pin') return;
  event.preventDefault();
  if (pending) return;
  pending = true;
  const favoriteSection = root.querySelector('[data-psm-favorites]');
  const wasFavorite = favoriteSection.contains(document.activeElement);
  const hadFocus = form.contains(document.activeElement);
  const buttons = [...root.querySelectorAll('.psm-pin')];
  buttons.forEach(button => { button.disabled = true; });
  const status = root.querySelector('[data-psm-pin-status]');
  status.textContent = '';
  try {
   const body = new FormData(form);
  if (window.psmWorkspace?.nativePalette) body.set('psm_commands', '1'); body.append('psm_inline', '1');
   const response = await fetch(form.getAttribute('action'), {method:'POST', credentials:'same-origin', body});
   if (!response.ok) throw new Error('request');
   const result = await response.json();
   if (!result.success || !Array.isArray(result.data?.pins)) throw new Error('response');
   root.querySelectorAll('[data-psm-page]').forEach(row => {
    const pin = row.querySelector('.psm-pin');
    const active = result.data.pins.includes(row.dataset.psmPage);
    pin.setAttribute('aria-pressed', String(active));
    pin.querySelector('span').textContent = active ? '★' : '☆';
   });
   const section = root.querySelector('[data-psm-favorites]');
   const list = section.querySelector('ul');
   const fragments = result.data.pins.map(id => {
    const source = [...root.querySelectorAll('[data-psm-page]')].find(row => row.dataset.psmPage === id);
    if (!source) return null;
    const copy = source.cloneNode(true); copy.hidden = false;
    const favorite = result.data.favorites?.find(page => page.id === id);
    copy.querySelector('[data-psm-destination]').textContent = favorite?.context_label || copy.querySelector('[data-psm-destination]').dataset.psmLabel;
    copy.querySelector('button').disabled = false;
    return copy;
   }).filter(Boolean);
   // Keep keyboard focus on the corresponding card when a favorite row is rebuilt.
   const focusId = form.closest('[data-psm-page]').dataset.psmPage;
   list.replaceChildren(...fragments); section.hidden = false;
   section.querySelector('[data-psm-favorites-empty]').hidden = fragments.length > 0;
   const overview = document.querySelector('#toplevel_page_psm-plugin-hub .psm-hub-overview');
   if (overview && Array.isArray(result.data.favorites)) {
    document.querySelectorAll('#toplevel_page_psm-plugin-hub li.psm-menu-favorite').forEach(row => row.remove());
    let previous = overview;
    result.data.favorites.forEach((page, index, pages) => {
     const row = document.createElement('li');
     row.className = 'psm-menu-favorite' + (index === pages.length - 1 ? ' psm-favorite-last' : '');
     const link = document.createElement('a');
     link.href = page.url; link.textContent = '★ ' + page.context_label;
     row.append(link); previous.after(row); previous = row;
    });
   }
   // This fragment is rendered and escaped by our authenticated endpoint.
   if (typeof result.data.personal_controls === 'string') {
    const template = document.createElement('template'); template.innerHTML = result.data.personal_controls;
    const controls = template.content.querySelector('#psm-personal-tools');
    if (controls) root.querySelector('#psm-personal-tools')?.replaceWith(controls);
   }
   if (Array.isArray(result.data.commands)) document.dispatchEvent(new CustomEvent('psm:commands', {detail:result.data.commands}));
   if (wasFavorite) {
    const target = cards.filter(card => !card.hidden).flatMap(card => [...card.querySelectorAll('[data-psm-page]')]).find(row => !row.hidden && row.dataset.psmPage === focusId);
    if (target) { target.closest('details')?.setAttribute('open',''); target.querySelector('button').disabled = false; target.querySelector('button').focus(); }
    else input?.focus();
   }
   status.textContent = window.psmNavigationLabels?.pinSaved || '';
  } catch {
   status.textContent = window.psmNavigationLabels?.pinError || '';
  } finally {
   pending = false;
   root.querySelectorAll('.psm-pin').forEach(button => { button.disabled = false; });
   if (!wasFavorite && hadFocus && form.isConnected) form.querySelector('button').focus();
  }
 });
})();
