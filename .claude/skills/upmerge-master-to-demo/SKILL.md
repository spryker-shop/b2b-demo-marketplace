---
name: upmerge-master-to-demo
description: Run the recurring "upmerge master → master-demo" workflow for the b2b-demo-marketplace repo. Use this skill whenever the user mentions an upmerge task, pastes a JIRA upmerge ticket URL (e.g. CC-XXXXX on board 2237), says "upmerge", "merge master to master-demo", "do the weekly upmerge", "update master-demo from master", or references the recurring task of bringing master changes into master-demo. The skill handles the whole loop: find the JIRA ticket, prepare branches, merge, resolve conflicts, refresh composer.lock, reconcile Pyz overrides with the incoming changes (especially Twig templates in src/Pyz that shadow changed core templates), smoke-test login locally, push, open the PR, transition the ticket to IN CR, then poll the pipeline starting at the 2h mark and try to auto-fix common failures before reporting back.
allowed-tools: Bash, Read, Edit, Write, Skill, ScheduleWakeup, AskUserQuestion, mcp__mcp-atlassian__*, mcp__chrome-devtools__*
user-invocable: true
---

# Upmerge master → master-demo

This skill automates the recurring upmerge task in `b2b-demo-marketplace`: bringing `master` into `master-demo` through a feature branch, opening a PR, and shepherding it through CI.

It is a destructive, multi-step workflow that touches the local working copy, the GitHub remote, JIRA, and the local Spryker stack. Pause and confirm with the user any time something is ambiguous — the cost of one extra question is far less than a bad force-push or a mis-resolved conflict.

## When to use this skill

Use it whenever the user:

- Pastes a JIRA URL pointing at a CC upmerge ticket (board 2237)
- Says "upmerge", "do the upmerge", "merge master to master-demo", or similar
- Asks about the recurring task of updating `master-demo` from `master`

If the user only asks for *part* of the flow (e.g. "just open the PR for me"), don't run the whole skill — do the requested step and skip the rest.

## High-level flow

1. Find the JIRA ticket
2. Sync `master` and `master-demo` from origin
3. Create a feature branch off `master-demo`
4. Merge `master` into it, resolve conflicts
5. Refresh `composer.lock` and run `composer install` locally
6. Reconcile Pyz overrides with the incoming changes (esp. Twig templates), audit `deploy.spryker-icpplus.yml` against the sibling deploy files, audit `config/Shared/config_default.php` for demo-only config blocks the merge silently dropped, **and upmerge the external `spryker/cypress-tests` repo's `master-demo` branch up to the cypress commit the demo-shop `master` pins (Step 6f)**
7. Smoke-test Yves and Backoffice login via the `login-chrome` skill, then ALWAYS run the demo Cypress group (`cy:demo`)
8. Push and open a PR targeting `master-demo`
9. Move the JIRA ticket to **IN CR**
10. Ask the user to review, then poll the GitHub Actions pipeline starting at +2h
11. Auto-fix common pipeline failures; surface unknowns to the user
12. Report success when the pipeline is green

Each step is described below with the exact commands.

---

## Step 1 — Find the JIRA ticket

The user may paste the ticket URL/key directly. Use it as-is.

If they don't, search the CC board for an open upmerge ticket:

```
mcp__mcp-atlassian__jira_search with jql:
  project = CC AND status != Done AND summary ~ "upmerge" ORDER BY created DESC
```

Show the candidates to the user and confirm which one. Extract:

- Ticket key (e.g. `CC-38849`) — used for the branch name and PR title
- Ticket URL — used in the PR body

If nothing matches, ask the user for the ticket key. Don't invent one.

## Step 2 — Sync master and master-demo

Before touching anything, make sure the working tree is clean:

```bash
git status --short
```

If there are uncommitted changes, stop and ask the user what to do. Don't stash silently — the user's in-progress work is more important than this automation.

Then update both base branches from origin. Do NOT use `git pull` on a checked-out branch that isn't the target — use fetch + fast-forward refspecs so we don't bounce HEAD around:

```bash
git fetch origin master:master master-demo:master-demo
```

If a fast-forward isn't possible (because the local branch has diverged from origin), surface that to the user — they may have unpushed work on `master` or `master-demo` that we shouldn't discard.

## Step 3 — Create the feature branch

Branch off `master-demo`:

```bash
git checkout master-demo
git checkout -b feature/<ticket-lower>/upmerge-latest-master
```

Where `<ticket-lower>` is the JIRA key lowercased (e.g. `CC-38849` → `cc-38849`).

If a local branch with the same name already exists from a previous attempt, ask the user whether to reuse it, recreate it, or pick a different suffix.

## Step 4 — Merge master in and resolve conflicts

```bash
git merge master --no-ff -m "Merge master into master-demo for upmerge <TICKET>"
```

### Conflict resolution policy

