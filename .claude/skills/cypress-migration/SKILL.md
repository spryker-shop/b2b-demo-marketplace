---
name: cypress-migration
description: >
  Use this skill whenever the user wants to onboard a Spryker project onto a project-owned
  Cypress E2E baseline in place of Spryker's internal demo-shop test suites. Trigger on
  phrases like "migrate off spryker/cypress-tests", "remove the demo-shop cypress/robot
  suites", "set up project-owned cypress testing", "bootstrap cypress-boilerplate for this
  project", "onboard this project onto cypress", "integrate cypress-boilerplate", or any
  request to replace `spryker/cypress-tests` / `spryker/robotframework-suite-tests` with a
  project's own Cypress setup. This is a one-time setup/migration skill — it removes the old
  suites, vendors in the already-adapted `tests/cypress-boilerplate/` implementation from the
  spryker-shop/b2b-demo-marketplace repository (the proven reference implementation — not the
  raw spryker-projects/cypress-boilerplate template), wires up CI, and generates the companion
  day-to-day `cypress-tests` skill for the target project. It is written to be project-agnostic
  for the *target* project: every step discovers the target's actual conventions (hostnames,
  fixtures, CI patterns) before acting rather than assuming they match the reference project.
---

# Cypress Migration: Demo-Shop Suites → Project-Owned Baseline

Every step below discovers facts about the **target project** before acting. Do not assume
file names, job names, hostnames, or conventions from any other project — grep and verify in
the repo you're actually working in. Several steps below exist specifically because assuming
instead of verifying caused real mistakes the first time this migration was done; don't skip
the verification sub-steps even though they look like extra work.

## Step 1 — Confirm scope

```bash
grep -n "spryker/cypress-tests\|spryker/robotframework-suite-tests" composer.json
```

If neither package is present, this project doesn't need the removal steps (Steps 2–5) — skip
ahead to Step 6 (detect the locator convention) and Step 7 (vendoring) if the goal is just
adding a Cypress baseline to a project that never had these suites.

If the project uses a different combination (only one of the two packages, or additional
similar internal test packages), adapt the steps below accordingly rather than assuming both
are always present together.

## Step 2 — Remove Composer entries

In `composer.json`, remove:
- The `require-dev` entries for the packages found in Step 1.
- Any `repositories` entries whose `url` points at these packages' git repos — but only if
  no other required package uses the same repository entry.
- `extra.installer-types` / `extra.installer-paths` entries **only if** they exist solely to
  support these packages. Verify first:
  ```bash
  grep -n "installer-types\|installer-paths\|spryker-test" composer.json
  ```
  Read the surrounding block — if `installer-paths` maps other packages too, only remove the
  entries specific to the removed packages, not the whole block.

Then sync the lock file and the installed tree. Note that `composer update --lock` only
rewrites `composer.lock` — it does **not** remove anything already installed under `vendor/`,
so it alone won't purge the old packages:
```bash
composer update --lock   # sync composer.lock with the edited composer.json
composer install         # actually remove the packages from vendor/
```
Alternatively, skip the manual `require-dev` edit above and let Composer do all three (edit
`composer.json`, update the lock, prune `vendor/`) in one step:
```bash
composer remove --dev spryker/cypress-tests spryker/robotframework-suite-tests
```
If `composer`/PHP isn't available in your environment, tell the user this step still needs to
be run and why (the lock file must reflect the require-dev removal).

Check for a leftover **untracked** install directory from a custom installer path (e.g. a
`tests/cypress-tests/`-style directory that was checked out by Composer via
`installer-paths`, not committed to git):
```bash
git status --porcelain <path-from-installer-paths>
```
If it shows `??` (untracked), it's a stale local build artifact — safe to delete. If it's
tracked, investigate before touching it; it may be legitimate project content.

## Step 3 — Remove CI workflow jobs referencing the old suites

```bash
grep -rn "robot\|cypress" .github/workflows/*.yml
```

For every job block that references the removed packages (by name, by docker-compose file,
by deploy-yml file), identify its exact line range: the job starts at its `<job-key>:` line
(same indentation as other job keys under `jobs:`) and ends at the line before the next
sibling job key (or end of file). Delete each full block, then:

