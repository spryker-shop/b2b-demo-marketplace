# Smoke test, FE-anomaly scan, and demo Cypress (Steps 7, 7a, 7b)

Detail for `@.claude/skills/upmerge-master-to-demo/SKILL.md`. If the local Spryker stack isn't running, start it (`script -q /dev/null docker/sdk up`) rather than skipping — this workflow runs unattended. CI polling (Steps 10–12) lives in `@.claude/skills/upmerge-master-to-demo/references/ci.md`.

Log outcomes per the SKILL.md convention: `[upmerge 7/12] DONE  smoke: Yves+BO login OK, analytics-gui 200`; `[upmerge 7a/12] DONE  FE scan: <N> pages, <fixed X / none>`; `[upmerge 7b/12] DONE  cy:demo <N> specs green`.

## Step 7 — Smoke-test Yves and Backoffice login

Drive the browser with `Skill(spryker-runtime)`. Scope:

- Yves login at `http://yves.eu.spryker.local/DE/en/login` with `spencor.hopkin@acme.com` / `change123` (canonical B2B user; `sonia@spryker.com` does NOT exist — valid customers are `@acme.com` / `@ottom.de`, list with `mariadb -h database -u spryker -psecret -D eu-docker -e "SELECT email FROM spy_customer WHERE registered IS NOT NULL LIMIT 15;"`).
- `/DE/en/customer/overview` renders without errors.
- Backoffice login at `http://backoffice.eu.spryker.local/security-gui/login` with `admin@spryker.com` / `change123`; dashboard renders.
- `http://backoffice.eu.spryker.local/analytics-gui/analytics` returns 200 / renders (NOT a Whoops 500). This is the demo-only-config canary from Step 6e — a `Could not find config key "AMAZON_QUICKSIGHT:..."` here means a demo-only block was dropped. A `Class "\Pyz\Zed\AnalyticsGui\...DependencyProvider" not found` (or any `\Pyz\...\...DependencyProvider not found` where the real provider lives in `src/Demo`) is usually a **stale local class-resolver cache**, not a merge drop — run `docker/sdk cli console cache:class-resolver:build` (not just `cache:empty-all`) and re-check before treating it as a regression.

**Reliable input patterns** (synthetic typing is flaky on these forms):
- The **redesigned Yves login** (master) is a standalone minimal card (no header/search) — set values via the native setter + dispatched events, then submit the form directly (same pattern as the BO form below), and judge success by the resulting URL, not the pre-submit screenshot. A plain `click → cmd+a → Delete → type` also works but is flakier.
- Backoffice (Angular/ZED web-component form): set values via the native setter + dispatched events, then submit the form directly:
  ```js
  const set = (sel,val)=>{const el=document.querySelector(sel);
    Object.getOwnPropertyDescriptor(HTMLInputElement.prototype,'value').set.call(el,val);
    el.dispatchEvent(new Event('input',{bubbles:true})); el.dispatchEvent(new Event('change',{bubbles:true}));};
  set('input[type=email]','admin@spryker.com'); set('input[type=password]','change123');
  document.querySelector('input[type=email]').closest('form').submit();
  ```
  Judge success by the resulting URL/page, not the pre-submit screenshot.

A raw glossary label (e.g. sidebar shows `Recurring_orders.Menu_item`) is **expected** on a fresh local env when the key exists in the merged glossary CSV (`grep -rn "<key>" data/import/*/common/glossary.csv`) but its `data:import` hasn't run — informational, not a regression. Run `docker/sdk cli console data:import:glossary` if a clean label is wanted.

A login or page that actually errors is a real regression — capture console errors and treat it as a hard stop.

## Step 7a — Visual FE-anomaly scan on the pages the merge touched (auto-fix the simple ones)

`git merge` and the Step 6b pre-filter both miss a whole class of breakage: **a demo/Pyz Twig or SCSS override shadowed by a master redesign of the same component**. No conflict marker fires, `cy:demo` may still pass (it asserts behavior, not layout), and static analysis is clean — but the page renders visibly wrong (icons stacked instead of inline, a search bar with no border box, overlapping controls, a broken grid). This is the same trap as Step 6b, caught here by *looking* instead of diffing. **A green login + green `cy:demo` is not enough — visually QA the redesigned pages every run.**

### Fast FE-anomaly flow (the smoke lane)

A **fast** pass — happy-path, layout-only, no login-per-page — that hits the highest-risk customized surfaces and confirms each with geometry, not eyeballing. Log in once as `spencor.hopkin@acme.com`, then visit the pages below in one session. **Run it every upmerge** as the quick baseline; expand to the full changed-file list (below) only when a page looks off or the diff touched a module not in this table.

