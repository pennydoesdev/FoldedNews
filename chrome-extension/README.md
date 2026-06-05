# FoldedNews QA — Chrome Extension

A **lightweight, internal** page-level QA tool for FoldedNews newsroom pages.
Manifest V3. It runs checks **on demand** (when you click it), **only on
configured domains**, entirely in your browser. It collects **no reader data**.

## Install (unpacked)

1. Visit `chrome://extensions`, enable **Developer mode**.
2. **Load unpacked** → select this `chrome-extension/` folder.
3. Open the extension **Options** and set your newsroom domains (e.g.
   `foldednews.test`) and, optionally, the health endpoint.

## Use

Open a newsroom page → click the extension → **Run checks** → **Export JSON**.

## Checks

Title, meta description, canonical, OpenGraph, `NewsArticle` schema, author
name/image, published/updated dates, featured image, image alt text, no public
`wp-content/uploads/` (or `app/uploads/`) URLs, CDN/S3 URLs used, paywall
component, newsletter module, ad slots, map/chart blocks, live blog, broken
images, heading order, and missing button labels.

## Privacy / scope

- Uses `activeTab` + `scripting` only — no background content script, no
  host_permissions, no network calls except the optional health endpoint you
  configure.
- Refuses to run on any domain not in your allow-list.

## Known limitations

- Console-error counting requires a persistent hook and is not captured by the
  one-shot check (run DevTools for that).
- This is an internal QA aid, not a Lighthouse replacement.
