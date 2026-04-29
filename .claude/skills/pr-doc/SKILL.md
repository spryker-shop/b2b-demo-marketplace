---
name: pr-doc
description: Generate a feature integration documentation markdown file from a GitHub PR. Use this skill whenever the user provides a PR number and wants documentation, mentions "document this PR", "create PR docs", "generate integration guide", "write up this PR", "integration doc for PR #...", or asks to document changes from a pull request. If no PR number is given, the skill auto-detects it from the current git branch. Always use this skill even if the user just says something like "document PR 1234", "make docs for this PR", or just "create pr doc" with no number.
---

# PR Documentation Generator

Generate a clean, human-readable integration documentation file from a GitHub Pull Request diff.

## What this skill does

Given a PR number (or none — it will find one from the current branch), it:
1. Verifies `gh` CLI is available
2. Resolves the PR number from the current branch if not provided
3. Fetches the PR metadata and diff
4. Produces a single `integration-doc-pr-<number>.md` file explaining what changed and how to integrate it

## Step 1: Check for `gh` CLI

Run:
```bash
gh --version
```

If the command fails or is not found, stop and tell the user:

> `gh` CLI is required but not installed. Please install it from https://cli.github.com/ and then run `gh auth login` to authenticate. Once done, re-run this skill.

Do not continue until `gh` is confirmed available.

## Step 2: Resolve the PR number

If the user provided a PR number explicitly, use it and skip to Step 3.

Otherwise, look up the PR associated with the current branch:

```bash
git branch --show-current
```

If the branch is `master` or `main`, stop and tell the user:

> You're on the default branch. Please provide a PR number explicitly, e.g. `document PR 1234`.

Otherwise, use `gh` to find an open (or recently merged) PR for this branch:

```bash
gh pr list --repo <OWNER/REPO> --head <BRANCH_NAME> --state all --json number,title,state --limit 5
```

- If exactly one PR is returned, use that number and inform the user: "Found PR #<N>: <title>"
- If multiple PRs are returned, show a short numbered list and ask the user which one to document
- If no PR is found, tell the user and ask them to provide a PR number manually

## Step 3: Resolve the repository

If the user provided an explicit `owner/repo` slug, use it.

Otherwise detect the current repo from git remote:
```bash
git remote get-url origin
```

Parse the owner/repo from the URL (handles both HTTPS and SSH formats).

If no remote can be determined, ask the user: "Which GitHub repository should I look up? (e.g. `owner/repo`)"

## Step 4: Fetch PR data

Run these in parallel:

```bash
gh pr view <PR_NUMBER> --repo <OWNER/REPO> --json number,title,body,author,createdAt,mergedAt,baseRefName,headRefName,files,labels,additions,deletions
```

```bash
gh pr diff <PR_NUMBER> --repo <OWNER/REPO>
```

If either command fails with an auth error, tell the user to run `gh auth login` first.

## Step 5: Analyze the diff and metadata

Read through the diff carefully. Identify:

- **Purpose**: What problem does this PR solve? Use the PR title and body as primary signals, then confirm with the diff.
- **Changed files**: Group them by concern (config, source, tests, docs, migrations, etc.)
- **New dependencies**: Any new `composer.json`, `package.json`, `go.mod`, or similar changes
- **Configuration changes**: New env vars, config keys, feature flags, or infrastructure changes
- **Database changes**: Migrations, schema changes, new tables/columns
- **Breaking changes**: Removed methods, changed interfaces, renamed classes/modules, altered API contracts
- **Integration steps**: What a developer needs to do to pick up these changes in their own project

Use the PR body — authors often document their own integration steps there. Combine that with your reading of the diff to produce complete, accurate steps.

## Step 6: Write the integration doc

Save to `integration-doc-pr-<PR_NUMBER>.md` in the current working directory.

Use this structure:

```markdown
# Integration Guide: <PR Title>

**PR:** #<number> — <owner/repo>
**Author:** <author login>
**Merged:** <mergedAt date, or "Not yet merged">
**Base branch:** <baseRefName>

## Summary

<2–4 sentence description of what this PR does and why. Focus on the developer reading this — what does it mean for them?>

## Changed Files

<Group files by category. For each group, a short sentence about what changed. Don't list every file if there are many — summarize patterns.>

### Core / Source
- `path/to/file.php` — <what changed>

### Configuration
- `config/something.yaml` — <what changed>

### Tests
- `tests/...` — <what changed>

## New Dependencies

<List any new packages added. If none, omit this section.>

## Configuration Changes

<List new environment variables, config keys, or feature flags. Provide the key name and a brief explanation of its purpose. If none, omit.>

## Database Changes

<Describe any schema or migration changes. If none, omit.>

## Breaking Changes

<Call out anything that could break existing code — removed methods, changed signatures, renamed things. If none, write "None.">

## Integration Steps

<Numbered list of concrete actions a developer must take to integrate these changes. Be specific — include commands to run, files to update, config values to set. If it's a simple change with no steps needed, say "No additional integration steps required.">

1. ...
2. ...

## Notes

<Any additional context, caveats, or links. Remove this section if empty.>
```

## Output

After writing the file, tell the user:

> Integration doc written to `integration-doc-pr-<PR_NUMBER>.md`

Then print a brief summary (2–3 sentences) of the most important things in the PR so they know what to expect in the doc.

## Edge cases

- **Draft PRs**: Note at the top of the doc that it is a draft and may change.
- **Very large diffs (>2000 lines)**: Focus on the file-level summary rather than line-by-line analysis. Call out the size so the user knows coverage may be high-level.
- **No PR body**: That's fine — rely entirely on the diff. Don't invent a description.
- **Private repos**: `gh` handles auth transparently; if it fails, surface the auth error message verbatim.