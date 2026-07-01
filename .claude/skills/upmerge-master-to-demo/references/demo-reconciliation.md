# Demo reconciliation audits (Step 6a–6e, 6g)

Detail for `@.claude/skills/upmerge-master-to-demo/SKILL.md` Step 6. The merge brings changes into `master-demo` that Demo's project code must stay in sync with, but `git merge` flags none of them — the override/config/wiring files and the changed upstream files live at different paths (no line overlap) or the loss is a same-file line edit inside a list. These audits make the divergence explicit. **Run all of them even on a clean merge.**

Log each sub-step's outcome with its own DONE line (see the logging convention in SKILL.md): `[upmerge 6a/12] DONE  Pyz files: 3 changed, 2 kept, 1 reverted to demo`; `[upmerge 6b/12] DONE  Twig overrides: 120 flagged → 0 stale after file-diff`; `[upmerge 6d/12] DONE  deploy audit: no gaps`; `[upmerge 6e/12] DONE  config audit: none dropped` (or "restored AmazonQuicksight block"); `[upmerge 6g/12] DONE  provider audit: none dropped` (or "restored QuicksightUserExpanderPlugin into src/Demo").

## Namespace ground rule — demo-only code belongs in `src/Demo`

`config_default.php` sets `PROJECT_NAMESPACES = ['Demo', 'Pyz']`, so `Demo` shadows `Pyz`. `Pyz` tracks `master` (master edits `Pyz` files, and those edits arrive through this merge); `Demo` does not. Therefore:

- Demo-only wiring (a demo-feature plugin in a Dependency Provider, a demo console command, a demo config-provider) placed in a **`src/Pyz` provider is fragile** — one master-side edit to that file, or a conflict resolved toward master, silently drops the demo entry.
- The durable home is a **`src/Demo` provider that `extends` the `Pyz` one** and adds the demo entries there, so the merge never touches it. Example: `@src/Demo/Zed/User/UserDependencyProvider.php` and `@src/Demo/Zed/Console/ConsoleDependencyProvider.php` extend their `Pyz` counterparts to register the QuickSight expander/console; `@src/Demo/Yves/QuoteRequestPage/Theme/` shows the same shadow-in-Demo pattern for Yves themes.

When you find demo-only entries sitting in a `src/Pyz` provider (Step 6g), relocate them to `src/Demo` as part of the fix.

## Step 6a — Pyz files the merge changed

```bash
echo "--- Pyz Twig changed by the merge ---";  git diff --name-status master-demo...HEAD -- 'src/Pyz/**/*.twig'
echo "--- other Pyz files changed ---";         git diff --name-status master-demo...HEAD -- 'src/Pyz' ':(exclude)src/Pyz/**/*.twig'
```

For each changed Pyz file: **keep** it if it's a generic core/Pyz improvement Demo should track; **revert to the master-demo side** if Demo intentionally diverges there (demo-only copy, marketplace markup, branded content). Default autonomously by that rule and note it in the PR body.

## Step 6b — Core (vendor) Twig changes shadowed by a Pyz/Demo override

The case the merge can never surface: `master` bumped a vendor package whose core Twig template changed, and Demo has an override of that exact template — the override keeps the old markup silently.

`vendor/` is git-ignored but is on disk after Step 5's `composer install`, so diff at file level. The module-granularity pre-filter below **over-reports massively** (flags every override in any module whose package version changed — ~120 flags where the true stale count is 0–2). It is a pre-filter only; always narrow to changed-FILE granularity before acting.

Two narrowing paths:
1. **File diff vs installed vendor (default, no network):** diff each flagged override against the freshly-installed core template; byte-identical vendor template ⇒ not stale. A `dev-master → 202606.0` bump with an unchanged lock `source.reference` is a label-only change — skip.
2. **GitHub compare API (fastest):** compare each changed package's old→new `source.reference` for the exact changed `.twig` set, intersect with overrides by `{Layer}/{Module}/<rest>` path-signature. Delegate to a subagent to keep context clean.

Pre-filter (run after Step 5; covers both `src/Pyz` and `src/Demo`):