```bash
grep -n "robot\|cypress" .github/workflows/*.yml   # no *active* job references may remain
python3 -c "import yaml; yaml.safe_load(open('<file>')); print('OK')"   # each edited file
```

Judge that grep by whether any **live** job still references the removed suites — not by
whether it prints zero lines. Commented-out blocks kept deliberately for reference, and the
project-owned Cypress jobs you add in Step 9, both legitimately match this pattern. (The
`python3 -c "import yaml"` check needs PyYAML installed; if it's missing, use any other YAML
parser available rather than skipping validation.)

Also check no remaining job has a `needs:` list referencing a job name you just deleted.

## Step 4 — Remove deploy configs and install-pipeline configs — verify before deleting

Find candidate files:
```bash
grep -rl "robot\|cypress" .github/deploy/ config/install/ 2>/dev/null
```

**Before deleting any file, grep the whole repo for its literal filename** to confirm nothing
else references it — don't trust that a "robot"/"cypress" name means it's exclusively used by
the suites you're removing:
```bash
grep -rln "<exact-filename>" --include="*.yml" --include="*.php" . | grep -v vendor
```

This check matters most for **data-import fixture configs**. A file named something like
`*_ROBOT.yml` might either (a) be used only by the Robot-Framework-specific install pipeline
you're deleting — in which case it (and its underlying CSV fixture directories) is now
orphaned and should also be deleted — or (b) also be referenced generically by the project's
regular (non-Robot) install pipelines under a step like `import-eu-region-demodata` — in
which case it must be **kept**, since deleting it would break unrelated demodata imports.
**Check the actual `command:`/`source:` lines in each install pipeline yml directly** —
don't infer from a research summary or from what a similar project did; re-verify with a live
grep every time, since naming conventions vary and can be misleading.

Also check for any environment-specific PHP config solely tied to a deleted deploy
environment (e.g. a config file only loaded when `environment: docker.ci.cypress` is
referenced) and remove it once nothing references that environment name anymore.

## Step 5 — Clean up `.gitignore`

Remove now-dead ignore entries tied to the removed suites (old install path, old result-output
directories). Don't remove ignore entries you're not sure are dead — grep first.

## Step 6 — Detect the project's existing locator convention

**Do not assume any locator attribute convention.** Check what the project's own templates
already use:
```bash
grep -rl "data-qa=" src/ --include="*.twig" 2>/dev/null | wc -l
grep -rl "data-testid=" src/ --include="*.twig" 2>/dev/null | wc -l
grep -rl "data-cy=" src/ --include="*.twig" 2>/dev/null | wc -l
```
(Adjust the template glob for the project's actual templating — Twig, JSX, Blade, etc.)

- If one convention already has real usage in the project's templates, use that one — adding
  a second, competing convention creates long-term maintenance drift.
- If none exist yet, `data-qa` is a reasonable default — it's what the b2b-demo-marketplace
  reference implementation (Step 7) already uses throughout — but **confirm this choice with
  the user explicitly** before proceeding — it's a project-wide convention that's expensive to
  reverse once tests and templates are built on it.

## Step 7 — Vendor the `tests/cypress-boilerplate/` implementation from b2b-demo-marketplace

The source of truth is no longer the raw `spryker-projects/cypress-boilerplate` template —
it's the **already-adapted, already-battle-tested** copy committed at
`tests/cypress-boilerplate/` in `spryker-shop/b2b-demo-marketplace`. That copy has had real
bugs found and fixed against a live Spryker B2B Marketplace instance (locator fixes, OMS
transition timing/race fixes, search-index sync timing, DataTables-based list filtering, etc.)
that the raw upstream template does not have. Vendoring from it means the target project
starts from a working baseline instead of rediscovering the same bugs from scratch.

```bash
# NOTE: tests/cypress-boilerplate/ does not exist on the default branch (master) — a plain
# `git clone` checks out master and the rsync below would fail with "No such file or directory".
# Clone the branch that actually contains it, and confirm before copying.
git clone --depth 1 --branch add-cypress-boilerplate \
  https://github.com/spryker-shop/b2b-demo-marketplace.git <scratch-dir>
ls <scratch-dir>/tests/cypress-boilerplate/package.json   # must exist before proceeding
rsync -a --exclude='.git' --exclude='node_modules' \
  <scratch-dir>/tests/cypress-boilerplate/ <project>/tests/cypress-boilerplate/
```

If that branch has since been merged to `master`, drop the `--branch` flag — but verify the
directory exists on whatever branch you clone rather than assuming it does:
```bash
git ls-remote --heads https://github.com/spryker-shop/b2b-demo-marketplace.git   # list available branches
```

Pick a destination path that is **not** the old (now-removed) installer-path directory and is
**not** covered by a stale `.gitignore` rule from Step 5 — `tests/cypress-boilerplate/` is the
established convention; only deviate from it with an explicit reason. Verify:
```bash
git check-ignore -v <project>/tests/cypress-boilerplate   # must produce no output
```

Then adapt, discovering each value rather than assuming a default is correct:
- `package.json`: set `name`/`description` to the project's own.
- `.envs/.env.local` / `.envs/.env.ci` / etc.: set `BACK_OFFICE_URL`/`STOREFRONT_URL`/
  `GLUE_URL`/`MP_URL` to the project's actual local/CI hostnames — grep existing CI workflows
  or install configs for `*.spryker.local` (or whatever domain convention the project uses)
  rather than trusting the reference project's own hostnames are right for this one.
- `PROJECT_LOCATION` (used by any CLI-exec-based commands, e.g. OMS transitions): set to the
  relative path from the vendored Cypress directory back to the repo root (e.g. `../..` if
  Cypress runs with `tests/cypress-boilerplate` — two directories deep — as its working
  directory). Set this in **every** `.envs/.env.<environment>` file that needs it, including
  the local one — a value only set for CI and missing locally silently no-ops any CLI-exec
  step when run locally instead of failing loudly.
- If Step 6 determined the project's convention differs from `data-qa` (what this reference
  copy already uses throughout), update the vendored page-object selectors accordingly.