- **`composer.lock` conflicts**: always resolve by regenerating in Step 5 — `git checkout --theirs composer.lock` (taking the master-demo version as a base), then refresh below. The regeneration is what matters; the merged content is a throwaway.
- **`composer.json` conflicts**: usually `master` wins on package versions, but `master-demo` may have demo-only additions. Read both sides, prefer the union (highest versions, both demo and core additions), and surface the result to the user if it isn't obvious.
- **Source code / config conflicts**: try a structural resolution — keep both changes when they're additive (e.g. both sides added different plugins to a Dependency Provider list). When the conflict is a real overlap, pause and ask the user.
- **`config/Shared/config_default.php` conflicts — NEVER drop a demo-only block toward master.** This file is the single highest-risk file in the upmerge: it mixes core config (track master) with **demo-only blocks that exist only on `master-demo`** (e.g. the `AmazonQuicksightConstants` / AWS QuickSight block from `[CC-36926] Enable Amazon Quick Sight for demo shops`, demo S3/`FileSystemConstants` bucket shapes, demo payment/feature toggles). Two distinct traps live here:
  - **Trap A — silent deletion (no conflict marker).** If `master` *deletes or never had* a block that exists only on `master-demo`, and a master-side commit in this merge touches that region, the demo-only block can disappear with **zero conflict markers**. `git merge` will not flag it. (This is exactly how the QuickSight config was lost — a master commit "fix b2b only" removed the block while reconciling, and the dependency-provider wiring that *reads* that config was left in place → Back Office Analytics page hard-crashed with `Could not find config key "AMAZON_QUICKSIGHT:AWS_REGION"`.) **Step 6e below exists to catch this — it is mandatory even on a clean merge.**
  - **Trap B — inconsistent hand-merge.** When both sides edited adjacent blocks (e.g. the `FileSystemConstants::FILESYSTEM_SERVICE` S3 buckets), resolve **every sibling entry to the same shape** — don't apply master's change to some hunks and the demo shape to others. A half-and-half result (some buckets with `key`+`secret`, some without) is the tell-tale sign of a mis-merge. When the demo intent isn't obvious, pause and ask the user which shape is correct for this project's infra.

After resolving, stage and continue the merge:

```bash
git add -A
git commit --no-edit
```

## Step 5 — Refresh composer.lock and install locally

**Core principle**: the final `composer.lock` on the upmerge branch MUST have the same package versions as `master`'s lock, plus only the demo-only packages that exist in `master-demo`'s lock but not in `master`'s. We bring master's package set into master-demo verbatim — no unrelated version bumps.

### Step 5a — Inspect what differs between the two locks

Find which packages exist on each side. Demo-only packages are the ones we need to layer onto master's lock.

```bash
git show master:composer.lock | python3 -c "import json,sys; d=json.load(sys.stdin); print('\n'.join(sorted(p['name'] for p in d['packages']+d['packages-dev'])))" > /tmp/master-lock-pkgs.txt
git show master-demo:composer.lock | python3 -c "import json,sys; d=json.load(sys.stdin); print('\n'.join(sorted(p['name'] for p in d['packages']+d['packages-dev'])))" > /tmp/demo-lock-pkgs.txt

echo "--- on master but not on master-demo (should be empty) ---"
comm -23 /tmp/master-lock-pkgs.txt /tmp/demo-lock-pkgs.txt

echo "--- demo-only packages to layer in ---"
comm -13 /tmp/master-lock-pkgs.txt /tmp/demo-lock-pkgs.txt
```

If "on master but not on master-demo" is non-empty, surface that to the user — it usually means master added a package and master-demo's lock hasn't caught up, which the merge alone should handle, but worth double-checking the merged `composer.json` requires it.

Also sanity-check the merged `composer.json` matches: it should have all of master's requires plus only those demo-only packages:

```bash
git show master:composer.json | python3 -c "import json,sys; d=json.load(sys.stdin); reqs=list(d.get('require',{}).keys())+list(d.get('require-dev',{}).keys()); print('\n'.join(sorted(reqs)))" > /tmp/master-json.txt
python3 -c "import json; d=json.load(open('composer.json')); reqs=list(d.get('require',{}).keys())+list(d.get('require-dev',{}).keys()); print('\n'.join(sorted(reqs)))" > /tmp/merged-json.txt
diff /tmp/master-json.txt /tmp/merged-json.txt
```

### Step 5b — Reconstruct composer.lock = master's lock + demo-only entries

Take master's lock verbatim and splice in each demo-only package entry. This guarantees zero unintended version bumps.

```bash
git show master:composer.lock > /tmp/master.lock
git show master-demo:composer.lock > /tmp/demo.lock

python3 <<'PYEOF'
import json
master = json.load(open('/tmp/master.lock'))
demo = json.load(open('/tmp/demo.lock'))

demo_only = ['spryker-eco/amazon-quicksight']  # <-- replace with the list from Step 5a

for name in demo_only:
    pkg = next((p for p in demo['packages'] if p['name'] == name), None)
    dev = pkg is None
    if dev:
        pkg = next(p for p in demo.get('packages-dev', []) if p['name'] == name)
    target = master['packages-dev' if dev else 'packages']
    # Insert preserving alphabetical order (composer convention)
    inserted = False
    for i, p in enumerate(target):
        if p['name'] > name:
            target.insert(i, pkg)
            inserted = True
            break
    if not inserted:
        target.append(pkg)

# CRITICAL: ensure_ascii=False so non-ASCII chars (e.g. "Kévin Dunglas") stay as
# raw UTF-8 the way composer writes them — otherwise the diff against master
# explodes with hundreds of lines of \uXXXX-escape noise.
with open('composer.lock', 'w', encoding='utf-8') as f:
    json.dump(master, f, indent=4, ensure_ascii=False)
    f.write('\n')
print('reconstructed composer.lock')
PYEOF
```

### Step 5c — Refresh content-hash and verify

The content-hash now needs to match the merged `composer.json`. `composer update --lock --no-install` only updates the lockfile metadata (hash + any new required entries) — it does NOT touch package versions when the existing entries already satisfy constraints.

```bash
composer update --lock --no-install --ignore-platform-reqs
composer install --ignore-platform-reqs
```

**Watch out for `plugin-api-version`.** Composer rewrites this field based on the *local* composer binary version, not the project. Master's lock has whatever `plugin-api-version` was current when master was last regenerated — usually higher than what your local composer writes. After Step 5c, restore master's value:

```bash
python3 <<'PYEOF'
import json
current = json.load(open('composer.lock'))
master = json.load(open('/tmp/master.lock'))
if current.get('plugin-api-version') != master.get('plugin-api-version'):
    print(f'restoring plugin-api-version: {current.get("plugin-api-version")} -> {master.get("plugin-api-version")}')
    current['plugin-api-version'] = master['plugin-api-version']
    with open('composer.lock', 'w') as f:
        json.dump(current, f, indent=4)
        f.write('\n')
else:
    print('plugin-api-version already matches master')
PYEOF
```

After installing, verify the lockfile has zero version diffs against master (apart from the deliberate demo-only adds):

```bash
python3 <<'PYEOF'
import json
master = json.load(open('/tmp/master.lock'))
current = json.load(open('composer.lock'))

def pkg_versions(lock):
    return {p['name']: p['version'] for p in lock['packages'] + lock.get('packages-dev', [])}

m = pkg_versions(master); c = pkg_versions(current)
diffs = [(n, m.get(n), c.get(n)) for n in sorted(set(m)|set(c)) if m.get(n) != c.get(n)]
print(f'{len(diffs)} version diffs vs master:')
for n, mv, cv in diffs[:30]:
    print(f'  {n}: master={mv} current={cv}')
PYEOF
```

The only diffs should be `master=None current=<version>` rows for the demo-only packages you intentionally added. Any other diff means something went wrong — stop and reconcile before committing.

### Step 5d — Commit

```bash
git add composer.lock
git commit -m "chore(composer): refresh lockfile after upmerge"
```

(Skip the commit if `git status` shows no changes — it can happen if the merge already produced a clean lock.)

### What NOT to do

- **Never run a bare `composer update`** (without `--lock`) — that resolves the whole dependency graph fresh and bumps unrelated packages beyond what master has. The PR would contain hundreds of unrelated version bumps.
- **Don't trust `composer update --lock` alone after a merge with new requires.** It refuses to add missing entries for packages that aren't in the current lock — that's why Step 5b splices them in manually first.
- **Don't take master-demo's lock as the base.** It may have versions older than master's. We want master's bumps applied to master-demo, not the other way around.

## Step 6 — Reconcile Pyz overrides with the incoming changes

The merge brings two kinds of change into `master-demo` that Demo's project-level code in `src/Pyz/` may need to stay in sync with. Neither is caught by `git merge` conflict markers, because the override files and the changed upstream files live at *different paths* — git sees no overlap, so the divergence is silent. This step makes it explicit.

> **Why this matters for Demo specifically.** A file at `src/Pyz/{Layer}/{Module}/...path...` *shadows* the core template/class at `vendor/spryker*/.../src/{Org}/{Layer}/{Module}/...path...`. When `master` updates the core file (via a `composer.lock` bump) or updates a `Pyz` file, the Demo override does **not** inherit that change — it keeps rendering its old copy. Twig overrides are the most common and most visible case (636 in `src/Pyz/.../Presentation/`, 577 under `src/Pyz/Yves/.../Theme/`), so give them special attention.

### Step 6a — Pyz changes that arrived via the merge

List every `src/Pyz/` file the merge touched, Twig first:

```bash
echo "--- Pyz files changed by the merge (Twig first) ---"
git diff --name-status master-demo...HEAD -- 'src/Pyz/**/*.twig'
echo "--- other Pyz files changed by the merge ---"
git diff --name-status master-demo...HEAD -- 'src/Pyz' ':(exclude)src/Pyz/**/*.twig'
```

For each changed Pyz file, decide whether the change belongs in Demo:

- **Keep** if it's a generic core/Pyz improvement that Demo should track.
- **Adapt or revert** if Demo intentionally diverges here (demo-only copy, marketplace-specific markup, branded content). Demo overrides often differ from `master`'s Pyz on purpose — don't blindly accept master's version. When unsure whether a Pyz divergence is intentional, **pause and ask the user** rather than silently overwriting.

### Step 6b — Core (vendor) Twig changes shadowed by a Pyz override

This is the case the merge can never surface on its own: `master` bumped a vendor package whose **core Twig template changed**, and Demo has a `Pyz` Twig override of that exact template. The override silently keeps the old markup.

**`vendor/` is git-ignored in this repo** (`/vendor/` in `.gitignore`), so you can't `git diff` vendor templates directly. Instead work from the lockfile: find which packages changed version in the upmerge, map each to its module, and intersect with the modules that have a Pyz Twig override. (Validated against the latest upmerge — this is the reliable path here.)

Run this AFTER Step 5 (so `composer.lock` reflects the merged set):

```bash
python3 - <<'PYEOF'
import json, re, subprocess

# Packages whose VERSION changed between the pre-merge lock and the merged lock.
before = json.load(open('/tmp/demo.lock'))      # master-demo's lock, saved in Step 5b
after  = json.load(open('composer.lock'))        # the merged/refreshed lock
def vers(l): return {p['name']: p['version'] for p in l['packages'] + l.get('packages-dev', [])}
vb, va = vers(before), vers(after)
changed = {n: (vb.get(n), va.get(n)) for n in sorted(set(vb) | set(va)) if vb.get(n) != va.get(n)}

# package "spryker/cms-slot-block-gui" -> module "CmsSlotBlockGui"
def module_of(pkg):
    return ''.join(w.capitalize() for w in pkg.split('/', 1)[1].split('-'))
changed_modules = {module_of(p): p for p in changed}

# Pyz Twig overrides, grouped by the module they live under.
def module_from_pyz(path):
    m = re.search(r'/(?:Zed|Yves|Glue|Client|Service)/([^/]+)/', path)
    return m.group(1) if m else None
pyz_twigs = [p for p in subprocess.run(['git','ls-files','src/Pyz'],
             capture_output=True, text=True).stdout.splitlines() if p.endswith('.twig')]

hits = [(module_from_pyz(p), changed_modules[module_from_pyz(p)], p)
        for p in pyz_twigs if module_from_pyz(p) in changed_modules]

if not hits:
    print(f"{len(changed)} packages changed version; none has a Pyz Twig override. "
          "Nothing to align in Step 6b.")
else:
    print("Pyz Twig overrides whose CORE package changed in this upmerge — review & align:")
    for mod, pkg, p in hits:
        o, n = changed[pkg]
        print(f"  [{pkg} {o}->{n}]  OVERRIDE: {p}")
PYEOF
```

