(() => {
 'use strict';
 const data = window.psmWorkspace;
 if (!data || !window.wp?.commands || !window.wp?.data) return;
 const store = wp.commands.store || 'core/commands';
 const dispatcher = wp.data.dispatch(store);
 if (!dispatcher?.registerCommand) return;
 const navigate = value => window.psmContextUrl ? window.psmContextUrl(value) : value;
 const register = items => {
 for (const item of items) {
  dispatcher.unregisterCommand?.(item.id);
  dispatcher.registerCommand({name:item.id,label:item.label,keywords:item.keywords || [],category:item.fields ? 'command' : 'view',callback:({close} = {}) => {
   close?.();
   if (!item.fields) { window.location.assign(navigate(item.url)); return; }
   const form = document.createElement('form'); form.method = 'post'; form.action = navigate(item.url);
   for (const [name,value] of Object.entries(item.fields)) { const input = document.createElement('input'); input.type = 'hidden'; input.name = name; input.value = value; form.append(input); }
   const context = document.createElement('input'); context.type = 'hidden'; context.name = 'psm_context'; context.value = new URL(navigate(item.url)).searchParams.get('psm_context') || data.group; form.append(context);
   document.body.append(form); HTMLFormElement.prototype.submit.call(form);
  }});
 }
 };
 register(data.commands);
 document.addEventListener('psm:commands', event => register(event.detail));
})();
