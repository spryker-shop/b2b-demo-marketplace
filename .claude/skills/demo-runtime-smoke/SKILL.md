---
name: demo-runtime-smoke
description: >-
  Runtime smoke-test all 10 master-demo AI Commerce features (AI Configuration, Search by Image,
  Quick Add by Image, Back Office Assistant, Smart PIM, Smart CMS, Cost Price & Gross Margin,
  QuickSight Analytics, Audit Logs, AI Workflows) against the LIVE running app — no Cypress. Use
  this whenever the user says "runtime smoke test the demo features", "smoke test master-demo",
  "detect anomalies in the demo shop", "check all 10 AI Commerce features work", "is the demo
  environment healthy", "manually verify the AI Commerce features", or asks to confirm the demo
  shop works end to end after a deploy/merge/config change without running the Cypress suite.
  Drives Back Office, Yves, Merchant Portal, console commands, HTTP, and read-only DB/Redis checks
  via `Skill(spryker-runtime)`. Standalone-runnable on its own, and also the delegated runtime-QA
  sub-step that `upmerge-master-to-demo` calls for its Step 7/7a smoke + FE-anomaly scan.
allowed-tools: Bash, Read, Skill, mcp__claude-in-chrome__*
user-invocable: true
---

# Demo Runtime Smoke — all 10 AI Commerce features, live

Exercises the 10 master-demo-only features against the **running** Spryker app the same way a person would — click through Back Office/Yves/MP, hit real endpoints, read real DB/Redis rows — and reports **PASS / ANOMALY / NOT-COVERED** per feature with evidence. This is a *runtime* check, not a test run: it does not execute `cy:demo` or any Codeception suite. Use `cy:demo` for regression coverage in CI; use this skill to see with your own eyes that the live environment actually works, and to catch the class of breakage automated specs miss — a demo Twig/SCSS override silently shadowed by a redesign, a dropped config block, a stale class-resolver cache.

The 10 features' expected behavior is derived from the authoritative Cypress specs in `tests/cypress-tests/cypress/e2e/demo/*.cy.ts` — this skill translates their key assertions into things you can observe live (a page loads, a control renders, an endpoint returns the right status, a DB row appears) rather than re-running them.

## Prerequisites

If the stack isn't up, start it — don't skip the smoke:

```bash
script -q /dev/null docker/sdk up
```

Drive everything through `Skill(spryker-runtime)` (browser sessions, console commands, HTTP calls, read-only SQL/Redis). Load `Skill(spryker-runtime)` before the first browser or console action.

**Credentials** (demo-only, safe to reuse):

| Actor | Login | Credentials |
|---|---|---|
| Back Office admin | `http://backoffice.eu.spryker.local/security-gui/login` | `admin@spryker.com` / `change123` |
| Yves customer | `http://yves.eu.spryker.local/DE/en/login` | `spencor.hopkin@acme.com` / `change123` (canonical B2B user — `sonia@spryker.com` does **not** exist) |
| Yves agent | `http://yves.eu.spryker.local/en/agent/login` | `agent123@spryker.com` / `change123` |
| Merchant Portal user | `http://mp.eu.spryker.local/security-merchant-portal-gui/login` | `harald@spryker.com` / `change123` ("Spryker" merchant) |

## Two gotchas that apply across ALL 10 features — read before flagging anything

1. **`0×0` / absent / disabled ≠ broken.** Several features gate on a Back Office **Configuration Management** toggle (`ai_commerce:*` settings). A `0×0` widget box or a missing control is the toggle being OFF in a fresh env, not a regression. Only flag it if the standard chrome *around* it breaks, or a toggle you confirmed ON is mislaid. Enable the toggle via `AI Configuration` (see feature 1) before judging a feature ANOMALY for "nothing renders".
2. **Prefer geometry over eyeballing, but probe only VISIBLE, on-row controls.** For any suspected layout break, confirm with `getBoundingClientRect()` via the JS tool rather than trusting a screenshot — screenshots miss `0×0` boxes and misjudge "stacked vs inline". **Critical caveat (this bit a real run):** the naive `form.querySelector('.search-form__submit')` can match a *hidden* popup/mobile submit that sits far below the input, so the probe reports a false `stacked` anomaly while the visible desktop icons are actually inline. Always filter to elements that are rendered (`width > 0` and `display !== 'none'`) and near the input's row before measuring `dy`. Example probe (search-form icons inline vs stacked), filtered:
   ```js
   const input = document.querySelector('.js-search-form__input--desktop') || document.querySelector('input[name="q"]');
   const form  = input.closest('.search-form__form') || input.closest('form');
   const rendered = (el) => el && el.getBoundingClientRect().width > 0 && getComputedStyle(el).display !== 'none';
   // pick the VISIBLE submit/trigger, not the first (possibly hidden popup) match
   const submit = [...form.querySelectorAll('.search-form__submit, button[type=submit]')].find(rendered);
   const dy = submit ? Math.abs(input.getBoundingClientRect().y - submit.getBoundingClientRect().y) : null;
   dy == null ? 'no visible submit found' : dy < 12 ? `inline (ok, dy=${dy})` : `stacked (dy=${dy}) — anomaly`;
   ```
   If the filtered geometry still looks off, cross-check with a screenshot before flagging — on the results page the *visible* desktop file-trigger/clear/submit icons sit inline (dy ≈ 0–6px) even when a hidden popup submit is 40px+ below.

