# ADR-003: Direct S3 XML import — split on middleware, CTE-based ingest, not proxied through Spryker

## Status

**Accepted** (2024-06-04)

## Context

Prices & parts-master arrive as a **FULL XML import per store** (~1,500,000 `<SET>#productdata` tags) to be processed in ~30 min (not strict), with the catalog growing toward 26 mio part numbers. Routing files through Spryker as a proxy, and importing row-by-row via Propel ORM, would exhaust memory and blow the time budget. Spryker's documented guidance favours batch/PDO over ORM, pre-gathered lookups, multi-queue Publish & Sync and tuned chunk sizes ([data-import optimization](https://docs.spryker.com/docs/dg/dev/data-import/latest/data-import-optimization-guidelines) · [architecture performance](https://docs.spryker.com/docs/dg/dev/guidelines/performance-guidelines/architecture-performance-guidelines)).

## Decision

Land IPS files directly on **S3** (Spryker is not a file proxy). **Split** the large XML on **Spryker Middleware** into per-entity/per-store chunks. Ingest with **CTE-based / PDO bulk** writes (no per-row ORM). Track each import run (type, `created_at`, `finished_at`, success) for HQ monitoring. Propagate via **multiple RabbitMQ publisher queues** with tuned chunk sizes; export only Storefront-relevant product data to Redis ([multi-queue publish](https://docs.spryker.com/docs/dg/dev/integrate-and-configure/integrate-multi-queue-publish-structure)).

## Consequences

### Positive
- Meets the volume/latency budget and scales toward 26 mio parts.
- Isolating the import behind middleware lets the source change (IPS → two new services) without touching Spryker import.

### Negative
- **"Split on middleware / CTE-based import" is a project pattern, not verbatim Spryker doc guidance** — must be benchmarked per release (documented equivalents: batch/PDO, pre-gather, multi-queue, chunk tuning).
- Bespoke bulk-load logic diverges from OOTB DataImport and adds an upgrade/maintenance burden ([Section 11](../11-risks-and-technical-debt.md)).
- ES is intentionally not populated for product search (delegated to IParts — [ADR-004](adr-004-external-catalog-search-delegation.md)).

---

*Corresponds to [arc42 Section 9](https://docs.arc42.org/section-9/)*
