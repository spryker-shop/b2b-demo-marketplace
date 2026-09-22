---
name: demoshop-integration
description: >
  Use this skill whenever a user wants to integrate package updates or project changes from the
  spryker/suite repository into a Spryker demoshop (b2b-marketplace, b2b, b2c-marketplace, b2c).
  Trigger on phrases like "integrate ticket", "do the integration", "update packages in demoshop",
  "integrate PR", "run integration", "apply suite changes", or any mention of integrating a JIRA
  ticket or suite pull request into a demoshop. This skill guides the full end-to-end workflow:
  gathering info, resolving feature packages via composer, updating composer.json, running
  composer update, fetching and applying project-level changes from the suite PR, and committing.
---

# Demoshop Integration Skill

This skill automates the full integration workflow for applying Spryker package updates and
project-level changes from `spryker/suite` into a demoshop. It is primarily focused on
**b2b-marketplace** but applies to all four demoshops.

Scope: **one ticket, in the demoshop(s) it affects.** Driving a whole bi-weekly BugFix
Integration round — reading the scope JQL, ordering the tickets, tracking a ledger, closing out
the umbrella JIRA task — sits above this skill and calls into it per ticket.

| Step | What |
|------|------|
| 0–1 | Prerequisites, gather info |
| 2 | Branch (+ check for an existing PR) |
| 3–5 | Resolve packages, update `composer.json`, minimal `composer update` |
| 6 | Apply project-level changes from the suite PR |
| **6b** | **Security fan-out — all four demoshops** |
| **6c** | **Build steps: cache / propel migration / transfer / `api:generate`, and verification** |
| 7–10 | Summary, commit, draft PR, report |

---

## Step 0 — Check Prerequisites

Before starting the workflow, verify that all required CLI tools are available.

### GitHub CLI (`gh`)

Run:
```bash
gh --version
```

- **If the command succeeds:** continue to Step 1.
- **If the command fails (not found):** stop and display this message to the user:

```
The GitHub CLI (gh) is required for this integration workflow but was not found on your system.

Install it:
  macOS:   brew install gh
  Linux:   https://github.com/cli/cli/blob/trunk/docs/install_linux.md
  Windows: winget install --id GitHub.cli

After installing, authenticate with:
  gh auth login

Then re-run this skill.
```

Do **not** proceed with the integration until `gh` is available and authenticated.

---

## Step 1 — Gather Required Information

Before doing anything, collect all required inputs. Check the conversation for answers already
provided. Ask only for what is still missing.