**Reliable login input pattern** (synthetic typing is flaky on both the redesigned Yves login and the Angular/web-component BO login) — set values via the native setter + dispatched events, then submit the form directly, and judge success by the resulting URL:
```js
const set = (sel,val)=>{const el=document.querySelector(sel);
  Object.getOwnPropertyDescriptor(HTMLInputElement.prototype,'value').set.call(el,val);
  el.dispatchEvent(new Event('input',{bubbles:true})); el.dispatchEvent(new Event('change',{bubbles:true}));};
set('input[type=email]','admin@spryker.com'); set('input[type=password]','change123');
document.querySelector('input[type=email]').closest('form').submit();
```

**JS tool return-value gotcha:** the browser JS tool refuses to return any object whose keys contain `password` or `token` (a safety filter). When a probe needs to report on a token/password field, name the returned keys neutrally (e.g. `masked`, `filled`, `fieldType`) rather than `token`/`password` — otherwise the call comes back blocked, not with your data.

A raw glossary label (e.g. `Recurring_orders.Menu_item` instead of a clean phrase) is **expected**, not a regression, when the key exists in the merged glossary CSV but `data:import:glossary` hasn't run yet — see `references/feature-checks.md` if this shows up.

## Run order

1. Log in to Back Office once; log in to Yves as customer once; log in to Yves as agent once (separately — **logging into BO in the same browser clobbers the Yves agent session**, see feature 7 in `references/feature-checks.md`). Reuse each session across the features that need it.
2. Walk the 10 features in the table below, lightest-weight check first (page loads / control renders) before attempting a real AI round-trip. A real provider call is optional (needs an API token in AI Configuration) — if no token is configured, report that feature's provider round-trip as `NOT-COVERED (no provider token)`, not a FAIL. Structural checks (page loads, control renders, endpoint contracts) never need a token and must always run.
3. For any ANOMALY, run the diagnosis + auto-fix flow in "Resolve or escalate" below before reporting.
4. Emit the final report per the "Output contract" below.

## The 10 features — check summary

Full step-by-step recipes, selectors, and gotchas for each row live in `references/feature-checks.md` — read it before driving a feature you haven't checked before. This table is the map.