- **Verify the vendored fixture data is real for this project**, don't just trust it:
  ```bash
  grep -rl "<fixture-customer-email>" data/import/common/ 2>/dev/null
  grep -rl "<fixture-product-sku>" data/import/common/ 2>/dev/null
  ```
  If the values aren't found in the project's own demodata, replace them with values that
  are — a smoke test asserting on data that doesn't exist in this project's environment will
  fail for reasons unrelated to the code under test. Prefer merchants/products/customers with
  no unusual restrictions (e.g. B2B Purchasing Control has no Glue REST API support at all —
  a customer whose business unit requires cost-center/budget selection will make any
  Glue-API-based test-setup scaffolding fail with a 422 regardless of the code under test).

## Step 8 — Add representative smoke tests

At minimum, prove the baseline works end-to-end with:
1. A storefront homepage → search/PDP → add-to-cart flow.
2. A full checkout flow.

Reuse whatever page objects/scenarios the vendored `tests/cypress-boilerplate/` copy already
ships (it already includes working examples for common Spryker flows — check
`cypress/support/page-objects/` and `cypress/e2e/` before writing new ones). Every test must
reset any state it mutates in a `before`/`beforeEach` hook (deterministic setup/cleanup) and
assert on specific, meaningful content rather than mere presence/visibility.

Run locally before moving on:
```bash
cd <project>/tests/cypress-boilerplate && npm ci
npm run lint:check && npm run prettier:check   # NOT `code:check` — see below
npm run cy:run
```

Do **not** use `npm run code:check` as a pass/fail gate. The boilerplate defines it as
`eslint . ; prettier . --check` — the `;` means the script exits with *prettier's* status, so a
real ESLint failure is silently reported as success. Always run `lint:check` and
`prettier:check` as separate commands (this is why the CI job in Step 9 runs them as two
separate steps). `code:check` is fine for eyeballing all issues at once, just not for gating.
(`cy:run` requires a locally booted instance of the target project — if one isn't available
in your environment, say so explicitly rather than claiming this step passed.)

## Step 9 — Wire CI, reusing the project's existing acceptance stack

Find the project's existing CI job that boots a full Docker/SDK stack for its other UI/API
tests (Codeception acceptance/functional, etc.) — reuse its `docker/sdk boot <deploy>.yml` /
`docker/sdk up` pattern rather than inventing a new deploy config:
```bash
grep -rn "docker/sdk boot" .github/workflows/*.yml
```

Add two jobs:
1. A fast, Docker-independent lint/format job (`npm ci && npm run lint:check && npm run
   prettier:check` in the Cypress directory) that runs on every push/PR.