| Info                | Question to ask                                                                                          |
|---------------------|----------------------------------------------------------------------------------------------------------|
| JIRA ticket         | "What is the JIRA ticket number for this integration? (e.g. ABC-1234)"                                   |
| Integration ticket  | "Is there a separate integration/BugFix Integration ticket? If so, what is its number?"                  |
| Suite PR URL        | "What is the GitHub URL of the pull request in spryker/suite that needs to be integrated?"               |
| Current release tag | Can be taken from one of the spryker-feature/* packages defined in the composer.json (don't ask)         |
| Packages to update  | "Which spryker/* packages need to be updated? (list them all)"                                           |
| Security fix        | "Is this a security fix integration? (yes/no)"                                                           |
| Project changes     | "Are there project-level changes in the suite PR that also need to be applied to the demoshop? (yes/no)" |
| Branch check        | "Are you on the master branch and have you pulled the latest changes before starting?"                   |

If the user says they are **not** on an updated master branch, stop and ask them to do that first.

---

## Step 2 — Create the Integration Branch

Determine the branch prefix based on the integration type:

| Type | Prefix |
|------|--------|
| Regular integration | `integration/` |
| BugFix / BugFix Integration ticket | `bugfix/` |
| Security fix | `security/` |

Prefer letting `brancho` derive the name from the ticket:

```bash
brancho branch {TICKET-NUMBER}
```

If `brancho` is unavailable or its token is rejected, fall back to a hand-derived name using
the JIRA ticket and a short slug of the ticket title:

```
{prefix}{TICKET-NUMBER}-{short-description}
```

**Example:** `integration/abc-1234-update-kernel-package`

```bash
git checkout -b {branch-name}
```

If the type is **security**, stop and read *Step 6b — Security fan-out* now: the ticket needs a
branch in **all four** demoshops, not just this one.

### Check for an existing PR first

Someone may already have integrated this ticket. An open PR means your job is a **rebase**, not
a fresh integration — do not open a competing PR.

```bash
gh pr list --repo {demoshop-repo} --state all --limit 60 \
  --json number,title,state,url \
  --jq '.[] | select(.title|test("(?i){TICKET-NUMBER}")) | "\(.number) [\(.state)] \(.title) — \(.url)"'
git branch -a --format='%(refname:short) %(committerdate:short)' | grep -i {TICKET-NUMBER}
```

---

## Step 3 — Resolve Feature Packages

For each spryker package the user wants to update, determine which `spryker-feature/*` package
pulls it in using `composer depends`.

### Standard packages

```bash
composer depends {vendor/package-name} | grep spryker-feature
```

Look for a result that matches `spryker-feature/*`. That is the feature package to update.

**Example:**
```bash
composer depends spryker/kernel
# → spryker-feature/spryker-core  ^1.22.0
```

Collect all resolved `spryker-feature/*` packages into a list (deduplicated).

### Exception packages

Packages matching these patterns are handled differently:
- `*-connector`
- `*-rest-api`
- `*-relationship`
- `*-merchant-portal-gui`

For these, first check if the package is already present in the **root `composer.json`** of the
demoshop:

```bash
cat composer.json | grep "vendor/package-name"
```

- **If present:** treat it like a standard package — run `composer depends` and resolve its
  feature package. If not found in a feature update this package.
- **If absent:** add it directly to the `require` section of the **root demoshop `composer.json`**
  with version `dev-master as {current-release-tag}`, then include it in the `composer update`
  call in Step 5.

---

## Step 4 — Update Feature Package Versions in composer.json

### 4a — First check whether you need to touch composer.json at all

Most `spryker-feature/*` packages constrain their modules with a caret
(`spryker/cart: ^7.17.0`), so a **minor** bump of an already-released module needs **no**
`composer.json` edit — just name the module in the `composer update` of Step 5.

```bash
composer show spryker-feature/{feature} | sed -n '/requires/,$p' | grep {module}
```

If the existing constraint already permits the fixed version, skip to Step 5. Editing
`composer.json` unnecessarily adds noise to the diff and invites an unrelated resolve.

### 4b — Only if the fix is not in any released tag: `dev-master`

```
"dev-master as {current-release-tag}"
```

**Example** (current release `202602.0`):
```json
"spryker-feature/spryker-core": "dev-master as 202602.0"
```

> ⚠️ **`dev-master` is not a routine step — it is a last resort, and it needs the user's
> explicit OK.** Flipping a feature package to `dev-master` pulls in **every** unreleased
> change in that feature's whole dependency chain, including new `conflict:` declarations that
> force unrelated majors.
>
> This has already bitten us. b2b-demo-marketplace PR #1252 flipped
> `spryker-feature/self-service-portal` to `dev-master` to reach one `structured_data` column
> change. That chain's new `conflict: spryker/api-platform <1.23.0` forced api-platform 1.23.0,
> which forced serializer 1.1.0, which silently changed every `Decimal`-backed API response
> field from a scale-preserving string (`"1.5000000000"`) to a bare JSON number (`1.5`). The PR
> went red on `Robot / API B2B` and stayed red — an API wire-format regression out of a
> column-type ticket.
>
> Before flipping, show the user what it drags in:
> ```bash
> composer update {feature} --dry-run 2>&1 | grep -E 'Upgrading|Downgrading|Installing|Removing'
> ```
> If that list contains anything you cannot explain, the right answer is to wait for the
> release tag, or to achieve the same effect with a project-level override in `src/Pyz`.

### 4c — Validate

Edit `composer.json` directly. After all changes are made, verify the file is valid JSON:

```bash
php -r "json_decode(file_get_contents('composer.json')); echo json_last_error() === JSON_ERROR_NONE ? 'valid' : 'invalid JSON';"
```

---

## Step 5 — Run Composer Update (minimal, no opportunistic bumps)

**Goal: update ONLY the packages this integration actually changes.** The lock diff should
ideally touch only the updated `spryker/*` package(s) plus the `dev-master` ref of their
`spryker-feature/*` meta-package — nothing else.

### 5a — Update the named packages together with their feature packages

Run `composer update` listing **both** the concrete `spryker/*` package(s) the user named **and**
their resolved `spryker-feature/*` package(s), plus any exception packages added directly.
**Do NOT pass `--with-dependencies`** — it opportunistically upgrades unrelated transitive
dependencies (e.g. `aws/aws-sdk-php`, `guzzlehttp/guzzle`, `neuron-core/neuron-ai`, symfony
polyfills) and can remove packages, polluting the lock diff.

```bash
composer update {spryker/named-package(s)} {resolved spryker-feature/* package(s)} {direct exception packages} --ignore-platform-req=ext-redis --ignore-platform-req=ext-grpc --ignore-platform-req=ext-amqp
```

**Example** (updating `spryker/ai-foundation`, pulled in by `spryker-feature/ai-commerce`):
```bash
composer update spryker/ai-foundation spryker-feature/ai-commerce --ignore-platform-req=ext-redis --ignore-platform-req=ext-grpc --ignore-platform-req=ext-amqp
```

> **Only add `--with-dependencies` if the targeted update fails** because the new package
> version genuinely requires bumped constraints (compare the package's `require` block in the
> old vs new lock — if identical, the bumps are NOT needed and you must avoid the flag).

### 5b — Verify the lock diff is minimal

After the update, inspect which packages changed in the lock:

```bash
git diff composer.lock | grep '"name":' | sort -u
```

The output should list only the intended package(s). If unrelated packages (aws-sdk, guzzle,
neuron-ai, polyfills, etc.) appear, you used `--with-dependencies` unnecessarily — revert and
re-run the targeted command:

```bash
git checkout composer.lock
composer update {spryker/named-package(s)} {resolved spryker-feature/* package(s)} --ignore-platform-req=ext-redis --ignore-platform-req=ext-grpc --ignore-platform-req=ext-amqp
```

### 5c — Guard `plugin-api-version` (do NOT let composer downgrade it)

A locally-installed composer with an older plugin API can rewrite the final
`"plugin-api-version"` line in `composer.lock` (e.g. `2.9.0 → 2.6.0`). This is an unwanted,
unrelated change. After every `composer update`, check it and revert if it was lowered:

```bash
git diff composer.lock | grep 'plugin-api-version'
```

If the diff shows the value was lowered (a `-` line with the higher version, a `+` line with the
lower), restore the original value by editing the last `"plugin-api-version"` line in
`composer.lock` back to what was there before. Confirm the lock is still valid:

```bash
composer validate --no-check-all --no-check-publish
```

If `composer update` fails, report the full error output to the user and stop.

---

## Step 6 — Apply Project-Level Changes from the Suite PR

> **Skip this step if the user confirmed there are no project-level changes.**

Use the GitHub CLI to fetch the PR diff from `spryker/suite`:

```bash
gh pr diff {PR-NUMBER-OR-URL} --repo spryker/suite
```

If a full URL was provided, extract the PR number from it first.

### Analysing the diff

Go through the diff and identify all changed files. Categorise them:

| Category | Examples |
|----------|---------|
| PHP classes | `src/**/*.php` |
| Config files | `config/**/*.php`, `config/**/*.xml` |
| Data import / schema | `data/import/**`, `*.schema.xml`, `*.schema.yaml` |
| Frontend | `src/**/*.js`, `src/**/*.twig`, `assets/**` |

**IMPORTANT** Changes in src/Spryker, src/SprykerFeature, and src/SprykerShop have to be ignored. These changes are released into the respective repositories which will be updated as explained.

**Also ignore** — these are candidates that are almost never real project changes:

| Path | Why |
|------|-----|
| `src/Spryker/*/tests/**`, `**/codeception.yml` | Suite-internal test wiring, not shipped |
| `.github/workflows/**` | Suite CI. Suite PRs routinely carry along CI fixes belonging to a *different* ticket — read the hunk before assuming it is yours |
| root `composer.lock` | Third-party drift (aws-sdk, guzzle, doctrine-bundle). Never port |

Conversely, **never dismiss a one-line config flag.** Fixes are often released behind an opt-in
default of `false`, so the package update alone changes nothing. That single method *is* the
integration.

### Path translation: suite monorepo → demoshop

Suite nests project files under a per-module wrapper; the demoshops are flat. Strip the wrapper:

```
suite:    src/Pyz/{Module}/src/Pyz/{Layer}/{Module}/{rest}
demoshop: src/Pyz/{Layer}/{Module}/{rest}
```

| Suite path | Demoshop path |
|------------|---------------|
| `src/Pyz/Cart/src/Pyz/Zed/Cart/CartConfig.php` | `src/Pyz/Zed/Cart/CartConfig.php` |
| `src/Pyz/Propel/src/Pyz/Zed/Propel/PropelConfig.php` | `src/Pyz/Zed/Propel/PropelConfig.php` |

`config/**` and `data/**` paths are usually identical in both. Note that a file existing in one
demoshop does not mean it exists in another — the same ticket can be an edit in b2b-marketplace
and a brand-new file in b2c-marketplace.

### Applying changes

For each changed file in the diff:

1. Check if the file exists in the demoshop.
2. Apply the change:
   - **File exists, no local modifications:** apply the diff hunk directly.
   - **File exists, demoshop has local modifications:** apply what can be applied cleanly;
     for any hunk that cannot be applied cleanly, insert a clearly marked conflict comment:
     ```
     // ⚠ INTEGRATION CONFLICT — manual review required
     // Original suite change:
     // {conflicting hunk}
     ```
     Add the file to a **conflicts list** to report at the end.
   - **File does not exist in demoshop:** create it with the content from the suite PR.

Keep a running list of:
- ✅ Files applied cleanly
- ⚠️ Files with conflicts requiring manual review
- ➕ New files created

---

## Step 6b — Security Fan-out

> **Skip this step unless the user said this is a security fix.**

A security fix goes into **all four** demoshops, not just the marketplaces. Setting the
`security/` branch prefix is not the whole job.

A ticket is a security fix if any of these hold: `issuetype = "Security Issue"`; a `Security`
label or component; or the fix is a CVE, injection, auth/ACL bypass, open redirect, SSRF,
secret leak, or privilege escalation — regardless of how it is typed. **When it is ambiguous,
ask.** Do not decide unilaterally that a fix is "not really" security and skip two shops.

Repeat Steps 2–6 in each of the four repos. The legacy shops need extra care because they run a
release behind:

1. **Check the fixed version is even reachable** from the legacy constraint:
   ```bash
   composer why-not vendor/package {fixed-version}
   ```
2. **Three options, best first:**

   | Option | When |
   |--------|------|
   | Use a backport tag on the legacy series | Always check first — look for a lower patch release carrying the same fix |
   | Port the fix as a project override in `src/Pyz` | Change is small and self-contained, no backport exists |
   | Bump the feature constraint | Last resort, needs the user's OK, expect a large lock delta |

   If none is viable, the shop is **deferred with the blocker named** — and because it is a
   security fix, say so prominently rather than burying it.
3. **Never change a legacy checkout's git state on the user's behalf.** Read the current branch,
   report it, and let them decide.
4. **Expect no CI parity.** The legacy shops do not run the marketplaces' matrix. Verify with
   static analysis and whatever unit/functional lanes exist, and state plainly that E2E
   coverage is absent there.

Report one explicit outcome per shop — a PR URL, or the reason there isn't one. "Integrated into
the demoshops" is not an acceptable summary for a security fix.

---

## Step 6c — Build Steps

Run only what the change actually requires. This is not a blanket ritual.

| Change kind | Required step |
|-------------|---------------|
| Config flag / plain PHP in `src/Pyz` | `console cache:empty-all` |
| `*.schema.xml` | `propel:install` — or `propel:schema:copy` → `propel:model:build` → `propel:diff` → `propel:migrate`. **Inspect the generated migration before applying it.** |
| `*.transfer.xml` | `transfer:generate` |
| API Platform `*.resource.yml` / DSL change | `glue api:generate`, then Glue cache clear + warmup per application (`GLUE_STOREFRONT`, and `GLUE_BACKEND` if a backend resource moved) |
| Any generated-code change | Restart the Glue/PHP containers — OPcache serves stale generated classes otherwise |
| Frontend (`*.twig`, `assets/**`) | The matching `console frontend:project:build-*` |

### Propel schema overrides

A project override that changes an **existing attribute value** (not merely adds a column) is
rejected by the schema merger unless whitelisted. A `type` change therefore needs the schema
file **and**:

```php
// src/Pyz/Zed/Propel/PropelConfig.php
/**
 * @return array<string, array<string>>
 */
