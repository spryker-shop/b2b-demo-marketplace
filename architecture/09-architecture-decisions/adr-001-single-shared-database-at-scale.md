# ADR-001: Single shared Database / Redis / Elasticsearch for the global catalog at scale

## Status

**Proposed** (2026-07-23)

> Launch-blocking scaling gate (G1 in §11). Stays **Proposed** until the load-test spike closes.

## Context

The Daimler TAD mandates a **single shared DB, Redis, and Elasticsearch** setup — all products are global and shared between the 22 markets. The catalog is projected to grow from **~300,000 products at Go-Live to up to 26,000,000 within one year**. A 1:1 abstract:concrete ratio means hot tables (`spy_product_abstract`, `spy_product_concrete`, `spy_price_product_store` and their `*_storage` counterparts) reach tens of millions of rows; `spy_price_product_store` is multiplied further by store and currency. Redis product/price storage keys scale as products × stores. Product search/filtering is delegated externally, so Elasticsearch stays small — but persistence, Publish & Sync, and the full XML import (~1.5M `<SET>` tags per store, ~30 min target) must all remain viable at this volume. Go-Live is a hard October-2024 deadline.

## Decision

We will **keep a single shared Database, Redis, and Elasticsearch** for the global catalog as mandated, and make it viable at scale by:
- Importing via **CTE-based bulk** writes and **chunked Publish & Sync** with manual event batching, files streamed from S3 (never proxied through Spryker) — per [Data import optimization guidelines](https://docs.spryker.com/docs/dg/dev/data-import/latest/data-import-optimization-guidelines).
- **Keeping product search/filter off Elasticsearch** ([ADR-002](adr-002-external-catalog-search-delegation.md)) so ES load stays low.
- **Not persisting availability** (read-through from TruckLog / MB LogBus DIMS) so no 26M-row stock table is required.
- Running a **load-test spike** (Gate G1) on a prod-like Stage with a 26M-scale catalog to validate hot-table behaviour, Redis memory, and import/P&S throughput **before** the decision is accepted. See [SD-001](../04-solution-designs/sd-001-large-scale-data-import-and-publish-sync.md).

## Consequences

### Positive
- Matches the TAD constraint; one codebase/DB/region keeps operations simple and products truly global.
- CTE + chunked P&S is the documented Spryker path for millions of rows.
- Delegated search + read-through availability remove two of the largest would-be hot tables.

### Negative
- A single DB is a single scaling ceiling; 26M rows on hot tables is unvalidated until the spike runs — hence Proposed.
- Redis memory for product/price storage at 26M × stores may be large and needs explicit sizing (§7 TODO).
- Import cost scales with store count (research §1: ~40 stores ≈ 5× slower than ~8), so 22-store full imports are a recurring load.
- If the spike shows the single DB cannot hold, mitigation (partitioning, read replicas, archiving inactive parts) is a significant late change against a hard deadline.

---

*Corresponds to [arc42 Section 9](https://docs.arc42.org/section-9/)*