```bash
python3 - <<'PYEOF'
import json, re, subprocess
before = json.load(open('/tmp/demo.lock')); after = json.load(open('composer.lock'))
def vers(l): return {p['name']: p['version'] for p in l['packages'] + l.get('packages-dev', [])}
vb, va = vers(before), vers(after)
changed = {n: (vb.get(n), va.get(n)) for n in sorted(set(vb)|set(va)) if vb.get(n) != va.get(n)}
def module_of(pkg): return ''.join(w.capitalize() for w in pkg.split('/', 1)[1].split('-'))
changed_modules = {module_of(p): p for p in changed}
def module_from(path):
    m = re.search(r'/(?:Zed|Yves|Glue|Client|Service)/([^/]+)/', path); return m.group(1) if m else None
twigs = [p for p in subprocess.run(['git','ls-files','src/Pyz','src/Demo'],capture_output=True,text=True).stdout.splitlines() if p.endswith('.twig')]
hits = [(module_from(p), changed_modules[module_from(p)], p) for p in twigs if module_from(p) in changed_modules]
if not hits:
    print(f"{len(changed)} packages changed version; no override in a changed module. Nothing to align.")
else:
    print("Overrides whose CORE package changed — narrow to file level, then align:")
    for mod, pkg, p in hits:
        o, n = changed[pkg]; print(f"  [{pkg} {o}->{n}]  {p}")
PYEOF
```

For a genuinely stale override, port the upstream change into it:

```bash
diff <override-path> vendor/<vendor-package>/src/<Org>/<Layer>/<Module>/<...>.twig
```

Update the override to carry the relevant upstream markup/logic. For branded/demo-specific markup keep the demo intent; port only the structural upstream change.

## Step 6c — Record what you reconciled

List the Pyz/Demo files you kept, adapted, or aligned in the PR body so the reviewer sees the reconciliation was deliberate.

## Step 6d — Audit `deploy.spryker-icpplus.yml` against sibling deploy files

Same silent-divergence trap, one level up: the merge can introduce a feature needing a deploy-level entry (a Yves `entry-point`, a `SPRYKER_*_HOST`, a `DOMAIN_WHITELIST` host, an install step), and `git merge` never flags it. `deploy.spryker-icpplus.yml` is the actively-maintained file; siblings (`deploy.spryker-scos.yml`, `deploy.spryker-sedemo15.yml`) are the convention reference. Both `deploy.spryker-icp.yml` and `deploy.spryker-icpplus.yml` exist and either may carry a conflict — resolve whichever conflicted and still run this audit against icpplus.

```bash
# (1) entry-points siblings define that icpplus doesn't
for f in deploy.spryker-scos.yml deploy.spryker-sedemo15.yml; do echo "=== $f ==="; grep -nE 'entry-point:' "$f"; done
echo "=== icpplus ==="; grep -nE 'entry-point:' deploy.spryker-icpplus.yml

# (2) env keys in a sibling but MISSING from icpplus
extract() { awk '/^[[:space:]]*environment:/{f=1;next} f&&/^[[:space:]]{4,}[A-Z0-9_]+:/{gsub(/[: ]/,"");print} f&&/^[a-z]/{f=0}' "$1" 2>/dev/null | sort -u; }
echo "=== scos keys not in icpplus ===";     comm -13 <(extract deploy.spryker-icpplus.yml) <(extract deploy.spryker-scos.yml)
echo "=== sedemo15 keys not in icpplus ==="; comm -13 <(extract deploy.spryker-icpplus.yml) <(extract deploy.spryker-sedemo15.yml)

# (3) NEW host/whitelist/entry-point requirements the merged code introduced
git diff master-demo..HEAD -- config/Shared/ | grep -iE "getenv\('SPRYKER_[A-Z_]*HOST'\)|DOMAIN_WHITELIST|ENTRY_POINT"
```