public function getWhitelistForAllowedAttributeValueChanges(): array
{
    return [
        'spy_product_page_search.schema.xml' => ['type'],
    ];
}
```

Omitting the whitelist produces a merger error at `propel:install`, not a silent no-op.

Column-type migrations on high-volume tables are real `ALTER TABLE` operations — core
deliberately leaves the biggest tables untouched and expects the project to opt in, which is why
these arrive as `src/Pyz` overrides. Running one against a shared or running stack needs the
user's explicit OK.

Verify a column-type migration by asking the database, not the schema file:

```sql
SELECT TABLE_NAME, DATA_TYPE, CHARACTER_MAXIMUM_LENGTH
FROM information_schema.COLUMNS
WHERE COLUMN_NAME = '{column}' AND TABLE_SCHEMA = DATABASE()
ORDER BY TABLE_NAME;
```

### Verification

- `vendor/bin/spryker-ci spryker-ci --current` for the static + touched-module gate.
- The tests that actually cover the change — name them, with file and line.
- `Robot / API` and `Robot / API B2B` are **pull-request-only** on the marketplaces. A green
  master proves nothing about them.
- **Never adjust a test expectation to match new output.** A changed assert is a changed
  contract; if the values moved, the package moved wrongly and that is a new ticket.

---

## Step 7 — Summary Before Commit

Before committing, present a summary to the user:

```
Integration summary
-------------------
Branch:            {branch-name}
JIRA ticket:       {ticket}
Integration ticket:{integration-ticket or "none"}
Suite PR:          {url}
Security fix:      yes/no