2. A Docker-dependent E2E job that `needs:` the lint job (fail fast, avoid booting the full
   stack on a trivial style violation), boots the reused acceptance stack, sets up Node,
   installs the Cypress project's dependencies, and runs `npx cypress run --env
   environment=ci ...`, uploading screenshots/reports as artifacts on failure.

Match the existing workflow file's formatting/indentation style exactly, then validate:
```bash
python3 -c "import yaml; yaml.safe_load(open('<workflow-file>')); print('OK')"
```

## Step 10 — Generate the companion day-to-day skill

Write `.claude/skills/cypress-tests/SKILL.md` in the target project (check
`.claude/skills/` for any existing skills first and match their frontmatter/structure
convention). It should document, using the values actually discovered/decided in this
migration (not generic placeholders):
- The vendored directory path and its naming conventions (spec/fixture/page-object naming).
- The **actual** locator convention decided in Step 6.
- The actual npm scripts available (`cy:open`, `cy:run`, etc. — confirm against the vendored
  `package.json`, don't assume names).
- The actual CI job names added in Step 9, so Claude can point developers at them.
- A quality-gate checklist matching what CI enforces: tests pass, lint/format clean, no
  brittle selectors (no raw `cy.get()` outside page objects; no positional/XPath selectors),
  deterministic setup/cleanup, and clear/specific assertions.

## Step 11 — Update project documentation

If the project's README (or equivalent) documents the old demo-shop suites, update it to
describe the new project-owned setup, its location, and a pointer to the new
`cypress-tests` skill.

## Final checklist (acceptance criteria)

- [ ] 1. `composer.json` and `composer.lock` no longer contain `spryker/cypress-tests` or
      `spryker/robotframework-suite-tests`.
      ```bash
      grep -n "spryker/cypress-tests\|spryker/robotframework-suite-tests" composer.json composer.lock
      ```
      must return nothing.
- [ ] 2. No active project command, configuration, or CI job depends on the removed demo-shop
      Cypress or Robot Framework suites. Repo-wide grep for the removed packages' names and
      deleted file names (deploy configs, docker-compose files, install pipelines) returns
      nothing outside historical git log; no remaining CI job's `needs:` references a job you
      deleted; data-import fixture configs still used by *other* pipelines were verified as
      still referenced (Step 4), not deleted by mistake.
- [ ] 3. The target project's repository contains its own Cypress boilerplate — vendored
      under `tests/cypress-boilerplate/` (per Step 7), committed to the repo, not a Composer
      dependency and not re-fetched at CI/runtime.
- [ ] 4. Cypress can be installed and executed using documented project commands (`npm ci`,
      `npm run cy:run`, `npx cypress open`, etc.) without any Composer-based Cypress
      dependency. `npm run lint:check` **and** `npm run prettier:check` each pass in the
      vendored Cypress directory (run separately — `code:check` masks ESLint failures, Step 8).
- [ ] 5. At least one project-specific Cypress smoke test runs successfully against a
      configured target environment (or the limitation is stated explicitly if no environment
      was available to test against).
- [ ] 6. Test selectors, fixtures, test users, and assertions used by the initial tests are
      project-owned — verified against the target project's own demodata/templates (Step 6/7),
      not copied from the reference project's fixtures unexamined; no demo-shop-specific
      dependencies or locators remain.
- [ ] 7. A Claude Skill for Cypress (`cypress-tests`, generated in Step 10) is available in
      the project repository and contains clear, actionable instructions for Claude to create,
      run, review, and validate Cypress tests.
- [ ] 8. That Claude Skill defines the project's actual conventions for test structure,
      naming, locators, fixtures/test data, environment variables, and supported test
      commands — the real, discovered values, not generic placeholders.
- [ ] 9. That Claude Skill applies an executable quality gate that, at minimum, verifies tests
      pass, lint/format is clean, no brittle selectors are used (no raw `cy.get()` outside
      page objects, no positional/XPath selectors), setup/cleanup is deterministic, and
      assertions are clear and specific.
- [ ] 10. Cypress setup, initial tests, the Claude Skill, and quality-gate automation are all
      committed to the main repository — including the new/edited CI workflow YAML, verified
      to parse and to follow the existing file's formatting.
