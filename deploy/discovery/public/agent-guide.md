# Public agent access: SysEagles Shop

Read the public description at https://shop.syseagles.com/about/ or fetch https://shop.syseagles.com/.well-known/site-info.json. The JSON endpoint is read-only, requires no account, and returns only product metadata.

Request `Accept: text/markdown` for the public homepage, sign-in screen, or product page to receive an available Markdown representation. Private routes do not support this conversion.

The public API is documented at https://shop.syseagles.com/openapi.json. It has no write operations. Account, employee, student, customer, mailbox, file, assessment and financial data are outside this API. Existing application authentication and permissions still apply. Never send credentials to discovery endpoints.

No public MCP server, A2A agent, OAuth issuer, agent registration service or agent payment protocol is advertised.