Composer changes:
  Feature packages updated: {list}
  Packages added directly:  {list or "none"}
  Packages moved:           {name} {from} → {to}   (one line each)

Build steps run:            {list, or "none required"}
Verification:               {job or manual check, and its result}

Project changes:
  ✅ Applied cleanly: {count} files
  ➕ New files:       {count} files
  ⚠️  Conflicts:      {count} files — manual review needed:
     - {file path}: {brief description of conflict}
```

Ask: **"Does everything look correct? Should I commit and push?"**

If there are conflicts, remind the user to resolve them before pushing.

---

## Step 8 — Commit and Push

Once the user confirms, stage and commit all changes:

```bash
git add .
git commit -m "{TICKET-NUMBER} Integrate package updates and project changes from suite PR {PR-number}"
git push origin {branch-name}
```

If there were conflicts, use this commit message instead:

```bash
git commit -m "{TICKET-NUMBER} Integrate package updates from suite PR {PR-number} — ⚠ manual conflict resolution required"
```

Commit messages and branch names may reference the JIRA key freely. **The code may not** — never
put a JIRA key in source, comments, config, or CI files.

After pushing, output the branch URL:
```
https://github.com/{demoshop-repo}/tree/{branch-name}
```

---

## Step 9 — Create Pull Request

> **Show the user the exact title and body and wait for an explicit OK before running this.**
> Creating a PR, editing a PR body, commenting on a PR or JIRA issue, and marking a PR ready are
> all outward sends — each needs its own approval, and approval for one does not carry to the
> next.

Create a **draft** pull request targeting `master` using the repo's PR template. Draft matters:
marking a PR ready fires the full, costly E2E suite.

```bash
gh pr create --draft --base master --title "{TICKET-NUMBER} {Sentence-case summary}" --body "$(cat <<'EOF'
#### Overview

