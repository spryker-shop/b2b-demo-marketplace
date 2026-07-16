# Smoke test, FE-anomaly scan, and demo Cypress (Steps 7, 7a, 7b)

Detail for `@.claude/skills/upmerge-master-to-demo/SKILL.md`. If the local Spryker stack isn't running, start it (`script -q /dev/null docker/sdk up`) rather than skipping — this workflow runs unattended. CI polling (Steps 10–12) lives in `@.claude/skills/upmerge-master-to-demo/references/ci.md`.

Log outcomes per the SKILL.md convention: `[upmerge 7/12] DONE  demo-runtime-smoke: <N> PASS / <M> ANOMALY / <K> NOT-COVERED`; `[upmerge 7a/12] DONE  FE scan: <N> changed-file pages, <fixed X / none>`; `[upmerge 7b/12] DONE  cy:demo <N> specs green, cy:demo:full <status>`.

## Step 7 / 7a — Runtime feature smoke (delegated) + changed-file visual scan (owned here)

Two checks share this step, split by ownership:

- **Runtime health of the 10 fixed AI Commerce features** — delegated entirely to `Skill(demo-runtime-smoke)`. That skill owns the login flows, credentials, the toggle-off-vs-broken and geometry-over-eyeballing gotchas, and the PASS/ANOMALY/NOT-COVERED recipe for each of the 10 features (including the analytics-gui/QuickSight canary and the quote-request cost-price agent-edit route). Don't re-derive any of that here — invoke the skill and fold its report into the log lines below.
- **Visual scan of the pages THIS merge's Twig/SCSS actually touched** — stays owned by upmerge, because it keys off `git diff`, not off a fixed feature list. `demo-runtime-smoke` always checks the same 10 fixed surfaces regardless of what changed; a merge can also touch a Twig/SCSS file outside those 10 (any ShopUi/Pyz component the demo overrides), and that needs a diff-driven visit, not a fixed table. This is what Step 7a below still does.

### Step 7 — Invoke `demo-runtime-smoke`

```
Skill(demo-runtime-smoke)
```

Run it as-is — it already covers Yves/BO/agent/MP login, the analytics-gui canary, and all 10 features, no upmerge-specific args needed. Take its per-feature PASS/ANOMALY/NOT-COVERED table and:
- Fold the overall verdict into `[upmerge 7/12] DONE  demo-runtime-smoke: <N> PASS / <M> ANOMALY / <K> NOT-COVERED`.
- For any ANOMALY it already auto-fixed (same restore-the-override recipe as Step 7a below), record the fix in the PR body under Reconciliation notes.
- For any ANOMALY it escalated, that's this step's hard stop too — leave the task `in_progress`, log `BLOCKED`, surface the page/screenshot/diagnosis, stop.
- A `NOT-COVERED` line (toggle off, no provider token, MP login silently failing, no seed data) is informational, not a blocker — carry its reason into the PR body.

If the stack isn't up, `demo-runtime-smoke` starts it itself — no separate boot step needed here.

<!-- The former Step 7 (manual Yves+BO login walkthrough, glossary/analytics-gui canary, reliable-input JS snippets) now lives inside Skill(demo-runtime-smoke) — see its "Prerequisites", "Two gotchas", and feature #8 (QuickSight) rows instead of duplicating them here. -->

## Step 7a — Visual FE-anomaly scan on the pages THIS merge touched (auto-fix the simple ones)

`git merge` and the Step 6b pre-filter both miss a whole class of breakage: **a demo/Pyz Twig or SCSS override shadowed by a master redesign of the same component**. No conflict marker fires, `cy:demo` may still pass (it asserts behavior, not layout), and static analysis is clean — but the page renders visibly wrong (icons stacked instead of inline, a search bar with no border box, overlapping controls, a broken grid). This is the same trap as Step 6b, caught here by *looking* instead of diffing. **A green login + green `cy:demo` is not enough — visually QA the redesigned pages every run.**

