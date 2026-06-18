#!/usr/bin/env python3
"""Verify the Cypress shard manifest matches the installed cypress-tests.

Runs from repo root. Compares the spec set in
`.github/workflows/cypress/shards.json` against the actual files in
`tests/cypress-tests/cypress/e2e/` (after composer install, excluding
`smoke/` and `ssp*` to match the CI filter).

Exits 1 on drift with a diff that points the developer at the repack
script in cypress-tests. Run from the `cypress-shards` gate job in
`_cypress.yml`: a non-zero exit fails that job red and the dependent
`cypress` matrix is skipped (no run-cancel needed).
"""

from __future__ import annotations

import glob
import json
import os
import sys
from pathlib import Path

CYPRESS_E2E_DIR = "tests/cypress-tests"
MANIFEST_PATH = ".github/workflows/cypress/shards.json"


def list_actual_specs() -> set[str]:
    specs: set[str] = set()
    cwd = Path.cwd()
    os.chdir(CYPRESS_E2E_DIR)
    try:
        for p in glob.iglob("cypress/e2e/**/*.cy.ts", recursive=True):
            if "/smoke/" in p or os.path.basename(p).startswith("ssp"):
                continue
            specs.add(p)
    finally:
        os.chdir(cwd)
    return specs


def load_manifest_specs() -> tuple[set[str], int]:
    with open(MANIFEST_PATH) as f:
        manifest = json.load(f)
    return {s for specs in manifest.values() for s in specs}, len(manifest)


def main() -> int:
    actual = list_actual_specs()
    manifest_specs, shard_count = load_manifest_specs()

    missing = actual - manifest_specs
    stale = manifest_specs - actual

    if not missing and not stale:
        print(f"OK: {len(actual)} specs across {shard_count} shards.")
        return 0

    print("Cypress shards manifest is out of sync with cypress-tests.")
    for s in sorted(missing):
        print(f"  + {s} (new spec, not in manifest)")
    for s in sorted(stale):
        print(f"  - {s} (in manifest, no longer in cypress-tests)")
    print()
    print("To fix: from tests/cypress-tests/, run a full local cypress run")
    print("(npm run cy:ci), copy the results table, then from repo root:")
    print("  pbpaste | npm --prefix tests/cypress-tests run cy:repack")
    print("Commit the updated .github/workflows/cypress/shards.json")
    return 1


if __name__ == "__main__":
    sys.exit(main())
