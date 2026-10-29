---
name: public-product-information
description: "Read public product information and access requirements for SysEagles Shop without accessing private application data."
---

# Public product information

1. Read https://shop.syseagles.com/.well-known/site-info.json for product features, public links and access boundaries.
2. Read https://shop.syseagles.com/about/ or https://shop.syseagles.com/about.md for the human-readable product description.
3. Use https://shop.syseagles.com/sitemap.xml to discover the curated public pages.
4. For an account task, use the normal sign-in workflow only when the user authorizes it. This skill provides no authentication, account delegation or permission to access private data.
5. Do not enumerate private paths, retrieve uploads, or submit changes based on these discovery documents.
