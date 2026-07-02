# CI poll and auto-fix (Steps 10–12)

Detail for `@.claude/skills/upmerge-master-to-demo/SKILL.md`. The local smoke test, FE-anomaly scan, and `cy:demo` (Steps 7, 7a, 7b) live in `@.claude/skills/upmerge-master-to-demo/references/smoke.md`. This file covers everything after the PR is open: scheduling the pipeline poll, polling + auto-fixing deterministic failures, and the final report.

Log outcomes per the SKILL.md convention: `[upmerge 10/12] DONE  poll scheduled +60m`; `[upmerge 11/12]` a line per poll (`running`, `auto-fixed phpcs → pushed`, or `BLOCKED  phpstan failure`); `[upmerge 12/12] DONE  pipeline green`.

Invoked directly as `/upmerge-master-to-demo poll PR <N>` (optionally ` ticket <KEY>`), from a `ScheduleWakeup` callback or by the user: skip Steps 1–10 and go straight to Step 11. The ticket key is optional — without it, poll and report with no JIRA action.

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

  A red `Run Tests (Demo)` step maps back to Step 6e/6g and Step 7b (dropped demo block or wiring). For phpstan errors, broken tests, or unexpected runtime failures, stop and surface the failure with the relevant log excerpt — don't rewrite code logic in CI unattended.

## Step 12 — Final report

When green:
- Tell the user: `Upmerge PR <PR-URL> is green and ready for review.`
- If a ticket was provided (and the user hasn't asked to stay off JIRA), optionally add a best-effort PR-link comment. Never move the ticket's status.
- Don't merge the PR — that's the user's call.
