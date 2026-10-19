(() => {
 'use strict';
 const root = document.querySelector('[data-psm-user-picker]');
 const ui = root?.querySelector('[data-psm-user-enhanced]');
 const config = window.psmUserPicker;
 if (!ui || !config || !window.fetch || !window.AbortController) return;
 const field = root.querySelector('[name="excluded_users"]');
 const chosen = new Map(JSON.parse(root.dataset.selected).map(user => [user.id, user.label]));
 const selectedList = root.querySelector('[data-psm-user-selected]');
 const results = root.querySelector('#psm-user-results');
 const search = root.querySelector('#psm-user-search');
 const status = root.querySelector('[data-psm-user-status]');
 const more = root.querySelector('[data-psm-user-more]');
 let controller, timer, generation = 0, page = 1;
 ui.hidden = false;
 root.querySelector('[data-psm-user-fallback]').hidden = true;
 const render = (changed = false) => {
  selectedList.replaceChildren(...[...chosen].map(([id, label]) => {
   const li = document.createElement('li'); const button = document.createElement('button');
   button.type = 'button'; button.className = 'button'; button.textContent = label + ' ×';
   button.setAttribute('aria-label', config.remove.replace('%s', label));
   button.addEventListener('click', () => { chosen.delete(id); render(true); search.focus(); });
   li.append(button); return li;
  }));
  root.querySelector('[data-psm-user-none]').hidden = chosen.size > 0;
  for (const input of results.querySelectorAll('input')) { input.checked = chosen.has(Number(input.value)); input.disabled = !input.checked && chosen.size >= 200; }
  if (changed) {
   field.value = [...chosen.keys()].sort((a,b) => a-b).join(', ');
   status.textContent = config.selected.replace('%d', chosen.size);
   field.dispatchEvent(new Event('change', {bubbles:true}));
  }
 };
 const load = async (append = false) => {
  controller?.abort(); controller = new AbortController(); const current = ++generation;
  const wanted = append ? page + 1 : 1;
  const restoreMoreFocus = append && document.activeElement === more;
  const previousCount = results.children.length;
  more.hidden = true; more.disabled = true; status.textContent = config.loading; results.setAttribute('aria-busy', 'true');
  if (!append) results.replaceChildren();
  try {
   const body = new URLSearchParams({action:'psm_user_search', nonce:config.nonce, search:search.value, page:String(wanted)});
   const response = await fetch(config.url, {method:'POST', credentials:'same-origin', body, signal:controller.signal});
   if (!response.ok) throw new Error('request');
   const data = await response.json(); if (!data.success || !Array.isArray(data.data?.users)) throw new Error('response');
   if (current !== generation) return;
   for (const user of data.data.users) {
    const li = document.createElement('li'), label = document.createElement('label'), input = document.createElement('input'), text = document.createElement('span');
    input.type = 'checkbox'; input.value = String(user.id); text.textContent = user.label;
    input.addEventListener('change', () => {
     if (input.checked && chosen.size >= 200) { input.checked = false; status.textContent = config.limit; return; }
     if (input.checked) chosen.set(user.id, user.label); else chosen.delete(user.id);
     render(true);
    });
    label.append(input, text); li.append(label); results.append(li);
   }
   page = wanted; more.hidden = !data.data.more;
   status.textContent = data.data.users.length ? config.selected.replace('%d', chosen.size) : config.empty;
   render();
   if (restoreMoreFocus) results.children[previousCount]?.querySelector('input')?.focus();
  } catch (error) {
   if (error.name !== 'AbortError' && current === generation) status.textContent = config.error;
  } finally { if (current === generation) { results.removeAttribute('aria-busy'); more.disabled = false; } }
 };
 search.addEventListener('input', () => {
  clearTimeout(timer); controller?.abort(); generation++; more.hidden = true; more.disabled = true;
  timer = setTimeout(() => load(), 250);
 });
 search.addEventListener('keydown', event => {
  if (event.key === 'Enter') event.preventDefault();
  if (event.key === 'Escape') { event.preventDefault(); search.value = ''; clearTimeout(timer); load(); }
 });
 more.addEventListener('click', () => load(true));
 render(); load();
})();