For each hit, diff the Pyz override against the freshly-installed core template to see what upstream changed, then port the relevant markup/logic into the override:

```bash
# {Layer}/{Module}/<...> after src/Pyz mirrors the vendor path after src/<Org>.
diff <pyz-override-path> \
     vendor/<vendor-package>/src/<Org>/<Layer>/<Module>/<...>/<name>.twig
# e.g.
# diff src/Pyz/Zed/ProductManagement/Presentation/Add/index.twig \
#      vendor/spryker/product-management/src/Spryker/Zed/ProductManagement/Presentation/Add/index.twig
```

If a change is large or its intent is unclear, **surface the list to the user and ask** which overrides to update — don't guess at branded/demo-specific markup.

> If a future checkout ever *does* track `vendor/`, you can instead intersect changed vendor `.twig` paths with Pyz overrides by their `{Layer}/{Module}/<rest>` path-signature — same idea at file granularity instead of module granularity.

### Step 6c — Record what you reconciled

In the PR body (Step 8) list the Pyz files you kept, adapted, or aligned, so the reviewer can see the override reconciliation was done deliberately and not skipped.

### Step 6d — Audit `deploy.spryker-icpplus.yml` against the other deploy files

Same silent-divergence trap as Pyz overrides, one level up: the merge can introduce a feature that needs a **deploy-level** entry (a Yves endpoint with an `entry-point`, a `SPRYKER_*_HOST` env var, a `DOMAIN_WHITELIST` host, an install/post-deploy step), but `git merge` never flags it because the deploy files are environment-specific and weren't part of the diff. `deploy.spryker-icpplus.yml` is the one we actively maintain — so after every upmerge, **diff it against its sibling deploy files** and against what the merged code now expects.

