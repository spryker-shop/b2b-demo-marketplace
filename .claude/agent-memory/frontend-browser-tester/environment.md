---
name: Environment & login notes
description: URLs, login credentials, build commands, and browser quirks for the Spryker B2B Demo Marketplace
type: project
---

## URLs

- Storefront: http://yves.eu.spryker.local (accessible without login on homepage)
- Backoffice: http://backoffice.eu.spryker.local

## Login credentials

- Storefront: sonia@spryker.com / change123
- Backoffice: admin@spryker.com / change123

## Build

- Assets build command: `npm run yves` (from project root)
- Compiled CSS locations:
    - `public/Yves/assets/current/default/css/yves_default.critical.css`
    - `public/Yves/assets/current/default/css/yves_default.app.css` (loaded as non-critical via yves_default.non-critical.css link)

## Browser quirks

- After building, always do a hard reload (ignoreCache: true) before inspecting computed styles — the browser caches the old critical.css aggressively.
- `navigate_page type=reload` with ignoreCache can time out. Use `type=url` with the full URL and `ignoreCache: true` and `timeout: 30000` instead.
- `evaluate_script` causes the page to lose focus from inputs. To verify `:focus-within` styles: use `mcp__chrome-devtools__click` with the input uid from the snapshot, then IMMEDIATELY call evaluate_script — focus will still be held for that synchronous read.
- `type_text` does NOT reliably fire the `input` event on Spryker web components — gives false-negatives for JS-driven visibility toggles (e.g. clear button never appears). Use `fill()` instead, which fires `input` correctly. Alternatively dispatch `new Event('input', { bubbles: true })` manually via `evaluate_script`.
- To focus and activate the desktop search input when it has zero BoundingClientRect (happens at first query), use: `document.querySelectorAll('.search-form__input')[1].focus(); document.querySelectorAll('.search-form__input')[1].click()` — then follow with `type_text`. Suggestions appear after network round-trip; wait for "circulation pump" text.

## Page structure — search instances (updated: 2 instances)

- Homepage renders TWO suggest-search elements (refactored from previous 5).
- Index 0: mobile/drawer instance, `input-class-name="js-search-form__input--mobile"`.
- Index 1: desktop instance, `input-class-name="js-search-form__input--desktop"`.
- Use class-based selectors: `.js-search-form__input--desktop` / `.js-search-form__input--mobile`.

## CSS cascade: critical vs app

- `yves_default.critical.css` is loaded first. If it contains a stale rule, it wins over `yves_default.app.css` even though app.css is correct on disk.
- Always verify which stylesheet the browser actually loaded by checking `document.styleSheets` entries, not just the file on disk.

## Stale browser-process CSS cache — workaround

- Even with `ignoreCache: true` on navigation, the Chrome process can hold a stale CSSOM for `yves_default.critical.css` that omits new properties from recent builds.
- Symptom: `getComputedStyle()` returns old values; `curl` of the same URL returns the correct file; the CSSOM rule object is missing properties that ARE in the file on disk.
- Fix: inject a fresh copy via `fetch(..., { cache: 'no-store' })` → disable the `<link>` tag → append a `<style>` tag with the fetched text. This correctly resolves computed styles for the remainder of the page session.
- Pattern to use at the top of any check that inspects recently-changed CSS:
    ```js
    const resp = await fetch('/assets/current/default/css/yves_default.critical.css', { cache: 'no-store' });
    const text = await resp.text();
    document.querySelector('link[href*="critical.css"]').disabled = true;
    const s = document.createElement('style');
    s.textContent = text;
    document.head.appendChild(s);
    ```

## URL routing note (confirmed 2026-04-02)

- `yves.de.b2b-demo-marketplace.local` — does NOT resolve (ERR_NAME_NOT_RESOLVED).
- `yves.de.spryker.local` — resolves but returns 502 Bad Gateway.
- `yves.eu.spryker.local` — the working storefront URL. Always use this.

## Webpack lazy chunk cache-busting (confirmed 2026-04-02)

- `ignoreCache: true` on navigation does NOT bust webpack's lazy `.js` chunk cache — those are served from HTTP disk cache (transferSize: 0).
- To force re-fetch of lazy chunks after a build: use `initScript` that patches `document.createElement` to append `?_v=Date.now()` to every `.js` src set via property assignment (webpack's chunk loader uses the `src` property setter on injected `<script>` tags).
- Pattern (use as `initScript` on `navigate_page`):
    ```js
    const _orig = document.createElement.bind(document);
    document.createElement = function (tag) {
        const el = _orig(tag);
        if (tag.toLowerCase() === 'script') {
            Object.defineProperty(el, 'src', {
                set(val) {
                    if (typeof val === 'string' && val.includes('.js') && !val.includes('_v=')) {
                        val = val + (val.includes('?') ? '&' : '?') + '_v=' + Date.now();
                    }
                    el.setAttribute('src', val);
                },
                get() {
                    return el.getAttribute('src') || '';
                },
                configurable: true,
            });
        }
        return el;
    };
    ```
- This must be applied BEFORE the page loads (via `initScript`), not via `evaluate_script` after load.
- Confirm it worked by checking `transferSize > 0` in `performance.getEntriesByType('resource')` for the chunk URL.

## Targeting search inputs

- Use `document.querySelector('.js-search-form__input--desktop')` for the desktop input.
- Use `document.querySelector('.js-search-form__input--mobile')` for the drawer input.

## Browser automation limitations (updated 2026-04-03)

- Chrome DevTools MCP tools (`mcp__chrome-devtools__*`) are NOT available by default
- Require `workbench.browser.enableChatTools` VS Code setting to be enabled
- Without these tools: cannot programmatically navigate, take screenshots, inspect DOM, or evaluate scripts
- Fallback verification approach when automation unavailable:
    1. Use `open_browser_page` to open browser manually
    2. Perform comprehensive code review against requirements
    3. Provide manual verification checklist with specific:
        - DevTools console commands to paste and run
        - CSS properties to inspect in Elements panel
        - Visual checks to perform
        - Expected vs actual comparisons
    4. Request user to manually verify and report back
- This approach verified effective for: navigation-multilevel Figma design verification (2026-04-03)
