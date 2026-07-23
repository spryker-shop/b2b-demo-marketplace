# ADR-003: Delta-only data import via Talend over the Glue Backend API

## Status

**Accepted** (2024-07-25)

## Context

Plentymarkets (via Talend) publishes only **delta** changes (~50 products every 10 minutes); there is no bulk product feed, and there is **no SLA** on import. NORMA must both keep the catalog in sync and perform the **initial/full catalog load**. Talend is the mandated ETL; the Spryker-side integration surface is the **Glue Backend API** (Data Exchange / Dynamic Entity endpoints or custom resources) (research §9–11).

## Decision

We will implement **delta-only import** where Talend calls the **Glue Backend API** with authenticated delta batches, and **build the full/initial catalog load on the same delta mechanism** (a controlled sequence/replay of deltas). Writes use batch/CTE bulk operations and bulk event triggering per the Data Import optimization guidelines; a stored **delta cursor** provides idempotency. Orders and payments are exported back through the Glue Backend API on the same 10-minute cadence.

## Consequences

### Positive
- One code path for both steady-state sync and full load — less to build and test.
- Idempotent, retry-safe imports; writes kept off the request path.
- Uses the standard, authenticated Glue Backend API surface.

### Negative
- Full-catalog build is a long chain of deltas rather than a fast bulk import; needs a robust replay/catalog-rebuild path.
- No SLA from the ERP side means freshness guarantees are best-effort.

---

*Corresponds to [arc42 Section 9](https://docs.arc42.org/section-9/)*