This sub-step is scoped to whatever `git diff` shows the merge changed — a different lens than `demo-runtime-smoke`'s fixed 10-feature table above, so run both; they catch different risk. For the shared gotchas (toggle-OFF `0×0` widgets are not anomalies, geometry-probe over eyeballing, reliable login input patterns, the quote-request-agent-edit-route recipe), see `Skill(demo-runtime-smoke)`'s "Two gotchas" section and its `references/feature-checks.md` — not duplicated below. The cart-item seeding case (row #6) has no counterpart in that skill and stays documented here.

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

**Gotchas that look like anomalies but are NOT** — toggle-OFF `0×0` widgets (see `demo-runtime-smoke`'s "Two gotchas" section, and feature 2 in its `references/feature-checks.md`) and the quote-request cost-price agent-edit-route recipe + its "empty Cost Price is a data state, not a break" case (feature 7c in that same file) are covered there in detail — read those before flagging row #1 or #7 above as broken; not re-explained here. **Cart-item seeding (#6) is unique to this Yves scan** (`demo-runtime-smoke` has no cart feature) and stays documented below.

- **Cart (#6) needs a seeded item** — a clean session's cart is empty and the demo catalog's headline products are **configurable** (need a variant attribute + a concrete variant SKU — the abstract SKU e.g. `VSB-2500` is "not found" in quick-order). To exercise the cart-item layout, seed a simple non-configurable product first (`Skill(spryker-data-seeder)`) or add via a resolvable variant SKU. If you can't seed within the smoke budget, record #6 as **not covered this run** — honest, not a pass.

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

## Step 7b — Run the demo Cypress groups (`cy:demo` + `cy:demo:full`)

`cypress/e2e/demo/` is the automated coverage for demo-only features (QuickSight and the other AI Commerce features) — exactly what an upmerge breaks via dropped demo config/wiring. Two tiers, both run on every upmerge:

```bash
cd tests/cypress-tests
ENV_IS_SSP_ENABLED=true npm run cy:demo         # smoke tier: @demo-smoke only (script sets ENV_REPOSITORY_ID=b2b-mp itself)
ENV_IS_SSP_ENABLED=true npm run cy:demo:full    # full tier: @demo-smoke ∪ @demo-full, real AI provider (script sets ENV_REPOSITORY_ID=b2b-mp + DEMO_AI_PROVIDER_ENABLED=1 itself)
```

- **`cy:demo` (smoke, `@demo-smoke`)** — mocked/no-provider assertions, needs no API tokens. **Mandatory gate.** All specs must pass before pushing; supersedes the manual analytics-gui canary. A failure is a real regression — cross-reference Step 6e/6g (dropped demo block or wiring). If `cy:demo` doesn't exist on the branch, that itself is a finding: the demo group / CI step may have been lost in the merge — restore it (see the `cypress-e2e-test` skill's demo-group section) before pushing. The same `cy:demo` runs in CI as its own `Run Tests (Demo)` step, so a green local run predicts the green CI step.
- **`cy:demo:full` (`@demo-smoke ∪ @demo-full`, real provider)** — runs the same smoke cases plus the `@demo-full` cases that hit **real** AI providers (OpenAI + AWS Bedrock), and needs the provider API tokens configured in Back Office AI Configuration (feature 1 in `demo-runtime-smoke`'s feature table). **Run it when tokens are present; the `@demo-full` cases self-skip gracefully when they aren't**, so an unattended upmerge with no tokens configured is not a hard failure on this tier — only `cy:demo` is the mandatory gate. Real-provider runs can be slower and occasionally need a rerun (rate limits, provider latency) — that's expected, not a regression signal by itself; only treat a `@demo-full` failure as real after a rerun still fails.

A failure in either tier is cross-referenced against Step 6e/6g (dropped demo block or wiring) before treating it as new breakage.

> Both tiers assert **behavior**, not layout — they pass on a visually-broken page. Neither replaces the Step 7a visual scan; run all three (`cy:demo`, `cy:demo:full`, and the Step 7a visual scan).