- Ticket: https://spryker.atlassian.net/browse/{TICKET-NUMBER}
- Suite PR: https://github.com/spryker/suite/pull/{PR-number}

###### Change log

- Packages: {name} {from} → {to} (one line each)
- Packages added directly: {list or "none"}
- Project changes applied from suite PR {PR-number}: {count} files
- Conflicts requiring manual review: {count} files

###### Test plan

- {the named job or manual check that proves the fix}

###### CI Notice
**Additional tests** can be triggered by adding labels:
- `run-compatibility-ci` runs compatibility tests (PHP 8.3 / PostgreSQL / Debian / Prefer Lowest / Dynamic Store OFF):
    - Codeception / Acceptance & API
    - Codeception / Functional Tests
    - Robot / API
    - Robot / UI
    - Cypress / UI
EOF
)"
```

- The **title** is `{TICKET-NUMBER} {Sentence-case summary}` — no colon after the key, and never
  a `fix(KEY): …` conventional-commit form.
- The **change log** summarises packages moved (with from→to versions), directly added packages,
  project changes applied, and conflicts flagged.
- **One PR per demoshop per ticket.** Tickets that share a package chain share one PR per shop,
  because they share one `composer update`.
- Keep the body terse — summary, links, test plan. Detailed analysis belongs in JIRA.
- Never cite a local file path or a personal notes location in a PR or JIRA body; restate the
  content inline.

---

## Step 10 — Summary with PR URL for Ticket Updates

Present the PR URL prominently so the user can update relevant JIRA tickets:

```
✅ Pull request created!