The customized FE surface (from `git diff master...master-demo -- 'src/{Pyz,Demo}/Yves/**/*.{twig,scss,ts}'`) maps to these pages:

| # | Surface (customized module) | Page to visit | Anomaly signature to check |
|---|---|---|---|
| 1 | **Search form + search-by-image** (ShopUi `search-form`, AiCommerce `search-by-image`) | any page header | search input, submit icon, camera/search-by-image icon **inline on one row** (input↔submit `dy < 12px`); not stacked below |
| 2 | **PLP product tiles** (ShopUi `product-item`) | `/DE/en/thermal-energy-systems` (bare `/catalog` is 404 by design) | tiles form a grid row (same `y`), each tile sized (not `0×0`) |
| 3 | **PDP + widgets** (ProductGrossMarginWidget, PriceProductVolumeWidget) | a product, e.g. `/DE/en/verticalsteamboiler2.5t/h` | image left / buy-box right; price + quantity stepper + Add-to-Cart aligned |
| 4 | **Quick Order form + image upload** (QuickOrderPage `quick-order-form`) | `/DE/en/quick-order` | SKU rows table columns aligned; "Add articles" sidebar **side-by-side** with the table (sidebar `x` > table `x`), upload dropzone present |
| 5 | **Header / side-drawer / icon-sprite** (ShopUi) | all pages | logo/search/cart/account/nav inline, no overlap; sprite icons render |
| 6 | **Cart item** (CartPage `cart-item-details`, `product-cart-item`) | `/DE/en/cart` **with an item** | cart-row columns (image / name / qty / price) aligned in one row |
| 7 | **Quote-request cost-price** (QuoteRequestPage `cost-price`, `quote-request-cart-item`) | an **existing** quote request in **agent context** (see recipe) | quote-request line items render aligned; cost-price molecule position (molecule itself is a known separate defect — see gotchas) |

**Geometry probe** — for any suspected break, confirm with `getBoundingClientRect()` rather than the screenshot alone:

```js
// search-form: are the icons inline with the input, or stacked below it?
const input = document.querySelector('.js-search-form__input--desktop') || document.querySelector('input[name="q"]');
const form  = input.closest('.search-form__form') || input.closest('form');
const submit= form.querySelector('.search-form__submit') || form.querySelector('button[type=submit]');
const dy = Math.abs(input.getBoundingClientRect().y - submit.getBoundingClientRect().y);
dy < 12 ? 'inline (ok)' : `stacked (dy=${dy}) — anomaly`;
```

