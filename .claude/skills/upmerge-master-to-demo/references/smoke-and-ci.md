# Smoke test, demo Cypress, and CI polling (Steps 7, 7b, 10–12)

Detail for `@.claude/skills/upmerge-master-to-demo/SKILL.md`. If the local Spryker stack isn't running, start it (`script -q /dev/null docker/sdk up`) rather than skipping — this workflow runs unattended.

Log outcomes per the SKILL.md convention: `[upmerge 7/12] DONE  smoke: Yves+BO login OK, analytics-gui 200`; `[upmerge 7b/12] DONE  cy:demo <N> specs green`; `[upmerge 8/12] DONE  PR <url>`; `[upmerge 10/12] DONE  poll scheduled +60m`; `[upmerge 11/12]` a line per poll (`running`, `auto-fixed phpcs → pushed`, or `BLOCKED  phpstan failure`); `[upmerge 12/12] DONE  pipeline green`.

## Step 7 — Smoke-test Yves and Backoffice login

Drive the browser with `Skill(login-chrome)`. Scope:

- Yves login at `http://yves.eu.spryker.local/DE/en/login` with `spencor.hopkin@acme.com` / `change123` (canonical B2B user; `sonia@spryker.com` does NOT exist — valid customers are `@acme.com` / `@ottom.de`, list with `mariadb -h database -u spryker -psecret -D eu-docker -e "SELECT email FROM spy_customer WHERE registered IS NOT NULL LIMIT 15;"`).
- `/DE/en/customer/overview` renders without errors.
- Backoffice login at `http://backoffice.eu.spryker.local/security-gui/login` with `admin@spryker.com` / `change123`; dashboard renders.
- `http://backoffice.eu.spryker.local/analytics-gui/analytics` returns 200 / renders (NOT a Whoops 500). This is the demo-only-config canary from Step 6e — a `Could not find config key "AMAZON_QUICKSIGHT:..."` here means a demo-only block was dropped.

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
