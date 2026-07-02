# Smoke test, demo Cypress, and CI polling (Steps 7, 7a, 7b, 10–12)

Detail for `@.claude/skills/upmerge-master-to-demo/SKILL.md`. If the local Spryker stack isn't running, start it (`script -q /dev/null docker/sdk up`) rather than skipping — this workflow runs unattended.

Log outcomes per the SKILL.md convention: `[upmerge 7/12] DONE  smoke: Yves+BO login OK, analytics-gui 200`; `[upmerge 7a/12] DONE  FE scan: <N> pages, <fixed X / none>`; `[upmerge 7b/12] DONE  cy:demo <N> specs green`; `[upmerge 8/12] DONE  PR <url>`; `[upmerge 10/12] DONE  poll scheduled +60m`; `[upmerge 11/12]` a line per poll (`running`, `auto-fixed phpcs → pushed`, or `BLOCKED  phpstan failure`); `[upmerge 12/12] DONE  pipeline green`.

## Step 7 — Smoke-test Yves and Backoffice login

Drive the browser with `Skill(spryker-runtime)`. Scope:

- Yves login at `http://yves.eu.spryker.local/DE/en/login` with `spencor.hopkin@acme.com` / `change123` (canonical B2B user; `sonia@spryker.com` does NOT exist — valid customers are `@acme.com` / `@ottom.de`, list with `mariadb -h database -u spryker -psecret -D eu-docker -e "SELECT email FROM spy_customer WHERE registered IS NOT NULL LIMIT 15;"`).
- `/DE/en/customer/overview` renders without errors.
- Backoffice login at `http://backoffice.eu.spryker.local/security-gui/login` with `admin@spryker.com` / `change123`; dashboard renders.
- `http://backoffice.eu.spryker.local/analytics-gui/analytics` returns 200 / renders (NOT a Whoops 500). This is the demo-only-config canary from Step 6e — a `Could not find config key "AMAZON_QUICKSIGHT:..."` here means a demo-only block was dropped. A `Class "\Pyz\Zed\AnalyticsGui\...DependencyProvider" not found` (or any `\Pyz\...\...DependencyProvider not found` where the real provider lives in `src/Demo`) is usually a **stale local class-resolver cache**, not a merge drop — run `docker/sdk cli console cache:class-resolver:build` (not just `cache:empty-all`) and re-check before treating it as a regression.

**Reliable input patterns** (synthetic typing is flaky on these forms):
- Yves: click → `cmd+a` → `Delete` → `type`, then screenshot-verify before submit.
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

**1. Build the visit list from what the merge actually changed.** Only Yves front-end files matter here:

```bash
git diff --name-only master-demo...HEAD -- 'src/Pyz/Yves/**/*.twig' 'src/Pyz/Yves/**/*.scss' 'src/Pyz/Yves/**/*.ts' 'src/Demo/Yves/**/*.twig' 'src/Demo/Yves/**/*.scss' \
  | sed -E 's#.*/Yves/([^/]+)/.*#\1#' | sort -u
```

Map the changed modules to a page that exercises each, and visit them logged-in as `spencor.hopkin@acme.com`. Always include, when their module shows up: the **header search bar** (`ShopUi` — search-form/suggest-search/header), a **catalog/PLP page** (`CatalogPage` — e.g. `/DE/en/thermal-energy-systems`; note bare `/catalog` is a 404 by design, use a category URL), a **PDP** (`ProductDetailPage`), the **cart** (`CartPage`), and any demo widget page (`SelfServicePortal`, `QuoteRequestPage`, etc.). If a build hasn't run since the SCSS/Twig changed, run `docker/sdk cli console frontend:yves:build` first or the page shows stale CSS.