**Two gotchas that look like anomalies but are NOT** (learned running this live on a clean master-demo):
- **A `0×0` demo-widget box is expected when its feature toggle is OFF.** The search-by-image molecule is `display:flex` but `0×0` because its enable flag is off in a fresh env (see the toggle list in `demo-reconciliation.md` / the AI-Commerce smoke plan) — the *surrounding* search box still renders fine. Only treat a widget as broken if the **standard chrome around it** breaks, or a **toggle-ON** widget is mislaid. Don't restore an override over a toggle-off `0×0`.
- **Cart (#6) needs a seeded item** — a clean session's cart is empty and the demo catalog's headline products are **configurable** (need a variant attribute + a concrete variant SKU — the abstract SKU e.g. `VSB-2500` is "not found" in quick-order). To exercise the cart-item layout, seed a simple non-configurable product first (`Skill(spryker-data-seeder)`) or add via a resolvable variant SKU. If you can't seed within the smoke budget, record #6 as **not covered this run** — honest, not a pass.
- **Quote-request cost-price (#7) needs NO seeding — open an existing quote request on the AGENT EDIT route.** This is the crucial part: the cost-price molecule renders **only on the agent edit view**, NOT on the customer read-only `details` view. Recipe (verified live):
  1. Agent-login at `http://yves.eu.spryker.local/en/agent/login` with `agent123@spryker.com` / `change123` (the `spy_user.status` shows `0` in the mariadb client but that is *active* — admin shows the same; `is_agent=1`). No customer impersonation is needed for the agent edit route.
  2. List existing quote requests: `mariadb -h database -u spryker -psecret -D eu-docker -e "SELECT quote_request_reference,status FROM spy_quote_request ORDER BY id_quote_request DESC LIMIT 10;"`.
  3. Open one at the **agent edit** route `/DE/en/agent/quote-request/edit/<REF>` (e.g. `/DE/en/agent/quote-request/edit/DE--21-6`). Each line item shows a **Cost Price** row + **Gross margin** badge + "Use default price" checkbox, aligned in the item box — that is the #7 PASS.
  - Route gotchas: the customer view `/DE/en/quote-request/details/<REF>` renders **0 cost-price nodes** (molecule not on that view — don't test cost-price there); `?quoteRequestReference=` and `?_switch_user=` both error/404 (use the path-segment agent-edit route). Logging into the **Back Office in the same browser clobbers the Yves agent session** — do the agent check before / separately from the BO scan, and re-login if `Agent Mode` is gone.
- **Empty "Cost Price: — / ⚠ (warning)" is a data state, not a break.** On a quote-request version whose cost price hasn't been applied yet, every item shows `Cost Price: —` with an amber warning and `Gross margin: —` — the molecule still renders and is laid out correctly. **Edit → Save** recalculates and applies real values (verified: `DE--21-6` after save shows `€337.88 / 30%`; an un-saved `DE--21-4` shows `— / ⚠`). So for #7 the Step 7a check is purely *does the molecule render & lay out* (it does on both) — the `—` vs `€value` is expected cost-price application state, not a Step 7a anomaly.

Known-good baseline (verified live on `master-demo`): the historical **search-form icon-stacking regression is currently absent** — the demo override is intact and icons render inline. If a run shows them stacked, that's the regression returning → diagnose per below.

### Full changed-file scan (when the fast pass flags something, or the diff is large)

Build the visit list from what the merge actually changed:

```bash
git diff --name-only master-demo...HEAD -- 'src/Pyz/Yves/**/*.twig' 'src/Pyz/Yves/**/*.scss' 'src/Pyz/Yves/**/*.ts' 'src/Demo/Yves/**/*.twig' 'src/Demo/Yves/**/*.scss' \
  | sed -E 's#.*/Yves/([^/]+)/.*#\1#' | sort -u
```

Map each changed module to a page that exercises it and visit it logged-in. If a build hasn't run since the SCSS/Twig changed, run `docker/sdk cli console frontend:yves:build` first or the page shows stale CSS.

### Back Office (Zed) FE surface — scan it too

The demo customizes **Back Office** Twig as well as Yves — a master redesign of a Zed presentation template can shadow a Demo override exactly like on the storefront. The BO surface (from `git diff master...master-demo -- 'src/{Pyz,Demo}/Zed/**/*.twig'` — no Zed JS/SCSS is customized, so Twig is the whole surface) maps to these BO pages. Log in once at `http://backoffice.eu.spryker.local/security-gui/login` as `admin@spryker.com` / `change123`, then visit:

| Surface (customized module) | BO page | Check |
|---|---|---|
| **Product cost-price / gross-margin** (`ProductManagement` `info-price-tax`/`info-price-stock`/`product_price_collection`, `PriceProductOfferGui`, `ProductCreationWizardGui`) | `product-management/edit?id-product-abstract=<id>` → **Price & Tax** tab | price matrix shows the **Cost price** column beside Gross/Net, per-currency rows + "Add Volume Price" controls, table aligned (verified live: renders correctly) |
| **Smart CMS editors** (`CmsBlockGui` create/edit block, edit-glossary, general/personalization tabs; `CmsGui` create-glossary) | `cms-block-gui/*` (create/edit a CMS block) and the CMS glossary editor | the Smart CMS content-assistant panel/tabs render in place, no overlapping form controls (Smart CMS has a BO config toggle — enable it via Configuration Management if the panel is absent) |
| **Zed layout + Back Office Assistant** (`Gui/.../Layout/layout.twig`) | any BO page (e.g. Dashboard) | the global **Assistant** widget renders bottom-right, left nav + top bar not overlapping (verified live: renders correctly) |

Same rules as the Yves scan: a `0×0`/absent demo widget is usually a **feature toggle OFF**, not a break; confirm a suspected layout break with `getBoundingClientRect()` geometry, not eyeballing. Note the harness may block the JS tool on BO pages whose URL/cookies it flags — fall back to screenshots + visual check there.

### Merchant Portal — cost-price product column

The demo **does** customize Merchant Portal for cost price, but the customization is **PHP table-config plugins, not Twig/SCSS** — so a Twig/SCSS diff misses it. Find it with:

```bash
git diff --name-only master...master-demo -- 'src/**' | grep -iE 'ProductMerchantPortalGui|CostPrice'
```

The demo `Demo/Zed/ProductMerchantPortalGui` adds a **Cost Price column** to the MP product table via `PriceProductTableColumnCreator`, `CostPriceTableConfigurationExpander`, `CostPriceProductMapper`, and the abstract/concrete `CostPrice*TableConfigurationExpanderPlugin`s (wired in `ProductMerchantPortalGuiDependencyProvider`).

| Surface | MP page | Check |
|---|---|---|
| **MP product Cost Price column** (`ProductMerchantPortalGui` cost-price plugins) | MP → **Products** table (abstract + concrete/variants) | the **Cost Price** column renders in the product table with values, aligned with the other price columns |

**Verify it** at the MP host from `deploy.dev.yml` — `http://mp.eu.spryker.local/security-merchant-portal-gui/login`, `harald@spryker.com` / `change123` (Merchant user, "Spryker" merchant) → open **Products** and confirm the Cost Price column.

> **Env gotchas (both seen live):** (1) `curl http://mp.eu.spryker.local` from a shell returns `000` — that's a shell-only DNS quirk; the **container `mportal_eu_1` is up** and the login page **renders fine in the browser** (`docker ps | grep mportal` to confirm the container). Judge MP reachability from the browser, not curl. (2) **MP login fails silently** — `harald@spryker.com` (password verifies in DB) submits and the Angular form just **resets to empty**, staying on the login URL with no error, so the Products table can't be reached. This is a standing env blocker. When it recurs, **exclude the MP cost-price surface from the run** (state "MP login silently fails — excluded") rather than marking it a FAIL/BLOCKED-forever; it stays a real surface to check whenever MP login works.

### Diagnose: which override did master's redesign shadow?

For the component that looks wrong, check whether the merge took master's side of its Twig/SCSS:

```bash
F=src/Pyz/Yves/<Module>/Theme/default/components/<...>/<name>.scss   # and the .twig sibling
git diff master-demo...HEAD -- "$F"                                   # what the merge changed
diff <(git show master-demo:"$F") "$F"                                # HEAD vs the demo's working version
```

Tell-tale of an incompatible redesign landing on the demo: master's version references classes/elements the demo theme doesn't define (grep the class across `src/Pyz`/`src/Demo` theme SCSS — if it's only in the Twig that emits it, it's dead on the demo), or drops the layout a demo-only element (a search-by-image widget, a merchant badge, cost-price molecule) depends on.

### Resolve the simple ones autonomously; escalate the rest

A **simple** fix is one confined to restoring a demo-owned Yves override to its known-working master-demo pair — no logic, just markup/style:

```bash
git checkout master-demo -- <the .twig and .scss that regressed>   # restore the working pair (BOTH files)
docker/sdk cli console frontend:yves:build && docker/sdk cli console cache:empty-all
# hard-reload with a cache-bust query (?cb=1) and re-verify the geometry
```

Because these are demo-owned FE files a demo-only feature depends on, prefer the **master-demo** side even though the file lives in `src/Pyz` (the Step 4 core-tracking default would keep master). Commit separately (`fix(<component>): restore demo <layout> after upmerge`), push, and record it in the PR body under Reconciliation notes with the root cause. **Escalate (leave the task `in_progress`, `BLOCKED` line, stop)** if the fix would need real template/logic changes, spans core/vendor, or you can't identify a working prior version — surface the page, the screenshot, and the diagnosis for the user.

Log: `[upmerge 7a/12] DONE  FE scan: <N> Yves + <M> BO pages checked, <fixed X / none>` — e.g. `restored search-form.{twig,scss} (icons were stacked)` or `no anomalies (Yves: search/PLP/PDP/quick-order/quote-request; BO: price-tax/CMS/layout; cart not-covered — no seeded item)`.

## Step 7b — Run the demo Cypress group (`cy:demo`)

`cypress/e2e/demo/` is the automated coverage for demo-only features (QuickSight and the other AI Commerce features) — exactly what an upmerge breaks via dropped demo config/wiring. Mandatory on every upmerge; it supersedes the manual analytics-gui canary.

```bash
cd tests/cypress-tests
ENV_REPOSITORY_ID=b2b-mp ENV_IS_SSP_ENABLED=true npm run cy:demo
```

All specs must pass before pushing. A failure is a real regression — cross-reference Step 6e/6g (dropped demo block or wiring). If `cy:demo` doesn't exist on the branch, that itself is a finding: the demo group / CI step may have been lost in the merge — restore it (see the `cypress-e2e-test` skill's demo-group section) before pushing. The same `cy:demo` runs in CI as its own `Run Tests (Demo)` step, so a green local run predicts the green CI step.

> `cy:demo` asserts **behavior**, not layout — it will pass on a visually-broken page. It does **not** replace the Step 7a visual scan; run both.
