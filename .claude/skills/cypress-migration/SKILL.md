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
  suites, vendors in spryker-projects/cypress-boilerplate, wires up CI, and generates the
  companion day-to-day `cypress-tests` skill for the target project. It is written to be
  project-agnostic: every step discovers the target project's actual conventions before
  acting rather than assuming they match any specific reference project.
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

If neither package is present, this project doesn't need the removal steps — skip to Step 6
(vendoring) if the goal is just adding a Cypress baseline to a project that never had these
suites.

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

Then run `composer update --lock` (regenerates `composer.lock` and purges the corresponding
`vendor/` directories). If `composer`/PHP isn't available in your environment, tell the user
this step still needs to be run and why (the lock file must reflect the require-dev removal).

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
grep -n "robot\|cypress" .github/workflows/*.yml   # must return nothing
python3 -c "import yaml; yaml.safe_load(open('<file>')); print('OK')"   # each edited file
```

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
- If none exist yet, `data-qa` is a reasonable default consistent with the Spryker ecosystem
  and the reference boilerplate's own example tests, but **confirm this choice with the user
  explicitly** before proceeding — it's a project-wide convention that's expensive to reverse
  once tests and templates are built on it.

## Step 7 — Vendor `spryker-projects/cypress-boilerplate` as plain files

```bash
git clone --depth 1 https://github.com/spryker-projects/cypress-boilerplate.git <scratch-dir>
rsync -a --exclude='.git' --exclude='composer.json' <scratch-dir>/ <project>/tests/e2e/
rm -rf <project>/tests/e2e/.github   # its CI/PR-template files are for the standalone boilerplate repo, not this monorepo
```

Pick a destination path that is **not** the old (now-removed) installer-path directory and is
**not** covered by a stale `.gitignore` rule from Step 5 — e.g. `tests/e2e/`. Verify:
```bash
git check-ignore -v <project>/tests/e2e   # must produce no output
```

Then adapt, discovering each value rather than assuming a default is correct:
- `package.json`: set `name`/`description` to the project's own.
- `.envs/.env.local` / `.envs/.env.ci` / etc.: set `BACK_OFFICE_URL`/`STOREFRONT_URL`/
  `GLUE_URL`/`MP_URL` to the project's actual local/CI hostnames — grep existing CI workflows
  or install configs for `*.spryker.local` (or whatever domain convention the project uses)
  rather than trusting the boilerplate's defaults are right for this project.
- `PROJECT_LOCATION` (used by any CLI-exec-based commands, e.g. OMS transitions): set to the
  relative path from the vendored Cypress directory back to the repo root (e.g. `..` if
  Cypress runs with `tests/e2e` as its working directory).
- If Step 6 determined the project's convention differs from what the boilerplate ships with
  (`data-qa`), update the vendored page-object selectors accordingly.
- **Verify the vendored fixture data is real for this project**, don't just trust it:
  ```bash
  grep -rl "<fixture-customer-email>" data/import/common/ 2>/dev/null
  grep -rl "<fixture-product-sku>" data/import/common/ 2>/dev/null
  ```
  If the values aren't found in the project's own demodata, replace them with values that
  are — a smoke test asserting on data that doesn't exist in this project's environment will
  fail for reasons unrelated to the code under test.

## Step 8 — Add representative smoke tests

At minimum, prove the baseline works end-to-end with:
1. A storefront homepage → search/PDP → add-to-cart flow.
2. A full checkout flow.

Reuse whatever page objects/scenarios the vendored boilerplate already ships (it typically
already includes working examples for common Spryker flows — check
`cypress/support/page-objects/` and `cypress/e2e/` before writing new ones). Every test must
reset any state it mutates in a `before`/`beforeEach` hook (deterministic setup/cleanup) and
assert on specific, meaningful content rather than mere presence/visibility.

Run locally before moving on:
```bash
cd <project>/tests/e2e && npm ci && npm run code:check && npm run cy:run
```
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

## Final checklist

- [ ] `composer.json`/`composer.lock` no longer reference the removed packages.
- [ ] Repo-wide grep for the removed packages' names and deleted file names returns nothing
      outside of historical git log.
- [ ] Data-import fixture configs still in use by other pipelines were verified as still
      referenced (Step 4) — none of them were deleted by mistake.
- [ ] `npm run code:check` passes in the vendored Cypress directory.
- [ ] At least one smoke spec runs successfully against a booted target environment (or the
      limitation is stated explicitly if no environment was available to test against).
- [ ] New/edited CI workflow YAML parses and follows the existing file's formatting.
- [ ] `.claude/skills/cypress-tests/SKILL.md` exists, reflects this project's actual
      discovered conventions, and is listed as an available skill.
