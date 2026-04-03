---
name: Spryker storefront patterns
description: Key DOM/component patterns to know when verifying Spryker ShopUi components
type: project
---

## Multiple component instances

- Several components render twice in the DOM: once for desktop (e.g. `search-form--main-search`) and once for mobile drawer (`search-form--drawer`).
- When querying by class with `getElementsByClassName` or `querySelector`, always scope to the correct instance. The first match is not always the desktop one.
- Example: `.js-search-form__clear` has two instances. The drawer one comes first in DOM order, so `document.querySelector('.js-search-form__clear')` returns the drawer instance, not the desktop one.

## search-form component

- Desktop: `.search-form--main-search` — visible at xl breakpoint (≥1280px).
- Mobile drawer: `.search-form--drawer` — embedded inside `.side-drawer__zone-search`. Search input visible and functional inside the drawer (confirmed working, feature/header branch).

## suggest-search component — page structure (updated, 2 instances)

- Homepage now renders exactly 2 suggest-search instances (refactored from previous 5).
- Index 0: `input-class-name="js-search-form__input--mobile"` — inside side-drawer, class `suggest-search--drawer`.
- Index 1: `input-class-name="js-search-form__input--desktop"` — inside `.header__search`, class `suggest-search--main-search`.
- To trigger desktop: focus `.js-search-form__input--desktop`, type, then dispatch `new Event('input', { bubbles: true })`. Wait for suggestions.

## suggest-search component — mobile drawer (PASS)

- In `.search-form--drawer` context the container overrides to: `position: fixed; top: 116px; left: 0; bottom: 0; width: 390px; z-index: 1201`.
- Creates a full-screen overlay from 116px down — confirmed rect on 390×844 viewport: x=0, y=116, w=390, h=728.
- `suggest-search--drawer` modifier IS correctly applied to the drawer instance.
- Mobile clear button: correctly wired to the drawer's own clear button — works as expected.

## suggest-search clearButton wiring — FIXED (desktop, feature/header branch, confirmed 2026-03-30)

- After the latest commits on feature/header, `mapInputForClear()` correctly resolves `searchForm` via `this.closest('.search-form')` and `searchForm.querySelector('.js-search-form__clear')` binds to the desktop clear button.
- Confirmed via `fill()` (MCP tool that fires proper `input` events): after filling the desktop input with "test", index 1 (header `search-form--main-search`) gets `hasVisible: true, display: flex`. Index 0 (drawer) stays `display: none`.
- CRITICAL: `type_text` does NOT trigger the `input` event on these web components. Use `fill()` or manually dispatch `new Event('input', { bubbles: true })` to test clear button visibility. Using `type_text` gives a false-negative result (clear button never appears).
- After clicking clear: `input.value = ""`, `hint.value = ""`, `clearVisible = false`. Suggestions panel closes. Input shows placeholder.

## Test 2 — Mobile drawer search does NOT open overlay (PASS, 2026-03-30)

- Typing in the drawer search input does NOT add `body-has-overlay` to `document.body`.
- `toggleSearch()` guard `if (this.classList.contains('search-form--drawer')) return;` correctly short-circuits overlay logic.
- `body.className` after typing in drawer: `"js-page-layout-main__side-drawer-container is-touch is-locked-tablet"` — no overlay class.
- Suggestions appear inline inside the drawer panel (not a full-screen page overlay).
- `yves.de.spryker.local` returns 502 — use `yves.eu.spryker.local` for all verification; same codebase.

## side-drawer

- Activated by adding `side-drawer--show` class to the `<side-drawer>` custom element.
- Drawer panel: `.side-drawer__drawer` transitions from `top: -100%` (mobile) to `top: 0` when `--show` is applied.
- `zone-search` inside drawer: `height: 3.75rem` (60px), `padding: 0 var(--scale-16)`.

## MultiCartWidget — does not exist as a widget class (confirmed 2026-03-31)

