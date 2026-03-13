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

---

## Step 1 — Gather Required Information

Before doing anything, collect all required inputs. Check the conversation for answers already
provided. Ask only for what is still missing.

| Info | Question to ask |
|------|----------------|
| JIRA ticket | "What is the JIRA ticket number for this integration? (e.g. ABC-1234)" |
| Integration ticket | "Is there a separate integration/BugFix Integration ticket? If so, what is its number?" |
| Upcoming release tag | "What is the tag of the upcoming release? (e.g. 1.23.0)" |
| Suite PR URL | "What is the GitHub URL of the pull request in spryker/suite that needs to be integrated?" |
| Packages to update | "Which spryker/* packages need to be updated? (list them all)" |
| Security fix | "Is this a security fix integration? (yes/no)" |
| Project changes | "Are there project-level changes in the suite PR that also need to be applied to the demoshop? (yes/no)" |
| Branch check | "Are you on the master branch and have you pulled the latest changes before starting?" |

If the user says they are **not** on an updated master branch, stop and ask them to do that first.

---

## Step 2 — Create the Integration Branch

Determine the branch prefix based on the integration type:

| Type | Prefix |
|------|--------|
| Regular integration | `integration/` |
| BugFix / BugFix Integration ticket | `bugfix/` |
| Security fix | `security/` |

Derive the branch name from the JIRA ticket and a short slug of the ticket title/description:

```
{prefix}{TICKET-NUMBER}-{short-description}
```

**Example:** `integration/abc-1234-update-kernel-package`

Run:
```bash
git checkout -b {branch-name}
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
  with version `dev-master as {upcoming-release-tag}`, then include it in the `composer update`
  call in Step 5.

---

## Step 4 — Update Feature Package Versions in composer.json

For each resolved `spryker-feature/*` package, update its version constraint in the root
`composer.json` to:

```
"dev-master as {upcoming-release-tag}"
```

**Example** (upcoming release `1.23.0`):
```json
"spryker-feature/spryker-core": "dev-master as 1.23.0"
```

Edit `composer.json` directly. After all changes are made, verify the file is valid JSON:

```bash
php -r "json_decode(file_get_contents('composer.json')); echo json_last_error() === JSON_ERROR_NONE ? 'valid' : 'invalid JSON';"
```

---

## Step 5 — Run Composer Update

Run `composer update` for all resolved feature packages at once, including any exception packages
that were added directly:

```bash
composer update {space-separated list of all feature packages and direct exception packages} --with-dependencies --ignore-platform-req=ext-redis --ignore-platform-req=ext-grpc --ignore-platform-req=ext-amqp
```

**Example:**
```bash
composer update spryker-feature/spryker-core spryker-feature/merchant spryker/some-rest-api --with-dependencies --ignore-platform-req=ext-redis --ignore-platform-req=ext-grpc --ignore-platform-req=ext-amqp
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

## Step 7 — Summary Before Commit

Before committing, present a summary to the user:

```
Integration summary
-------------------
Branch:            {branch-name}
JIRA ticket:       {ticket}
Integration ticket:{integration-ticket or "none"}
Upcoming release:  {tag}
Suite PR:          {url}
Security fix:      yes/no

Composer changes:
  Feature packages updated: {list}
  Packages added directly:  {list or "none"}

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
git commit -m "{TICKET-NUMBER}: integrate package updates and project changes from suite PR {PR-number}"
git push origin {branch-name}
```

If there were conflicts, use this commit message instead:

```bash
git commit -m "{TICKET-NUMBER}: integrate package updates from suite PR {PR-number} — ⚠ manual conflict resolution required"
```

After pushing, output the branch URL:
```
https://github.com/{demoshop-repo}/tree/{branch-name}
```

---

## Step 9 — Create Pull Request

Create a pull request targeting `master` using the repo's PR template:

```bash
gh pr create --base master --title "{TICKET-NUMBER}: {short-description}" --body "$(cat <<'EOF'
#### Overview

- Ticket: https://spryker.atlassian.net/browse/{TICKET-NUMBER}

###### Change log

- Updated feature packages: {list}
- Packages added directly: {list or "none"}
- Project changes applied from suite PR {PR-number}: {count} files
- Conflicts requiring manual review: {count} files

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

- The **title** uses the JIRA ticket number and the short description from the branch name.
- The **change log** summarises feature packages updated, directly added packages, project changes applied, and conflicts flagged.

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

| Demoshop | GitHub repo |
|----------|------------|
| b2b-marketplace | `spryker-shop/b2b-marketplace-demo-shop` |
| b2b | `spryker-shop/b2b-demo-shop` |
| b2c-marketplace | `spryker-shop/c2c-marketplace-demo-shop` |
| b2c | `spryker-shop/b2c-demo-shop` |

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
- [ ] Branch created with correct prefix and naming
- [ ] All spryker packages resolved to feature packages (or added directly for exceptions)
- [ ] `composer.json` updated with `dev-master as {tag}` for all feature packages
- [ ] `composer update` ran successfully
- [ ] Suite PR diff fetched and applied (if project changes)
- [ ] Conflicts documented and flagged
- [ ] Summary confirmed by user
- [ ] Changes committed and pushed
- [ ] Pull request created targeting master
- [ ] PR URL shared for ticket updates