The sibling files are the source of truth for the convention (there is usually no existing entry for a brand-new feature in *any* of them yet, so "do it like the others" means *follow the same shape*, not copy a line that doesn't exist). Good reference siblings: `deploy.spryker-scos.yml`, `deploy.spryker-sedemo15.yml`.

Run these three checks:

```bash
# (1) Feature endpoints / entry-points: what hosts+entry-points do siblings define that icpplus doesn't?
for f in deploy.spryker-scos.yml deploy.spryker-sedemo15.yml; do
  echo "=== entry-points in $f ==="; grep -nE 'entry-point:' "$f"
done
echo "=== entry-points in deploy.spryker-icpplus.yml ==="; grep -nE 'entry-point:' deploy.spryker-icpplus.yml

# (2) environment: env-var keys present in a sibling but MISSING from icpplus
extract() { awk '/^[[:space:]]*environment:/{f=1;next} f&&/^[[:space:]]{4,}[A-Z0-9_]+:/{gsub(/[: ]/,"");print} f&&/^[a-z]/{f=0}' "$1" 2>/dev/null | sort -u; }
echo "=== keys in scos NOT in icpplus ===";     comm -13 <(extract deploy.spryker-icpplus.yml) <(extract deploy.spryker-scos.yml)
echo "=== keys in sedemo15 NOT in icpplus ==="; comm -13 <(extract deploy.spryker-icpplus.yml) <(extract deploy.spryker-sedemo15.yml)

# (3) NEW host/whitelist/entry-point requirements the merged code introduced
git diff master-demo..HEAD -- config/Shared/ | grep -iE "getenv\('SPRYKER_[A-Z_]*HOST'\)|DOMAIN_WHITELIST|ENTRY_POINT"
```

**Interpreting the diff — most keys that differ are NOT gaps.** Hostnames (`SPRYKER_YVES_HOST_*`, `*_CONFIGURATOR_HOST`), `AWS_REGION`, deploy-hook paths (`SPRYKER_HOOK_*`), and features another env happens to demo (e.g. scos's water-treatment configurator) are **environment-specific by design** — do not copy them into icpplus. A key is a genuine gap only when it is **required by a feature this upmerge introduced** and icpplus lacks it. Confirm with check (3): if the merged code reads a new `SPRYKER_FOO_HOST` / adds a `DOMAIN_WHITELIST` host / registers a new Yves `entry-point`, then icpplus needs the matching `environment:` var and/or endpoint, shaped like the existing `*-configurator` entry:

```yaml
            yves:
                application: yves
                endpoints:
                    yves.eu.icp-plus.sh01.demo-spryker.com: { region: EU, services: { session: { namespace: 11 } } }
                    <feature-host>.eu.icp-plus.sh01.demo-spryker.com:
                        entry-point: <EntryPointName>   # only if the feature defines a separate Yves entry-point
```

> A feature that merely adds **routes to the existing Yves app** (e.g. PunchOut: `/punchout-cxml-setup`, `/punchout-gateway/oci/...` registered via a `RouteProviderPlugin` in `Pyz\Yves\Router`) needs **no** new endpoint or env var — it's already served by the main `yves.*` vhost. Only add a dedicated host/entry-point when the feature actually declares its own entry-point or reads its own `SPRYKER_*_HOST`. When unsure whether something needs a deploy entry, **surface checks (1)–(3) to the user and ask** rather than adding a vhost that has no DNS/cert backing it.

Also confirm any **new install step** the merge added (e.g. a `punchout` block in `config/install/destructive.yml`) is present in the recipe icpplus's deploy hooks actually run (`SPRYKER_HOOK_*` → `config/install/*.yml`), if it's meant to run there. Demo-seed steps usually live only in `destructive.yml` and need no icpplus change — verify, don't assume.

Record the outcome (changed / no change needed) in the PR body alongside the Pyz reconciliation.

### Step 6e — Audit `config/Shared/config_default.php` for dropped demo-only config blocks

**Mandatory, even on a clean merge.** This is the same silent-divergence trap as Pyz/Twig and deploy files, applied to the highest-risk config file. `config/Shared/config_default.php` carries **demo-only config blocks that exist only on `master-demo`**. A master-side commit reconciled during the merge can delete such a block with **no conflict marker** (Trap A in Step 4). The merge looks clean; the demo feature silently breaks at runtime.

> **Real incident this prevents.** The AWS QuickSight block (`AmazonQuicksightConstants::*`, added by `[CC-36926] Enable Amazon Quick Sight for demo shops`) was dropped during an upmerge while its dependency-provider wiring (`Pyz\Zed\AnalyticsGui\AnalyticsGuiDependencyProvider` → `QuicksightAnalyticsCollectionExpanderPlugin`) was left intact. Result: `GET /analytics-gui/analytics` (Back Office → Analytics) hard-crashed with `Could not find config key "AMAZON_QUICKSIGHT:AWS_REGION"`. The fix was to restore the demo-only config block.

**Check (1) — which `$config[...Constants::...]` keys did master-demo have that the merge result lost?**

```bash
keys() { grep -oE '\$config\[[A-Za-z0-9_]+Constants?::[A-Z0-9_]+\]' "$1" 2>/dev/null | sort -u; }
git show master-demo:config/Shared/config_default.php > /tmp/demo-config.php
echo "=== demo-only config KEYS dropped by the merge (investigate each!) ==="
comm -23 <(keys /tmp/demo-config.php) <(keys config/Shared/config_default.php)
```

Any line printed here is a config key that existed on `master-demo` before the merge and is **gone now**. For each one, decide:
- **Legitimately removed** — the demo feature was intentionally retired in this upmerge (rare; confirm there is a matching commit and that no code still reads the key).
- **Accidentally dropped (the dangerous case)** — restore the block verbatim from `master-demo`. Find it with:
  ```bash
  git show master-demo:config/Shared/config_default.php | grep -n "AmazonQuicksight\|<ConstantsClass>"
  ```
  and re-add both the `$config[...]` lines and the corresponding `use ...Constants;` import.

**Check (2) — `use` imports dropped from the file (catches the import half of a removed block):**

```bash
echo "=== Constants imports present on master-demo but missing now ==="
comm -23 \
  <(git show master-demo:config/Shared/config_default.php | grep -oE '^use .*Constants;' | sort -u) \
  <(grep -oE '^use .*Constants;' config/Shared/config_default.php | sort -u)
```

**Check (3) — orphaned wiring: config a block was removed but the code that READS it still runs.** A dropped config block only crashes because a plugin/dependency-provider still consumes it. After Checks (1)–(2), for any `…Constants` you decided to *leave removed*, confirm nothing still references it:

```bash
# Example for QuickSight — generalise to whatever block you dropped
grep -rn "Quicksight\|AmazonQuicksight" src/Pyz config/ 2>/dev/null
```

If wiring remains for a removed config block, you have an inconsistent state (like the QuickSight incident): either **restore the config** (preferred when the module is still in `composer.json` and the demo wants the feature) or **also remove the wiring** — but never leave config gone while the reader stays. When unsure which way to resolve, **pause and ask the user**; default to restoring the demo-only block, since the demo is meant to showcase the feature.

**Check (4) — the analytics-gui smoke check.** Because the QuickSight regression manifested only at the Back Office Analytics endpoint, add it to the Step 7 smoke test: load `http://backoffice.eu.spryker.local/analytics-gui/analytics` and confirm it returns 200 (page renders — "No Analytics permission has been granted to the current user." is the expected healthy state on a local env without provisioned AWS), not a Whoops/500.

Record the outcome (keys restored / confirmed intentionally removed / none) in the PR body alongside the Pyz and deploy audits.

### Step 6f — Upmerge the external `spryker/cypress-tests` repo (CRITICAL — merge by HASH, not cypress master tip)

The demo-shop pins `spryker/cypress-tests` to its **own `master-demo` branch** (`require-dev: "spryker/cypress-tests": "dev-master-demo"`), checked out under `tests/cypress-tests/` (composer installs it from git source). This is a **separate repository with its own `master` and `master-demo` branches** — the demo-shop upmerge does **not** touch it. You must upmerge it explicitly, and the version of the cypress `master-demo` branch **must match the cypress version the demo-shop `master` is pinned to**.

> **Why merge by HASH, never from cypress `master`'s tip — this is the whole point of the step.**
> The demo-shop's own `master` branch pins `spryker/cypress-tests` to a **specific commit reference** in its `composer.lock` (the `source.reference` hash). Cypress `master` is almost always *dozens of commits ahead* of that pinned hash (e.g. **64 commits ahead** in the 2026-06-27 case). If you merge cypress `master`'s **tip** into cypress `master-demo`, the demo cypress branch ends up running **ahead of the demo-shop version** — it would assert behavior/selectors/fixtures the shop on `master` doesn't have yet → flaky/false failures and a version-skewed demo branch. So cypress `master-demo` must be advanced to **exactly the cypress commit the demo-shop `master` pins, and no further.** That pinned hash is your merge target; cypress `master`'s tip is off-limits.

#### Step 6f-1 — Determine the target cypress commit (the hash demo-shop `master` pins)

Read the cypress reference from the demo-shop's `master` lock (NOT from `master-demo`, NOT from cypress `master`):

```bash
# Run from the demo-shop repo root.
git show master:composer.lock | python3 -c "import json,sys; d=json.load(sys.stdin); p=[x for x in d['packages']+d['packages-dev'] if x['name']=='spryker/cypress-tests'][0]; print('demo-shop master pins cypress at:', p['source']['reference'])"
```

Call that hash `TARGET_CYPRESS_HASH`. This — not cypress `origin/master` — is the upper bound for the merge.

#### Step 6f-2 — In the cypress repo, sync and validate the relationship

```bash
cd tests/cypress-tests
git fetch origin
git rev-parse master-demo                 # current demo branch tip
git log --oneline -1 <TARGET_CYPRESS_HASH>  # confirm the pinned commit exists & see what it is

# Sanity guards — surface to the user if either is surprising:
# (a) Is the target already in master-demo? If YES, there is nothing to merge — master-demo is already at/ahead of the pin.
git merge-base --is-ancestor <TARGET_CYPRESS_HASH> master-demo && echo "Target already in master-demo — no merge needed" || echo "Target NOT yet in master-demo — merge required"
# (b) How far is cypress master AHEAD of the target? Those are the commits you MUST NOT pull in.
echo "Commits on cypress master that are AHEAD of the target hash (DO NOT MERGE THESE):"
git log --oneline <TARGET_CYPRESS_HASH>..origin/master | wc -l
```

If guard (a) says "already in master-demo", record "cypress master-demo already at/ahead of demo-shop master's pin — no upmerge needed" in the PR body and skip to Step 6f-5 (still run `cy:demo` in Step 7b).

#### Step 6f-3 — Merge the target HASH into cypress `master-demo` (never `master`)

```bash
cd tests/cypress-tests
git checkout master-demo
git merge <TARGET_CYPRESS_HASH> --no-ff -m "Upmerge cypress master (@<TARGET_CYPRESS_HASH>) into master-demo for <TICKET>"
```

- **Use the hash, not `git merge master`.** `git merge master` would pull cypress master's tip and everything ahead of the pin — exactly the version skew this step exists to prevent.
- **Conflict policy**: cypress `master-demo` carries the **demo-only specs** (the `cypress/e2e/demo/` group and any demo fixtures/config). When a conflict touches a demo-only file, keep the `master-demo` (demo) side. For shared/core specs and helpers, take the incoming (master) side up to the pinned hash. When intent is unclear, **pause and ask the user**.

#### Step 6f-4 — Double-check you did NOT merge ahead of the target

After the merge, verify cypress `master-demo` contains the target but **nothing from cypress master that is newer than the target**:

```bash
cd tests/cypress-tests
# Target must now be an ancestor of master-demo:
git merge-base --is-ancestor <TARGET_CYPRESS_HASH> master-demo && echo "OK: target is in master-demo" || echo "PROBLEM: target missing"
# master-demo must NOT contain any commit that is ahead of the target on cypress master:
echo "Cypress-master commits AHEAD of the target that leaked into master-demo (MUST be empty):"
git log --oneline <TARGET_CYPRESS_HASH>..origin/master ^$(git rev-parse master-demo) 2>/dev/null | head
# Equivalent, clearer phrasing — newer-than-target master commits now reachable from master-demo:
git log --oneline master-demo --not <TARGET_CYPRESS_HASH> | grep -Ff <(git log --format=%h <TARGET_CYPRESS_HASH>..origin/master) - 2>/dev/null && echo "LEAK DETECTED — reset and redo with the hash" || echo "OK: no newer-than-target master commits in master-demo"
```

If a leak is detected, reset cypress `master-demo` to its pre-merge tip and redo Step 6f-3 with the **hash** (not `master`). Surface to the user before force-moving the branch.

#### Step 6f-5 — Run the demo cypress group, push, and re-pin the demo-shop

1. Run the demo group against the local stack (this is also Step 7b — running it here validates the just-merged cypress branch):
   ```bash
   cd tests/cypress-tests
   ENV_REPOSITORY_ID=b2b-mp ENV_IS_SSP_ENABLED=true npm run cy:demo
   ```
   All specs must pass before pushing.

2. Push cypress `master-demo` (this is a push to the **external `spryker/cypress-tests` repo**, separate from the demo-shop PR):
   ```bash
   cd tests/cypress-tests
   git push origin master-demo
   ```
   Capture the new `master-demo` tip hash — that becomes the demo-shop's new pin.

3. Re-pin the demo-shop's `composer.lock` to the new cypress `master-demo` reference so the demo shop installs the upmerged tests:
   ```bash
   # From the demo-shop repo root, on the upmerge feature branch.
   composer update spryker/cypress-tests --lock --no-install --ignore-platform-reqs
   # Verify the reference now matches the cypress master-demo tip you just pushed:
   python3 -c "import json; d=json.load(open('composer.lock')); p=[x for x in d['packages']+d['packages-dev'] if x['name']=='spryker/cypress-tests'][0]; print('demo-shop now pins cypress at:', p['version'], p['source']['reference'])"
   git add composer.lock
   git commit -m "chore(composer): re-pin spryker/cypress-tests to upmerged master-demo for <TICKET>"
   ```

Record in the PR body: the `TARGET_CYPRESS_HASH` you merged to, the new cypress `master-demo` tip you pushed, and confirmation that no newer-than-target cypress-master commits leaked in.

## Step 7 — Smoke-test Yves and Backoffice login

Invoke the `login-chrome` skill to verify the local stack still works after the merge. The smoke test scope is:

- Log into Yves at `http://yves.eu.spryker.local/DE/en/login` with `spencor.hopkin@acme.com` / `change123` (canonical B2B company user; `sonia@spryker.com` does NOT exist in this dataset — valid customers are `@acme.com`/`@ottom.de`. List them: `mariadb -h database -u spryker -psecret -D eu-docker -e "SELECT email FROM spy_customer WHERE registered IS NOT NULL LIMIT 15;"`)
- Verify the customer overview page (`/DE/en/customer/overview`) renders without errors
- Log into Backoffice at `http://backoffice.eu.spryker.local/security-gui/login` with `admin@spryker.com` / `change123`
- Verify the Backoffice dashboard renders without errors
- Load `http://backoffice.eu.spryker.local/analytics-gui/analytics` and confirm it returns 200 / renders (NOT a Whoops 500). This is the demo-only-config canary from Step 6e — a `Could not find config key "AMAZON_QUICKSIGHT:..."` here means a demo-only block was dropped during the merge.

Use `Skill(login-chrome)` (or `/login-chrome`) to drive the browser. If a login or page fails, capture the console errors and surface them — that's a real regression we should not push.

If your environment doesn't have the local Spryker stack running, ask the user whether to skip the smoke test (don't silently skip — they may want to start docker first).

## Step 7b — ALWAYS run the demo Cypress group (`cy:demo`)

The `cypress/e2e/demo/` group is the **automated, isolated coverage for demo-only features that live only on `master-demo`** (QuickSight Analytics and the other AI Commerce features). These are exactly the surfaces an upmerge most often breaks via dropped demo-only config/wiring (Step 6e), so this run is **mandatory on every upmerge — never skip it**, even on a clean merge. It supersedes the manual analytics-gui canary (it asserts the same QuickSight 200/graceful-state, plus more, deterministically).

Run it against the local stack with the demo repository id:

```bash
cd tests/cypress-tests
ENV_REPOSITORY_ID=b2b-mp ENV_IS_SSP_ENABLED=true npm run cy:demo
```

- **All specs must pass.** A failure here is a real regression — most likely a demo-only block dropped during the merge (cross-reference Step 6e Checks 1–4). Do **not** push until it's green or the user explicitly accepts the failure.
- If the local Spryker stack isn't running, ask the user whether to start docker or skip — same rule as Step 7, don't silently skip.
- If `cy:demo` doesn't exist yet (older branch), that itself is a finding: the demo group / CI step may have been lost in the merge — restore it (see the `cypress-e2e-test` skill's "demo group" section) before pushing.
- The same `cy:demo` runs in CI as its own `Run Tests (Demo)` step (`if: always()`), so a green local run predicts the CI step; a red CI demo step in Step 11 maps straight back here.