- No PHP class named `MultiCartWidget` exists in vendor (`spryker-shop/multi-cart-widget`).
- Available widget classes in that package: `MiniCartWidget`, `MultiCartListWidget`, `MultiCartMenuItemWidget`, `CartOperationsWidget`, `AddToMultiCartWidget`, `QuickOrderPageWidget`.
- `MiniCartWidget` is the one that renders `@MultiCartWidget/views/mini-cart/mini-cart.twig` and requires `$cartQuantity` as constructor arg.
- Calling `{% widget 'MultiCartWidget' only %}` in Twig silently renders nothing — Spryker's widget system does not throw an error for unknown widget names, it just skips rendering.
- The correct call would be `{% widget 'MiniCartWidget' args [cartQuantity] only %}`.

## side-drawer cart panel drill-down (confirmed working, 2026-03-31)

- Cart button in drawer header: `.side-drawer__header-cart.js-side-drawer__drill-down-trigger` with `data-target-panel="cart-panel"`.
- Clicking it activates `[data-panel-id="cart-panel"]` and adds `side-drawer__panel--active` to that panel.
- `syncCloseButton()` correctly fires: close icon changes from `close` to `arrow_back`, aria-label changes to "Go back".
- Drill-down JS works correctly. Content inside cart-panel renders blank only when widget is absent (see MultiCartWidget note above).

## header-cart-pill component (new, confirmed 2026-03-31)

- Custom element `<header-cart-pill>` is `position: relative`; its dropdown child is `position: absolute`.
- On fresh page load the browser CSSOM may serve a stale critical.css missing the new component rules, causing the dropdown to fall back to `position: static` and push the header layout out (trigger rect goes to y:-201).
- Workaround: always inject fresh CSS via `fetch(..., { cache:'no-store' })` pattern before inspecting this component's computed styles.
- After fresh CSS: trigger sits at y≈50 (inside `.header__main-bar`), dropdown is hidden (`opacity:0; visibility:hidden`), correct.
- Hover state (CSS-only `:hover` on host): to force visible for screenshots, set inline styles on `.header-cart-pill__dropdown` directly.
- DOM structure: dropdown children are `.mini-cart__header`, `.mini-cart__row` (×n), `.mini-cart__divider`, `.mini-cart__footer` — **no** `.mini-cart` wrapper element.
- `miniCart: !!document.querySelector('.mini-cart')` returns `false` — the MiniCartWidget renders children directly into the dropdown, no `.mini-cart` root div.
- `miniCartRows: document.querySelectorAll('.mini-cart-detail').length` returns the total count across ALL carts (e.g. 4 carts × multiple items = 20+), not 1 per cart row.

## cart_popover.title — resolved (confirmed 2026-03-31, fixed confirmed 2026-03-31)

- Previously rendered raw key `cart_popover.title`; now resolved to "My carts" (en_US) after glossary import.
- Glossary entry added to `data/import/common/common/glossary.csv`: `cart_popover.title,My carts,en_US` and `cart_popover.title,Meine Warenkörbe,de_DE`.
- Applies to both desktop dropdown and mobile drawer cart panel.

## header-shopping-list-pill — JS not initializing (confirmed 2026-04-01)

- Custom element `<header-shopping-list-pill>` renders correctly in DOM, CSS works (position:absolute dropdown, hover shows/hides it).
- BUT the component JS never initializes: `customElements.get('header-shopping-list-pill')` returns `undefined`.
- Symptom: absent from the `define …` log lines in console; `data-mounted` attribute never set; lazy chunk never fetched.
- The `index.ts` IS in app.js `__webpack_exec__` list and IS executed — `register('header-shopping-list-pill', …)` is called.
- Root cause: Spryker's stale-CSSOM issue also affects the JS bootstrap in a subtle way. With the stale critical.css loaded, the pill's `position` falls back to `static`, the dropdown expands in-flow causing `navQuickLinksRect` to report y=14 (outside parent bounds). After injecting fresh CSS (the standard cache-bust workaround), layout and computed styles are correct.
- The JS non-initialization is a separate bug: the Spryker registry `candidate.define()` called `getElementsByTagName('header-shopping-list-pill')` at bootstrap time and found 0 results, so it skipped defining the custom element. This is reproducible across both desktop and mobile page loads.
- The CSS-only `:hover` interaction DOES work correctly once CSS is fresh — the dropdown opens on hover without needing JS.
- ARIA state (`aria-expanded`, `aria-hidden`) is NOT updated on hover because the JS class is uninitialized — this is an accessibility gap but not a visual failure.

