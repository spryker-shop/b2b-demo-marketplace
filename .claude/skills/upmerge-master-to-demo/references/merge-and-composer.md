# Merge & composer.lock reconciliation (Steps 2–5)

Detail for the sync, branch, merge, and lockfile steps of `@.claude/skills/upmerge-master-to-demo/SKILL.md`. Emit the `[upmerge N/12] START/DONE` log lines defined there around each step. Log outcomes: Step 2 DONE = "master@<sha> master-demo@<sha> synced"; Step 3 DONE = the branch name; Step 4 DONE = the list of conflict files and which side you kept for each; Step 5 DONE = "lock = master + <N> demo-only pkgs, 0 unexpected diffs".

## Step 2 — Sync master and master-demo

Working tree must be clean (`git status --short`). If it's dirty, stop and ask the user — don't stash.

The repo's resting branch is `master-demo`, and git refuses to fetch into the checked-out branch, so pick the form by current branch (`git branch --show-current`):

```bash
# HEAD on master-demo (usual case):
git fetch origin master:master          # update master via refspec
git pull --ff-only origin master-demo   # fast-forward the checked-out branch

# HEAD on some other branch:
git fetch origin master:master master-demo:master-demo
```

Confirm both match origin (`git log --oneline -1 master` and `... master-demo`). If a fast-forward is impossible (local branch diverged), surface it — there may be unpushed work.

If the `master-demo` fast-forward moved `composer.lock`, reinstall so the local stack matches:

```bash
composer install --ignore-platform-reqs   # only if composer.lock changed
```

## Step 3 — Create the feature branch

```bash
git checkout master-demo
git checkout -b <branch>
```

Branch name:
- **Ticket provided:** `feature/<ticket-lower>/upmerge-latest-master` (e.g. `CC-38849` → `feature/cc-38849/upmerge-latest-master`).
- **No ticket:** name by today's date — `feature/upmerge-YYYY-MM-DD/master-to-master-demo`. Append `-2`, `-3`, … if the name already exists from an earlier run the same day.

If a same-named branch exists from a previous attempt, ask the user whether to reuse, recreate, or pick another suffix.

## Step 4 — Merge master in and resolve conflicts

```bash
git merge master --no-ff -m "Merge master into master-demo for upmerge <TICKET-or-date>"
```

Resolve conflicts autonomously by type (this workflow runs unattended — apply the default rule; only the hard blockers listed in `@.claude/skills/upmerge-master-to-demo/SKILL.md` stop the run):

- **`composer.lock`:** take the master-demo side as a throwaway base (`git checkout --theirs composer.lock`); Step 5 regenerates it. The merged content doesn't matter.
- **`composer.json`:** union — master's package versions plus master-demo's demo-only additions (highest versions, both sets of additions).
- **Additive source/config conflicts** (both sides added different plugins to a Dependency Provider list, etc.): keep both.
- **Real overlaps** (both sides edited the same lines with incompatible intent): default to keeping the **master-demo** side for demo-owned files (branded markup, demo config, `src/Demo`) and the **master** side for core-tracking files (`src/Pyz` generic logic), then let the Step 6 audits + smoke test + `cy:demo` catch a wrong call. Record every non-obvious resolution in the PR body so the reviewer can check it.
- **`config/Shared/config_default.php`:** highest-risk file — see the two traps in `@.claude/skills/upmerge-master-to-demo/references/demo-reconciliation.md`. Keep every demo-only block; resolve sibling entries (e.g. `FileSystemConstants` S3 buckets) to the **same** shape.

Then continue:

```bash
git add -A
git commit --no-edit
```

## Step 5 — Refresh composer.lock and install locally

**Goal:** the final lock = master's lock verbatim + only the demo-only packages that exist in master-demo's lock but not master's. Master's package set comes into master-demo with zero unrelated version bumps.

### 5a — Diff the two package sets

```bash
git show master:composer.lock | python3 -c "import json,sys; d=json.load(sys.stdin); print('\n'.join(sorted(p['name'] for p in d['packages']+d['packages-dev'])))" > /tmp/master-lock-pkgs.txt
git show master-demo:composer.lock | python3 -c "import json,sys; d=json.load(sys.stdin); print('\n'.join(sorted(p['name'] for p in d['packages']+d['packages-dev'])))" > /tmp/demo-lock-pkgs.txt

echo "--- on master but not master-demo (expect empty) ---"; comm -23 /tmp/master-lock-pkgs.txt /tmp/demo-lock-pkgs.txt
echo "--- demo-only packages to layer in ---";               comm -13 /tmp/master-lock-pkgs.txt /tmp/demo-lock-pkgs.txt
```