| # | Feature | Where to drive it live | PASS signal | Anomaly signature |
|---|---|---|---|---|
| 1 | **AI Configuration** | BO `/configuration/manage?feature=ai_vendor&tab=openai` (also `anthropic`, `aws`); `?feature=ai_commerce&tab=backoffice_assistant` | 200; OpenAI/Anthropic/AWS tabs exist; masked (`type=password`) token fields + model-prices JSON editor render; provider radio has 3 options | 500/Whoops; tab missing; token field not masked; radio has <3 options |
| 2 | **Search by Image** | Yves any search bar (after enabling the toggle) | camera/upload icon renders **inline** with the search input (`dy < 12px`); `/search-by-image` POST returns 200 | icons stacked (dy≥12px, the known regression); endpoint 5xx |
| 3 | **Quick Add by Image** | Yves `/DE/en/quick-order` | "Add to cart from image" section renders with file input `accept=image/jpeg,image/jpg,image/png` and enabled Upload button | section absent while toggle is ON; Upload disabled; endpoint 5xx |
| 4 | **Back Office Assistant** | Any BO page (e.g. Dashboard) | launcher renders bottom-right; clicking opens a `role=dialog` panel with greeting, agent picker, input, send | launcher/panel absent while enabled; panel overlaps nav/other widgets |
| 5 | **Smart PIM** | BO `/product-management/edit?id-product-abstract=<id>` | category/alt-text/translate/improve-content triggers render; clicking a trigger fires the matching `/ai-commerce/*` POST | triggers absent; wrong endpoint; 500 instead of a graceful error |
| 6 | **Smart CMS** | BO `/cms-gui/create-glossary?id-cms-page=<id>` (Page) **and** a CMS Block editor | Smart CMS Content Assistant panel renders on **both** surfaces; expanding shows prompt input + Ask AI + attach | panel on Page only (Block missing, or vice versa); toggle-off panel mistaken for broken (see gotcha 1) |
| 7 | **Cost Price & Gross Margin** | BO product Price&Tax tab; Yves **agent** `/DE/en/agent/quote-request/edit/<REF>`; MP Products table | BO: Cost/Gross/Net columns editable; Yves agent-edit: Cost Price + Gross margin molecule per line item; MP: "Cost Default" column present | any surface missing the column/molecule; molecule present on customer `details` view (wrong — it must NOT be there) |
| 8 | **QuickSight Analytics** | BO `/analytics-gui/analytics` | 200 (not Whoops 500); "Analytics" section title renders | 500 `Could not find config key "AMAZON_QUICKSIGHT:..."` (dropped config) or `...DependencyProvider not found` (stale class-resolver cache — not a real regression, see fix below) |
| 9 | **Audit Logs** | BO `/ai-foundation/ai-interaction-log` | 200; 10 column headers render; 5 stats cards render; DataTable AJAX returns `{draw, recordsTotal, recordsFiltered, data}` | table/stats absent; AJAX 500; missing "Estimated cost" column |
| 10 | **AI Workflows** | BO `/ai-foundation/ai-workflow` | 200; "Workflows" heading + "Workflow Items" widget; 6 column headers; DataTable AJAX returns the same shape as #9 | table absent; AJAX 500 |

## Resolve or escalate

Same rule as the upmerge skill's FE scan: a **simple** fix is confined to restoring a demo-owned Yves/BO Twig+SCSS override to its known-working `master-demo` pair — no logic change.

```bash
git checkout master-demo -- <the .twig and .scss that regressed>   # restore BOTH files of the pair
docker/sdk cli console frontend:yves:build && docker/sdk cli console cache:empty-all
# hard-reload with a cache-bust query (?cb=1) and re-verify the geometry probe
```

For the QuickSight `...DependencyProvider not found` symptom specifically, try `docker/sdk cli console cache:class-resolver:build` (not just `cache:empty-all`) before treating it as a regression — it is usually a stale local cache, not a dropped config block.

**Escalate instead of auto-fixing** when the fix needs real template/logic changes, spans core/vendor code, or you can't find a working prior version. Surface the page, a screenshot, and your diagnosis rather than guessing.

## Output contract

Report one line per feature, in table order, then a one-line overall verdict:

```
[demo-smoke] 1  AI Configuration        PASS
[demo-smoke] 2  Search by Image         PASS       (icons inline, dy=3px)
[demo-smoke] 3  Quick Add by Image      NOT-COVERED (toggle OFF, not tested)
[demo-smoke] 4  Back Office Assistant   PASS
[demo-smoke] 5  Smart PIM               ANOMALY    (category trigger missing on product 300 — see diagnosis below)
[demo-smoke] 6  Smart CMS               PASS       (Page + Block panels both render)
[demo-smoke] 7  Cost Price & Margin     PASS       (BO + Yves agent-edit + MP all show the column/molecule)
[demo-smoke] 8  QuickSight Analytics    PASS       (200, ran cache:class-resolver:build once)
[demo-smoke] 9  Audit Logs              PASS
[demo-smoke] 10 AI Workflows            PASS
[demo-smoke] OVERALL  9 PASS / 1 ANOMALY / 0 NOT-COVERED (fixed 0, escalated 1)
```

Each ANOMALY line must carry: the evidence (geometry numbers, HTTP status, screenshot path, or DB row), the diagnosis (which override/config/wiring is implicated), and whether it was auto-fixed or escalated. Each NOT-COVERED line must carry the reason (toggle off, no provider token, MP login silently failing, no seed data within budget) — never silently skip a feature.

## Reused by upmerge-master-to-demo

`upmerge-master-to-demo`'s Step 7/7a (smoke test + FE-anomaly scan) delegates to this skill instead of re-deriving per-feature checks inline: invoke `Skill(demo-runtime-smoke)` there and fold its per-feature report into the upmerge's `[upmerge 7/12]` / `[upmerge 7a/12]` log lines. This skill owns "does each of the 10 features actually work live"; the upmerge skill still separately owns the changed-file-driven visual scan of pages the merge touched (`references/smoke.md` there) and running `cy:demo` (Step 7b) — those stay upmerge-specific because they key off `git diff`, not off the fixed feature list.