## Step 8 — Push and open the PR

```bash
git push -u origin feature/<ticket-lower>/upmerge-latest-master
```

Then create the PR with `gh`:

```bash
gh pr create \
  --base master-demo \
  --title "<TICKET>: Upmerge master to master-demo" \
  --body "$(cat <<'EOF'
## Summary
Routine upmerge bringing the latest `master` changes into `master-demo`.

JIRA: <TICKET-URL>

## Test plan
- [x] Pyz override reconciliation (incl. Twig templates shadowing changed core)
- [x] deploy.spryker-icpplus.yml audited vs sibling deploy files
- [x] config/Shared/config_default.php audited for dropped demo-only config blocks (Step 6e)
- [x] spryker/cypress-tests `master-demo` upmerged to demo-shop master's pinned cypress hash, NOT cypress master tip (Step 6f); demo-shop re-pinned
- [x] Local smoke test: Yves storefront login + customer overview
- [x] Local smoke test: Backoffice login + dashboard + analytics-gui (QuickSight canary)
- [x] Demo Cypress group green locally (`npm run cy:demo`, `ENV_REPOSITORY_ID=b2b-mp`)
- [ ] CI pipeline green (incl. the `Run Tests (Demo)` step)

## Pyz override reconciliation
<!-- List Pyz files kept / adapted / aligned from Step 6. Note "none" if the merge touched no Pyz files and no core-template overrides were affected. -->

## Deploy file audit (Step 6d)
<!-- Result of diffing deploy.spryker-icpplus.yml vs siblings + merged-code host/entry-point requirements. Note "no change needed" if the new features only add routes to existing apps and define no new SPRYKER_*_HOST / entry-point. -->

## config_default.php demo-only block audit (Step 6e)
<!-- Result of Checks (1)-(4): list any demo-only config keys/imports restored (e.g. AmazonQuicksight), or "none dropped". Confirm analytics-gui smoke check returned 200. -->

## cypress-tests repo upmerge (Step 6f)
<!-- TARGET_CYPRESS_HASH (the cypress commit demo-shop master pins) merged into cypress master-demo; new cypress master-demo tip pushed; demo-shop composer.lock re-pinned to it; confirmed NO newer-than-target cypress-master commits leaked in. Note "already at/ahead of pin — no merge needed" if applicable. -->
EOF
)"
```

