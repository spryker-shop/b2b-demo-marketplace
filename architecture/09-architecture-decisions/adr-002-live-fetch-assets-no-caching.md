# ADR-002: Live-fetch assets from FMAT with no caching (POC)

## Status

**Accepted** (2026-07-23) — for the POC.

## Context

A fleet customer's vehicles (assets) are owned by **FMAT**, not by Spryker. The asset list must be shown accurately, and a customer may own many vehicles (volume unknown — Open Q #6). Options are to (a) fetch assets live from FMAT on each request, or (b) cache/sync assets into Spryker. Caching adds a sync mechanism, invalidation logic, and a data-ownership question that the POC does not need to answer to prove feasibility.

## Decision

We will **fetch assets live from the FMAT API on each request, with no caching in Spryker**, using a Client-layer HTTP adapter with pagination. Asset caching is explicitly deferred to a Future workstream.

## Consequences

### Positive
- Always-fresh asset data; no stale-cache or invalidation bugs.
- Simplest possible implementation for the POC; no new persistence or sync jobs.
- Keeps FMAT as the clear source of truth.

### Negative
- Every asset page load depends on FMAT latency and availability (coupling; a listed risk).
- No graceful fallback for the POC — FMAT downtime surfaces as an exception.
- Does not scale to large fleets or high traffic; must be revisited before production.

> **Revisit trigger:** production planning, large per-customer fleets, or measured FMAT latency problems → introduce caching/sync (Future).

---

*Corresponds to [arc42 Section 9](https://docs.arc42.org/section-9/)*