**Most differing keys are NOT gaps.** Hostnames, `AWS_REGION`, deploy-hook paths (`SPRYKER_HOOK_*`), and features another env happens to demo are environment-specific by design — don't copy them. A key is a genuine gap only when check (3) shows the merged code introduced a requirement icpplus lacks (reads a new `SPRYKER_FOO_HOST`, adds a `DOMAIN_WHITELIST` host, registers a new Yves entry-point). A feature that only adds routes to the existing Yves app needs nothing. When check (3) confirms a gap, add the matching entry shaped like the existing `*-configurator` endpoint. Also confirm any new install step (e.g. a `destructive.yml` block) belongs in the recipe icpplus's deploy hooks run. Record the outcome in the PR body.

## Step 6e — Audit `config/Shared/config_default.php` for dropped demo-only config blocks

The config half of the config↔wiring pair. A master-side commit reconciled during the merge can delete a demo-only block (e.g. the AWS QuickSight `AmazonQuicksightConstants::*` block) with **no conflict marker**, while the code reading it stays wired → runtime crash (`Could not find config key "AMAZON_QUICKSIGHT:AWS_REGION"` at Back Office → Analytics).

```bash
keys() { grep -oE '\$config\[[A-Za-z0-9_]+Constants?::[A-Z0-9_]+\]' "$1" 2>/dev/null | sort -u; }
git show master-demo:config/Shared/config_default.php > /tmp/demo-config.php
echo "=== demo-only config KEYS dropped by the merge ==="; comm -23 <(keys /tmp/demo-config.php) <(keys config/Shared/config_default.php)

echo "=== Constants imports on master-demo but missing now ==="
comm -23 <(git show master-demo:config/Shared/config_default.php | grep -oE '^use .*Constants;' | sort -u) \
         <(grep -oE '^use .*Constants;' config/Shared/config_default.php | sort -u)
```

For each dropped key: if a matching commit intentionally retired the feature and nothing reads it, leave it removed; otherwise **restore the block and its `use ...Constants;` import verbatim** from `master-demo` (find it with `git show master-demo:config/Shared/config_default.php | grep -n <ConstantsClass>`). Default autonomously to restoring, since the demo showcases the feature. Then confirm no orphaned reader remains for anything left removed:

```bash
grep -rn "<ConstantsClass-or-feature>" src/Pyz src/Demo config/ 2>/dev/null
```

## Step 6g — Audit Dependency Providers for dropped demo-only plugin/console registrations

The wiring half. A demo-only entry can be dropped from a provider's `return [ … ]` list — via a master-side edit to that `Pyz` provider, or a conflict resolved toward master — with **no conflict marker**, because it's a same-file line edit, not an add/add. The feature then silently does nothing even though its config survived (this is what killed QuickSight in CC-39501: `Pyz\Zed\User` expander/post-update lists emptied to `[]`, `QuicksightUserSyncSaveConsole` dropped from `Pyz\Zed\Console`).

```bash
# Providers touched by the merge:
git diff --name-only master-demo...HEAD -- 'src/Pyz/**/*DependencyProvider.php' 'src/Demo/**/*DependencyProvider.php'

# For each, registrations present on master-demo but GONE now:
for f in $(git diff --name-only master-demo...HEAD -- 'src/Pyz/**/*DependencyProvider.php' 'src/Demo/**/*DependencyProvider.php'); do
  echo "=== $f — dropped registrations ==="
  comm -23 <(git show "master-demo:$f" 2>/dev/null | grep -oE 'new [A-Za-z0-9_]+(Plugin|Console)\(\)' | sort -u) \
           <(grep -oE 'new [A-Za-z0-9_]+(Plugin|Console)\(\)' "$f" 2>/dev/null | sort -u)
done
```

For each dropped registration: if intentionally retired, leave it; otherwise **restore it into a `src/Demo` provider extending the `Pyz` one** (per the namespace ground rule above), not back into `Pyz`. Default autonomously to restoring.

## The invariant

For every demo feature, its config block (Step 6e) AND its provider wiring (Step 6g) are **both present or both absent**. A half state in either direction is the bug:
- config present, wiring dropped → feature silently dead (6g).
- config dropped, wiring present → runtime crash (6e).

Record all audit outcomes (kept / restored / confirmed removed / none) in the PR body.
