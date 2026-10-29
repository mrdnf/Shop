# Public discovery and indexing: shop.syseagles.com

Public scope: product information. Private application records, login screens, user files, candidate results and administration are excluded from sitemaps and receive noindex headers. This is indexing policy, not authorization: existing application permissions must remain enforced.

`public/` contains the public product page, robots.txt, XML sitemap, llms.txt, read-only product metadata/OpenAPI, API catalog, agent guide, a hashed public Agent Skill, and an AI resource catalog. `agent-tools.js` registers one read-only WebMCP tool when the browser implements the draft API. It fetches public product information with credentials omitted.

The API catalog advertises only the implemented, read-only product information API. No OAuth issuer, MCP server, A2A agent or payment protocol is fabricated. Search/retrieval is permitted for public pages; model training is opted out by default.

## CloudPanel

Install `nginx-http.conf` at HTTP scope and `nginx-server.conf` inside each server block for this hostname. Production paths in those files target `/home/shop/htdocs/shop.syseagles.com/deploy/discovery/public`. Root-managed copies are installed under `/etc/nginx`; do not load writable application configuration directly. Test with `nginx -t` before reload, and retain backups for rollback.

Include `php-head.php` before `</head>` in the PHP templates listed in `site.json`. It emits canonical, social and structured metadata on allowlisted public pages, and noindex for private screens. Static HTML pages receive the equivalent generated metadata. The source may have a different framework from the current PHP deployment; these static discovery artifacts and Nginx integration remain framework-independent.

Markdown negotiation converts only the allowlisted public page/sign-in representations. It never converts authenticated records. The endpoint returns the correct MIME type and varies on Accept. JSON discovery endpoints support anonymous GET/HEAD only and contain no secrets. The real application's APIs are unchanged.

## Maintenance

Review `site.json` before publishing. Add only real, public, canonical pages to the sitemap. Regenerate the skill digest after changing SKILL.md. Keep descriptions consistent with the public product. Do not publish demo storefront products, account URLs, share tokens or fabricated ratings/prices. Submit `https://shop.syseagles.com/sitemap.xml` in the verified syseagles.com Search Console domain property.
