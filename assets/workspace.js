(() => {
 'use strict';
 const data = window.psmWorkspace;
 if (!data) return;
 try { sessionStorage.setItem(data.key, data.group); } catch (_) { /* URL context still works without storage. */ }
 const group = () => { try { return sessionStorage.getItem(data.key) || data.group; } catch (_) { return data.group; } };
 const contextual = value => {
  const url = new URL(value, window.location.href);
  const admin = new URL(data.admin);
  if (url.origin === admin.origin && url.pathname.startsWith(admin.pathname) && !url.searchParams.has('action') && !url.searchParams.has('_wpnonce')) url.searchParams.set('psm_context', group());
  return url.href;
 };
 window.psmContextUrl = contextual;
 const prepareLink = event => {
  const link = event.target.closest?.('a[href]');
  if (!link || link.getAttribute('href').startsWith('#') || link.hasAttribute('download')) return;
  link.href = contextual(link.href);
 };
 document.addEventListener('pointerdown', prepareLink, true);
 document.addEventListener('click', prepareLink, true);
 document.addEventListener('auxclick', prepareLink, true);
 document.addEventListener('submit', event => {
  const form = event.target;
  const url = new URL(form.getAttribute('action') || location.href, location.href);
  const admin = new URL(data.admin);
  if (url.origin !== admin.origin || !url.pathname.startsWith(admin.pathname)) return;
  let field = form.querySelector('input[name="psm_context"]');
  if (!field) { field = document.createElement('input'); field.type = 'hidden'; field.name = 'psm_context'; form.append(field); }
  field.value = group();
 }, true);
})();