**2. Look for the anomaly signature.** On each page, screenshot and scan for: controls stacked vertically that should be inline, a missing border/background box around an input group, icons dropped below or overlapping their field, elements at x/y `0,0` or with a `0x0` box that should be visible, raw glossary keys (that's the Step 7 glossary case — informational, not this). Confirm a suspected layout break with geometry rather than eyeballing — for the elements involved, read `getBoundingClientRect()` and check they share a row / sit inside their container:

```js
// example: are the search-form icons inline with the input, or stacked below it?
const input = document.querySelector('.js-search-form__input--desktop');
const submit = input.closest('.search-form__form').querySelector('.search-form__submit');
const dy = Math.abs(input.getBoundingClientRect().y - submit.getBoundingClientRect().y);
dy < 12 ? 'inline (ok)' : `stacked (dy=${dy}) — anomaly`;
```

**3. Diagnose: which override did master's redesign shadow?** For the component that looks wrong, check whether the merge took master's side of its Twig/SCSS:

```bash
F=src/Pyz/Yves/<Module>/Theme/default/components/<...>/<name>.scss   # and the .twig sibling
git diff master-demo...HEAD -- "$F"                                   # what the merge changed
diff <(git show master-demo:"$F") "$F"                                # HEAD vs the demo's working version
```

Tell-tale of an incompatible redesign landing on the demo: master's version references classes/elements the demo theme doesn't define (grep the class across `src/Pyz`/`src/Demo` theme SCSS — if it's only in the Twig that emits it, it's dead on the demo), or drops the layout a demo-only element (e.g. a search-by-image widget, a merchant badge) depends on.

**4. Resolve the simple ones autonomously; escalate the rest.** A **simple** fix is one confined to restoring a demo-owned Yves override to its known-working master-demo pair — no logic, just markup/style. Do it and move on:

```bash
git checkout master-demo -- <the .twig and .scss that regressed>   # restore the working pair (BOTH files)
docker/sdk cli console frontend:yves:build && docker/sdk cli console cache:empty-all
# hard-reload with a cache-bust query (?cb=1) and re-verify the geometry from step 2
```

Because these are demo-owned FE files a demo-only feature depends on, prefer the **master-demo** side even though the file lives in `src/Pyz` (the Step 4 core-tracking default would keep master). Commit separately (`fix(<component>): restore demo <layout> after upmerge`), push, and record it in the PR body under Reconciliation notes with the root cause. **Escalate (leave the task `in_progress`, `BLOCKED` line, stop)** if the fix would need real template/logic changes, spans core/vendor, or you can't identify a working prior version — surface the page, the screenshot, and the diagnosis for the user.

Log: `[upmerge 7a/12] DONE  FE scan: <N> pages checked, <fixed X / none>` — e.g. `restored search-form.{twig,scss} (icons were stacked)` or `no anomalies`.

## Step 7b — Run the demo Cypress group (`cy:demo`)

`cypress/e2e/demo/` is the automated coverage for demo-only features (QuickSight and the other AI Commerce features) — exactly what an upmerge breaks via dropped demo config/wiring. Mandatory on every upmerge; it supersedes the manual analytics-gui canary.

```bash
cd tests/cypress-tests
ENV_REPOSITORY_ID=b2b-mp ENV_IS_SSP_ENABLED=true npm run cy:demo
```

All specs must pass before pushing. A failure is a real regression — cross-reference Step 6e/6g (dropped demo block or wiring). If `cy:demo` doesn't exist on the branch, that itself is a finding: the demo group / CI step may have been lost in the merge — restore it (see the `cypress-e2e-test` skill's demo-group section) before pushing. The same `cy:demo` runs in CI as its own `Run Tests (Demo)` step, so a green local run predicts the green CI step.

## Step 10 — Schedule the CI poll

After opening the PR, tell the user the PR is open and you'll check the pipeline in ~2h (mention the ticket only if one applies — and that its status was not moved). Then schedule:

```
ScheduleWakeup with
  delaySeconds=3600            # runtime caps at 3600s; the poll reschedules itself if still running
  reason="Initial pipeline check for upmerge PR <PR-NUMBER>"
  prompt="/upmerge-master-to-demo poll PR <PR-NUMBER>"     # append " ticket <TICKET>" only if a ticket applies
```

## Step 11 — Poll and auto-fix

```bash
gh pr checks <PR-NUMBER> --json name,status,conclusion,detailsUrl
```

- **Still running:** reschedule at 1200s with the same prompt.
- **All green:** go to Step 12.
- **Failed:** get logs (`gh run view <RUN-ID> --log-failed`) and auto-fix the deterministic classes, then commit + push + reschedule a 1200s re-check:

  | Failure | Auto-fix |
  |---|---|
  | `phpcs` / code style | `vendor/bin/phpcbf` on flagged files |
  | `transfer:generate` | `docker/sdk cli console transfer:generate`, commit generated files |
  | `propel:install` schema diff | `docker/sdk cli console propel:install` |
  | `composer.lock` out of sync | `composer update --lock --ignore-platform-reqs` |

  A red `Run Tests (Demo)` step maps back to Step 6e/6g and Step 7b. For phpstan errors, broken tests, or unexpected runtime failures, stop and surface the failure with the relevant log excerpt — don't rewrite code logic in CI unattended.

## Step 12 — Final report

When green:
- Tell the user: `Upmerge PR <PR-URL> is green and ready for review.`
- If a ticket was provided (and the user hasn't asked to stay off JIRA), optionally add a best-effort PR-link comment. Never move the ticket's status.
- Don't merge the PR — that's the user's call.
