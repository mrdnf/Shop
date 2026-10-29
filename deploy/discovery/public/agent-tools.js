// Read-only public information. No session, credentials, account data, or mutations.
(async function () {
  const context = document.modelContext || navigator.modelContext;
  if (!context || typeof context.registerTool !== 'function') return;
  try {
    await context.registerTool({
      name: 'get_public_product_information',
      description: 'Read this website\'s public product summary, features, links, and access requirements. Does not access signed-in data.',
      inputSchema: {type: 'object', properties: {}, additionalProperties: false},
      annotations: {readOnlyHint: true},
      execute: async function () {
        const response = await fetch('/.well-known/site-info.json', {credentials: 'omit', headers: {Accept: 'application/json'}});
        if (!response.ok) throw new Error('Public product information is unavailable.');
        return {content: [{type: 'text', text: JSON.stringify(await response.json())}]};
      }
    });
  } catch (_) { /* Unsupported draft APIs must not affect normal browsing. */ }
})();