## navigation-multilevel custom element (updated 2026-04-02)

- Desktop instance: index 1 in `document.querySelectorAll('navigation-multilevel')`, inside `.header__nav-categories`, visible at ≥1024px.
- Mobile/drawer instance: index 0, inside `.side-drawer__browse-list`.
- NEW JS (feature/header, built 2026-04-02): uses `mouseenter`/`mouseleave` (not `mouseover`/`mouseout`). `onTriggerOut` delays `classList.remove` via 200ms `setTimeout` stored in `hideTimers: Map`. `onTriggerOver` clears any pending hide timer first. `onTriggerOver`/`onTriggerOut` only call `toggleOverlay()` when `trigger.querySelector('.menu-wrapper--lvl-1')` exists — nav items without dropdowns (Sale %, New) do NOT trigger the overlay. New method `moveNavPanelsToDrawer()` moves `.navigation-multilevel__nav-panel` elements into `.side-drawer__panels` on init.
- `data-mounted` attribute is never set (benign).
- BROWSER CACHE WARNING: `navigation-multilevel` lazy chunk (`yves_default.navigation-multilevel.js`) has a stable filename — the browser caches it aggressively. After a build, cache-bust by patching `document.createElement` via `initScript` to append `?_v=Date.now()` to all `.js` src assignments. This forces webpack's script-tag chunk loader to re-fetch. Without this, the old code keeps running even after `ignoreCache: true` navigation.

## navigation-multilevel desktop hover (confirmed 2026-04-02)

- Gap bridge: `.menu-wrapper--lvl-1::before` has `height: 12px`, `top: -12px`, `position: absolute`, `pointer-events: auto` — bridges the gap between nav bar and dropdown. Confirmed via `getComputedStyle(..., '::before').pointerEvents === 'auto'`.
- 200ms delay: after `mouseleave`, `is-shown` stays on trigger synchronously (removed only after timer fires). Confirmed: `isShownSyncAfterLeave === true` with new JS.
- Overlay only shown for items WITH a `.menu-wrapper--lvl-1` child — Sale % and New do NOT dim the page.

## navigation-multilevel mobile drill-down (feature/header, updated 2026-04-02, FINAL PASS)

- 8 `.navigation-multilevel__mobile-row` total: 4 in drawer (visible, rectY 269–525), 4 from desktop instance (zero-rect, not rendered).
- Drawer rows: Products, Services, Brands, Merchants — each has exactly 2 children: label span + chevron. No duplicate text labels.
- Sale % and New render as plain `<span><a>` — no mobile-row button, no mega-menu, no duplicates.
- `is-hidden-sm-lg` class REMOVED from template entirely (0 elements, 0 CSS rules in current build). Duplicate-label issue fully resolved.
- `.mega-menu` at mobile: all 8 instances `display: none`. PASS.
- `.menu__item--has-children-dropdown .menu__trigger--lvl-0`: all 8 instances `display: none`. PASS.
- Clicking Products row: `data-panel-id="js-navigation-multilevel-panel-1"` activates, 5 subcategory links render. Close icon → `arrow_back`, aria-label → "Go back". PASS.
- `moveNavPanelsToDrawer()` guard confirmed: drawer gets 4 nav panels, desktop instance retains its own 4 in subtree. PASS.
- No console errors or warnings.

## mega-menu molecule (confirmed 2026-04-02, final)

- Rendered tag: `<div class="mega-menu js-navigation-multilevel__wrapper menu-wrapper--lvl-1">` — `is-hidden-sm-lg` class removed from template.
- Default state: `visibility: hidden; opacity: 0; display: flex` — present in DOM but invisible until `is-shown` added to parent LI.
- Hover activates: parent LI gets `is-shown`, mega-menu becomes `visibility: visible; opacity: 1`. No `helper-visibility-visible` class needed — driven by `is-shown` on LI + CSS.
- Two-column layout: `.mega-menu__sidebar` (display:flex, 5 items for Products) + `.mega-menu__panel` (display:block, intro/divider/items/show-all). PASS.
- Sale % and New: no overlay (`body-has-overlay` absent), no mega-menu shown on hover. JS guard correctly skips `toggleOverlay()` when no `.menu-wrapper--lvl-1` child. PASS.
- Mobile: all 8 mega-menu instances `display: none`. PASS.