PR URL: {pr-url}

Please add this PR URL to the following tickets:
- {JIRA ticket}
- {Integration ticket (if provided)}
```

---

## Reference: Demoshop Repositories

| Demoshop | GitHub repo | Release line |
|----------|-------------|--------------|
| b2b-marketplace | `spryker-shop/b2b-demo-marketplace` | current |
| b2c-marketplace | `spryker-shop/b2c-demo-marketplace` | current |
| b2b (legacy) | `spryker-shop/b2b-demo-shop` | one release behind |
| b2c (legacy) | `spryker-shop/b2c-demo-shop` | one release behind |

Read each shop's release tag off its own `composer.json` (the modal `2026NN.0` across the
`spryker-feature/*` constraints) — never assume, and never guess "the next one".

A shop that does not install the affected package is **out of scope** for that ticket. Decide
by the lock, not by the shop's name:

```bash
jq -r '.packages[] | select(.name=="vendor/package") | .version' composer.lock
```

Report an out-of-scope shop explicitly. Silently skipping one reads as "covered".

---

## Reference: Exception Package Patterns

These package name patterns are handled differently from standard packages (see Step 3):

- `*-connector`
- `*-rest-api`
- `*-relationship`
- `*-merchant-portal-gui`

They are checked for presence in the root `composer.json` first. If absent, they are added
directly to `require` rather than being resolved through a feature package.

---

## Quick Checklist

- [ ] All required info gathered
- [ ] On updated master branch
- [ ] No existing PR/branch already carries this ticket (else this is a rebase, not a new PR)
- [ ] Branch created via `brancho`, or with the correct prefix and naming
- [ ] All spryker packages resolved to feature packages (or added directly for exceptions)
- [ ] Existing caret constraints checked **before** editing `composer.json`
- [ ] Any `dev-master as {tag}` flip approved by the user, with its dry-run delta shown
- [ ] `composer update` ran for ONLY the named packages + their feature packages (no `--with-dependencies` unless required)
- [ ] Lock diff verified minimal (`git diff composer.lock | grep '"name":'` shows only intended packages)
- [ ] `plugin-api-version` in `composer.lock` not downgraded (reverted if composer lowered it)
- [ ] Resulting versions printed from the lock, not assumed
- [ ] Suite PR diff fetched; non-package files judged individually, paths translated
- [ ] Required build steps run (cache / propel / transfer / `api:generate`), container restarted if generated code changed
- [ ] Verification named and run; no test expectation was changed to match new output
- [ ] Security fix? → all four demoshops handled, one explicit outcome each
- [ ] Conflicts documented and flagged
- [ ] Summary confirmed by user
- [ ] Changes committed and pushed
- [ ] Draft pull request created targeting master, after explicit approval of title + body
- [ ] PR URL shared for ticket updates
