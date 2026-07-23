# ADR-004: Delta-only import strategy (full import built on delta)

## Status

**Accepted** (2024-07-25)

## Context

Plentymarkets/Talend deliver catalog data as **delta** feeds — approximately **50 changed products every 10 minutes** for a ~60k-product catalog. The customer/partner provides **no SLAs** for imports. NORMA still needs an initial/full load and periodic full reconciliation, but maintaining a separate bulk-import path in parallel with the delta path adds complexity and duplication on a very tight timeline.

## Decision

We will implement **delta-only import types** as the single ingestion mechanism, and build the **initial/full import on top of the delta solution** (a "full" run is a large sequence of delta applications), rather than a distinct bulk pipeline. Imports use Spryker **Data Import** with the [optimization guidelines](https://docs.spryker.com/docs/dg/dev/data-import/latest/data-import-optimization-guidelines): batch/PDO over ORM, pre-gathered lookups (no per-row queries), and multi-queue tuned **Publish & Sync**.

## Consequences

### Positive
- One code path to build, test and operate — critical given the compressed timeline.
- Imports are idempotent and re-runnable; delta re-application is safe.
- P&S tuning (multi-queue, chunk size) directly benefits both delta and full runs.

### Negative
- A full/initial load via delta mechanics may be **slower or less efficient** than a purpose-built bulk import — a known technical-debt item ([§11](../11-risks-and-technical-debt.md)).
- The 60k-product / 2,000-category scale must be benchmarked before go-live to confirm the delta-based full import fits operational windows.

---

*Corresponds to [arc42 Section 9](https://docs.arc42.org/section-9/)*