## navigation.all_prefix translation key (confirmed 2026-04-02)

- The "Show all" footer link in the Services mega-menu renders the raw key `navigation.all_prefix Services` instead of a translated string.
- Appears in both the desktop mega-menu footer and in the mobile drawer navigation.

## Design tokens (confirmed values at 16px root font size)

- `--radius-md`: 8px
- `--radius-sm`: 4px
- `--radius-full`: 999px
- `--scale-4`: 4px, `--scale-8`: 8px, `--scale-16`: 16px, `--scale-24`: 24px
- `--input-border-default`: #cccccc
- `--input-border-focus`: #318272 (teal)
- `--background-subtle`: #f5f5f5
- `--shadows-focus-color`: #acffef, spread: 3px
- Typography tokens:
    - `--body-md-size`: 14px (navigation items, mobile buttons, panel links)
    - `--caption-size`: 12px (mobile section headers)
    - `--body-lg-size`: 16px (view-all links)
    - `--heading-sm-size`: 20px
    - `--label-sm-size`: 12px (mega menu sidebar header)

## navigation-multilevel responsive breakpoint (confirmed 2026-04-03)

- Uses `$xl` breakpoint (1024px) for desktop vs mobile switch
- Desktop: mega-menu dropdowns, horizontal navigation bar
- Mobile: side-drawer drill-down panels with section headers
- SCSS pattern: mobile-first (base styles), then `@include helper-breakpoint-media-min($xl)` for desktop overrides

## navigation-multilevel mega-menu (confirmed 2026-04-03)

- Desktop dropdown dimensions: 992px wide × 450px height
- Sidebar: 260px width, fixed, with scrollable items list
- Sidebar scrolling setup: `overflow-y: auto`, `overflow-x: hidden`, `min-height: 0`, items have `flex-shrink: 0`
- Gap between top-level nav items: 24px (via `--scale-24`)
- Chevron icons in sidebar: 16px
- Active/hover state: blue 3px accent line on left, semibold font, subtle background

## navigation-multilevel mobile drill-down (confirmed 2026-04-03)

- Mobile buttons: body-md (14px), padding `8px 16px`
- Section header at top: caption (12px), tertiary color, padding `4px 16px 6px`
- Nav panel structure: flex column, full height container
    - Header: `flex-shrink: 0` (sticky top)
    - Items list: `flex: 1`, `overflow-y: auto`, `min-height: 0` (scrollable middle)
    - View-all link: `flex-shrink: 0`, `border-top: 1px solid` (sticky bottom)
- Chevron icons: 18px (mobile-row + view-all arrow)
- View-all link: body-lg, semibold, brand color

## Manual verification checklist template (navigation example)

When browser automation unavailable, provide:

```js
// Desktop mega-menu checks
const menu = document.querySelector('.menu--lvl-0');
console.log('display:', getComputedStyle(menu).display); // Expected: flex
console.log('flex-direction:', getComputedStyle(menu).flexDirection); // Expected: row
console.log('gap:', getComputedStyle(menu).gap); // Expected: 24px

const sidebarItems = document.querySelector('.mega-menu__sidebar-items');
console.log('overflow-y:', getComputedStyle(sidebarItems).overflowY); // Expected: auto
console.log('min-height:', getComputedStyle(sidebarItems).minHeight); // Expected: 0px

// Mobile nav panel checks
const mobileRow = document.querySelector('.navigation-multilevel__mobile-row');
console.log('padding:', getComputedStyle(mobileRow).padding); // Expected: 8px 16px
console.log('font-size:', getComputedStyle(mobileRow).fontSize); // Expected: 14px

const navPanelItems = document.querySelector('.navigation-multilevel__nav-panel-items');
console.log('flex:', getComputedStyle(navPanelItems).flex); // Expected: 1 1 0%
console.log('overflow-y:', getComputedStyle(navPanelItems).overflowY); // Expected: auto
```