Capture the PR URL from `gh pr create`'s output — you'll need it for the JIRA transition and the final report.

## Step 9 — Move the JIRA ticket to code review

```
mcp__mcp-atlassian__jira_get_issue with issue_key=<TICKET>  expand=transitions
# discover available transitions
```

The exact transition name varies by workflow. On the CC board the code-review transition is usually called **"Start CR"** (not "IN CR" — that's the *status name*, not the transition).

**MCP gotcha**: `jira_update_issue` with `fields={"status": "<status name>"}` is rejected ("Could not find transition to status X"). Instead pass the *transition name* via the `transition` key:

```
mcp__mcp-atlassian__jira_update_issue with
  issue_key=<TICKET>
  fields={"transition": "Start CR"}
```

Then re-fetch the issue to verify the status actually changed — the MCP returns "Issue updated successfully" even when the transition silently fails. If status didn't change, surface the failure to the user with the available-transitions list and ask which to apply.

If a target transition isn't in the available list (e.g. the ticket was already closed and reopened to a non-default status), list what *is* available and ask the user which to pick. Don't invent a transition.

Also add a comment to the ticket linking the PR:

```
mcp__mcp-atlassian__jira_add_comment with
  issue_key=<TICKET>
  comment="PR opened: <PR-URL>"
```

## Step 10 — Hand off to the user for review

Tell the user:

> PR opened: `<PR-URL>` and JIRA moved to IN CR. Please review the merge resolution when you have a moment. I'll check the pipeline in 2 hours.

Then schedule the pipeline check. Use `ScheduleWakeup` with the max delay of 3600s (1 hour — the runtime clamps anything higher), and pass the PR number + ticket key so the wake-up has everything it needs:

```
ScheduleWakeup with
  delaySeconds=3600
  reason="Initial pipeline check for upmerge PR <PR-NUMBER>"
  prompt="/upmerge poll PR <PR-NUMBER> ticket <TICKET>"
```

(The skill *wants* a 2-hour first check, but `ScheduleWakeup` caps at 3600s. If the pipeline is still running on first poll, the polling-mode invocation reschedules itself for another 1200s, which gets us to a similar place in practice.)

## Step 11 — Pipeline polling and auto-fix

On wake-up (or when the user comes back and asks about the pipeline), check the PR's checks:

```bash
gh pr checks <PR-NUMBER> --json name,status,conclusion,detailsUrl
```

Three outcomes:

### Still running
Schedule another wake-up at 20 minutes (1200s):

```
ScheduleWakeup with
  delaySeconds=1200
  reason="Re-check pipeline for upmerge PR <PR-NUMBER>, still running"
  prompt="/upmerge poll PR <PR-NUMBER> ticket <TICKET>"
```

### All green
Report success to the user (Step 12).

### One or more checks failed

Get the logs for the failing check:

```bash
gh run view <RUN-ID> --log-failed
# or: gh pr checks <PR-NUMBER> --watch  to find the run id
```

Then try to auto-fix common, deterministic failures:

| Failure pattern | Auto-fix |
|---|---|
| `phpcs` / code style violations | `vendor/bin/phpcbf` on flagged files, commit, push |
| `transfer:generate` related failures | `docker/sdk cli console transfer:generate`, commit any generated files |
| `propel:install` schema diff | `docker/sdk cli console propel:install`, commit, push |
| `composer.lock` out of sync warning | `composer update --lock --ignore-platform-reqs`, commit, push |
| Cache-related failures | `docker/sdk cli console cache:empty-all` is local-only — for CI, check whether the failure is genuine before pushing |

For anything else — phpstan errors, broken tests, unexpected runtime errors — **stop and surface the failure to the user with the relevant log excerpt**. Don't try to fix code logic in CI without a human in the loop.

After a fix push, schedule a 20-minute wake-up to re-check.

## Step 12 — Final report

When the pipeline is green:

- Add a JIRA comment: `Pipeline green, ready for review/merge: <PR-URL>`
- Tell the user: `Upmerge PR <PR-URL> is green and ready for review.`

Don't merge the PR — that's the user's call.

---

## Common pitfalls

- **Don't `git pull` on the wrong branch.** Use `git fetch origin master:master master-demo:master-demo` so HEAD stays put.
- **Don't silently stash.** If the working tree is dirty, ask the user.
- **Don't force-push to a PR branch without telling the user.** Normal pushes are fine; force-pushes need a heads-up.
- **Never run a bare `composer update` in Step 5.** It would bump unrelated package versions beyond what master has. The final lock must equal master's lock + demo-only entries only. If `composer update --lock` complains about a missing package, splice that package's entry in from master-demo's lock by hand (see Step 5b) — don't fall back to a bare update.
- **Don't skip the smoke test silently.** If you can't run it (stack down, no chrome), ask the user.
- **Don't treat a clean merge as "no Pyz work."** `git merge` only flags overlapping line edits. A core Twig template changing while Demo's `src/Pyz` override shadows it produces ZERO conflict markers but a stale override — Step 6 exists to catch exactly this. Always run Step 6's checks even when the merge applied cleanly.
- **Don't blindly accept master's Pyz over Demo's.** Demo intentionally diverges in some Pyz files (branded/marketplace markup). When a divergence's intent is unclear, ask the user rather than overwriting.
- **Don't trust a clean `config/Shared/config_default.php` merge.** Demo-only config blocks (QuickSight, demo S3 bucket shapes, demo toggles) can be deleted by a reconciled master-side commit with ZERO conflict markers, and the code reading them stays wired → runtime crash. Always run Step 6e's key/import diff and the analytics-gui canary, even when the merge applied cleanly. When a demo-only key vanished, default to restoring it and ask the user if intent is unclear.
- **Never `git merge master` in the cypress-tests repo (Step 6f).** Cypress `master` runs dozens of commits ahead of the hash the demo-shop `master` pins. Merge the **pinned hash** into cypress `master-demo`, then verify no newer-than-target cypress-master commits leaked in. Merging cypress master's tip puts the demo cypress branch ahead of the shop version → flaky/false test failures. The demo cypress branch version must always match what demo-shop master pins.
- **Pipeline polling is best-effort.** If the wake-up doesn't fire for any reason, the user can re-invoke the skill with `/upmerge poll PR <N> ticket <KEY>` and it'll resume from the polling step.

## Polling-only invocation

When invoked as `/upmerge poll PR <N> ticket <KEY>` (from a `ScheduleWakeup` callback or directly by the user), skip Steps 1–10 and jump straight to Step 11.