A non-empty "on master but not master-demo" list means master added a package master-demo's lock hasn't caught up on — verify the merged `composer.json` requires it. Sanity-check the merged `composer.json` = master's requires + demo-only ones:

```bash
git show master:composer.json | python3 -c "import json,sys; d=json.load(sys.stdin); print('\n'.join(sorted(list(d.get('require',{})) + list(d.get('require-dev',{})))))" > /tmp/master-json.txt
python3 -c "import json; d=json.load(open('composer.json')); print('\n'.join(sorted(list(d.get('require',{})) + list(d.get('require-dev',{})))))" > /tmp/merged-json.txt
diff /tmp/master-json.txt /tmp/merged-json.txt
```

### 5b — Reconstruct the lock (master's lock + demo-only entries)

```bash
git show master:composer.lock > /tmp/master.lock
git show master-demo:composer.lock > /tmp/demo.lock

python3 <<'PYEOF'
import json
master = json.load(open('/tmp/master.lock'))
demo = json.load(open('/tmp/demo.lock'))

demo_only = ['spryker-eco/amazon-quicksight']  # <-- the list from 5a

for name in demo_only:
    pkg = next((p for p in demo['packages'] if p['name'] == name), None)
    dev = pkg is None
    if dev:
        pkg = next(p for p in demo.get('packages-dev', []) if p['name'] == name)
    target = master['packages-dev' if dev else 'packages']
    for i, p in enumerate(target):
        if p['name'] > name:
            target.insert(i, pkg); break
    else:
        target.append(pkg)

# ensure_ascii=False keeps non-ASCII bytes (e.g. "Kévin Dunglas") as composer writes them,
# so the diff against master stays clean instead of exploding into \uXXXX noise.
with open('composer.lock', 'w', encoding='utf-8') as f:
    json.dump(master, f, indent=4, ensure_ascii=False); f.write('\n')
print('reconstructed composer.lock')
PYEOF
```

### 5c — Refresh content-hash, restore plugin-api-version, verify

`composer update --lock --no-install` only updates lock metadata (hash + any new required entries); it doesn't touch versions already satisfying constraints.

```bash
composer update --lock --no-install --ignore-platform-reqs
composer install --ignore-platform-reqs
```

Composer rewrites `plugin-api-version` from the local binary; restore master's value:

```bash
python3 <<'PYEOF'
import json
current = json.load(open('composer.lock')); master = json.load(open('/tmp/master.lock'))
if current.get('plugin-api-version') != master.get('plugin-api-version'):
    current['plugin-api-version'] = master['plugin-api-version']
    with open('composer.lock', 'w') as f: json.dump(current, f, indent=4); f.write('\n')
    print('restored plugin-api-version')
else:
    print('plugin-api-version already matches master')
PYEOF
```

Verify zero version diffs vs master apart from the demo-only adds:

```bash
python3 <<'PYEOF'
import json
master = json.load(open('/tmp/master.lock')); current = json.load(open('composer.lock'))
def v(l): return {p['name']: p['version'] for p in l['packages'] + l.get('packages-dev', [])}
m, c = v(master), v(current)
diffs = [(n, m.get(n), c.get(n)) for n in sorted(set(m)|set(c)) if m.get(n) != c.get(n)]
print(f'{len(diffs)} version diffs vs master:')
for n, mv, cv in diffs[:30]: print(f'  {n}: master={mv} current={cv}')
PYEOF
```

Expect only `master=None current=<version>` rows for the demo-only packages you added. Any other diff → stop and reconcile before committing.

### 5d — Commit

```bash
git add composer.lock
git commit -m "chore(composer): refresh lockfile after upmerge"
```

Skip the commit if `git status` shows no changes.

## Guardrails

- Regenerate the lock with the 5b reconstruction; never run a bare `composer update` (it bumps unrelated packages).
- `composer update --lock` alone won't add entries for packages missing from the current lock — that's why 5b splices demo-only entries in first.
- Base on master's lock, not master-demo's (master-demo may hold older versions).
