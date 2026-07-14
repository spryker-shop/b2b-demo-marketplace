# Ready-made options — investigation & recommendation (POC iteration 1)

## What ShopUi already gives us

ShopUi is a server-rendered Twig storefront with a custom-element component model
(`Component extends HTMLElement`, app-driven lifecycle, webpack-discovered
`index.ts` registration, idempotent `mount()`). Any candidate has to *complement*
that — not bring a second component model, a second router, or a client renderer.

## Options reviewed

### Signal libraries (state layer)

| Library | Size | Notes |
| --- | --- | --- |
| `@preact/signals-core` | ~1.5 kB | Framework-agnostic signals + `effect`/`computed`; no DOM opinions; TS-first. **Adopted by this POC.** |
| TC39 Signals proposal / polyfill | ~5 kB | The future standard, API still moving; revisit once stage advances — migration from preact signals is mechanical. |
| RxJS | already in repo (Zed/Angular side) | Streams, not state; too heavy an abstraction for "a value changed, update the DOM", and Yves bundles don't ship it today. |
| nanostores / valtio / zustand | 1–3 kB | Equivalent capability; smaller ecosystems; no advantage over preact signals for our use. |

### DOM/behavior frameworks

- **Stimulus (Hotwire)** — controllers + values + targets bound via `data-*`. It is
  a *component model*, i.e. exactly what ShopUi's custom elements already do.
  Adopting it means two competing lifecycles on one page. **No.**
- **Turbo (Hotwire)** — HTML-over-the-wire page/frame swaps. Orthogonal to state
  reactivity: it re-renders server HTML instead of updating values. Interesting as
  a *future* iteration for partial re-render (Turbo-Frame-like fetch + swap +
  `mount()`), but it replaces innerHTML wholesale and needs careful integration
  with the app-driven mount lifecycle. **Not now; pattern worth borrowing.**
- **htmx** — attribute-driven AJAX partial swaps. Same story as Turbo: great for
  server-rendered swaps, but it writes `innerHTML` (against the POC constraint),
  and every swap must re-run `mount()` and re-bind. ShopUi already has ajax-renderer
  patterns covering this niche. **No.**
- **Alpine.js** — inline `x-data`/`x-bind` expressions evaluated from markup
  (`new Function`-style). CSP/injection surface, duplicates the component model,
  and its expression language in Twig templates gets ugly fast. **No.**
- **Lit / petite-vue / Solid** — client renderers (template → DOM). ShopUi renders
  on the server; we only need value → DOM patches. Overkill and a second rendering
  paradigm. **No.**

## Recommendation

Keep the ShopUi component model as-is and adopt the thinnest possible state layer:

1. **`@preact/signals-core` as the reactive engine** (this POC) — tiny, stable,
   framework-agnostic, and the store/directive code on top is ~150 lines we fully
   control. If the TC39 Signals standard lands, swapping the engine is contained
   to `store.ts`.
2. **Declarative `data-bind-*` directives + `reactive-bind`** as the only markup
   API — Twig stays the single source of rendered HTML, output writes are limited
   to `textContent`/attributes/`classList` (no injection surface), and existing
   components opt in through their normal `attributes` contract without code
   changes.
3. **One server payload per page** (templates contribute via `setGlobalStore()` /
   `setGlobalStoreByName()`, the layout renders `getGlobalStore()` once) instead
   of per-widget JSON islands.
4. For iteration 2+ (server re-render of a bound region), borrow the Turbo Frames
   *pattern* — fetch a partial, swap a scoped container, re-run `mount()` — rather
   than adopting Turbo itself; ShopUi's ajax-renderer already points this way.
