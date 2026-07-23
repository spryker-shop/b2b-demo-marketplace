# SD-001: Large-scale data import & Publish & Sync

## Metadata

| Field | Value |
|-------|-------|
| **Status** | Draft |
| **Date** | 2026-07-23 |
| **Author(s)** | Architecture writer (from Daimler TAD) |
| **Stakeholders** | Solution architect, infra architect, Daimler IPS team |

## Problem Statement

IPS delivers prices + parts-master data as **XML files via SFTP** (landing on S3). A **full import per store** is ~1,500,000 `<SET>#productdata</SET>` tags and should complete in **~30 minutes (not strict)**. The catalog grows from **300k → 26M products in +1Y** on a **single shared DB/Redis/ES**, and the import runs across **22 stores** (import cost scales with store count). This is the throughput-critical, launch-blocking path (Gate G1) — it must not proxy large files through Spryker, must use bulk techniques, and must keep the storefront responsive.

## Goals & Requirements

### Functional Requirements
- Import abstract products (minimal: name + price) and prices (LLP + discount classes) from IPS XML on S3.
- Track each import execution (type, `created_at`, `finished_at`, success) for HQ monitoring.
- Propagate to Redis storage via Publish & Sync; **do not** index products in Spryker ES (search delegated — [ADR-002](../09-architecture-decisions/adr-002-external-catalog-search-delegation.md)).

### Non-Functional Requirements
- **Throughput:** ~1.5M `<SET>` tags in ~30 min at 300k; validated toward 26M (Gate G1).
- **Scalability:** viable on a single shared DB to 26M rows on hot tables ([ADR-001](../09-architecture-decisions/adr-001-single-shared-database-at-scale.md)).
- **Availability:** import must not block or degrade the storefront.

### Constraints
- Single shared DB/Redis/ES; all products global.
- Files land on S3 via SFTP; **not proxied through Spryker**.
- Per-store import; 22 stores multiply cost.

## Proposed Solution

### Overview

A custom `XmlPartsImporter` streams the XML from S3 in chunks and performs **CTE-based bulk** writes, with events **manually batched** so Publish & Sync runs in chunks rather than per row. An `ImportExecutionTracker` records execution metadata. Prices import via a companion `PriceImporter`. All per research §11 ([Data import optimization guidelines](https://docs.spryker.com/docs/dg/dev/data-import/latest/data-import-optimization-guidelines)).

### Architecture

**See diagram:** [Large XML Import + P&S](../diagrams/sequence/large-xml-import-publish-sync.mmd)

### Key Components

| Component | Responsibility | Technology |
|-----------|----------------|------------|
| `XmlPartsImporter` (Zed console) | Stream S3 XML, CTE bulk import abstract products | PHP, `DataSetWriterInterface`, PDO/CTE |
| `PriceImporter` (Zed console) | LLP + discount-class prices, CTE bulk | PHP, CTE |
| `ImportExecutionTracker` | Persist import run metadata | Propel entity + hooks |
| Publish & Sync (tuned) | Chunked, bulk, event-batched propagation | Spryker P&S + `EventBehaviorConfig` |

### Integration Points

- **Internal:** DataImport module (`DataSetWriterInterface`), Event module (`EventBehaviorConfig::disableEvent/triggerBulk/enableEvent`), Publish & Sync storage publishers (CTE-based).
- **External:** IPS → S3 (SFTP/XML). No direct IPS↔Spryker call; S3 is the boundary.
- **Data flow:** read S3 → CTE bulk write DB → batched events → RabbitMQ → chunked P&S workers → Redis.

### Data Model

**Touched core tables (bulk-written via CTE):**

| Table | Role | Key columns |
|-------|------|-------------|
| `spy_product_abstract` | Abstract product (name) | `id_product_abstract` (PK), `sku`, `fk_tax_set` |
| `spy_product_abstract_localized_attributes` | Localized name per locale | PK, `fk_product_abstract`, `fk_locale`, `name` |
| `spy_product` | Concrete (1:1) | `id_product` (PK), `fk_product_abstract`, `sku` |
| `spy_price_product` | Price link | `id_price_product` (PK), `fk_product_abstract`/`fk_product`, `fk_price_type` |
| `spy_price_product_store` | Store/currency price (hot) | `id_price_product_store` (PK), `fk_price_product`, `fk_store`, `fk_currency`, `gross_amount`, `net_amount` |
| `spy_product_abstract_storage` | P&S storage | PK, `fk_product_abstract`, `data`, `store`, `locale` |
| `spy_price_product_store` → `spy_price_product_abstract_storage` | P&S price storage | PK, product ref, `data` |

**Custom staging / tracking tables (Propel `pyz_*`):**

```
pyz_import_execution
  id_import_execution      INTEGER  PK
  type                     VARCHAR      -- 'parts' | 'price' | 'org-dump'
  fk_store                 INTEGER      -- market
  file_reference           VARCHAR      -- S3 key
  created_at               TIMESTAMP
  finished_at              TIMESTAMP    (nullable)
  is_success               BOOLEAN
  message                  TEXT         (nullable, error detail)
  row_count                INTEGER      (nullable)

pyz_parts_import_staging   -- optional landing table for chunked transform before CTE upsert
  id_parts_import_staging  INTEGER  PK
  fk_import_execution      INTEGER  FK -> pyz_import_execution
  sku                      VARCHAR
  raw_payload              JSON/TEXT
  processed                BOOLEAN
```

**Transfers:** `ImportExecutionTransfer` (tracking), `PartsImportChunkTransfer` (in-memory chunk), reuse core `ProductAbstractTransfer` / `PriceProductTransfer` for writing. **Ownership:** the Import domain **owns write** on `pyz_*` and the import-time bulk writes to core catalog/price tables; HQ monitoring has **read-only** access to `pyz_import_execution`.

## Implementation Plan

### Phases
1. **Phase 1** — `ImportExecutionTracker` + `XmlPartsImporter` (CTE, chunked, S3 streaming) for a single store at 300k.
2. **Phase 2** — `PriceImporter` (LLP + discount classes) + tuned chunked P&S with event batching.
3. **Phase 3** — Multi-store (22) import; load test to 26M (Gate G1); sizing.

### Dependencies
- **Spryker modules:** DataImport (`^1.32.0` for `--progress-bar`), Event, PublishAndSynchronize/Synchronization, Product, PriceProduct, Store.
- **External:** IPS full sample file (~1.5M tags) on S3; S3 bucket + credentials.
- **Prerequisites:** DMS store config; ADR-001 load-test environment.

### Rollout Strategy & Cost
- **Approach:** phased; import runs off-peak; feature-flag new importers per store.
- **Estimated effort:** XL (import) + L (price) — see §5 t-shirt sizing.
- **Risk mitigation:** keep legacy import path available until throughput proven; rollback = re-run prior successful import.

## Trade-offs & Considerations

### Advantages
- Documented Spryker bulk path; storefront unaffected; ES stays small.

### Disadvantages
- CTE/raw SQL is DB-specific (MariaDB ≥ 10.2) and harder to maintain than ORM.
- Per-store × 22 imports are a recurring, heavy job.

### Risks

| Risk | Impact | Mitigation |
|------|--------|------------|
| 26M import misses window | Stale catalog | Load test early (G1); tune chunk size, parallelism |
| Redis memory blows up at 26M × stores | Storage failures | Size Redis in G1; consider selective storage |
| Discount-group price explosion in `spy_price_product_store` | Hot-table bloat | Model discounts dynamically at calc time, not as stored rows where possible |

## Alternatives Considered

### Alternative 1: ORM-based import (`findOneOrCreate()->save()`)
- **Pros:** Simple, standard.
- **Cons:** 2+ queries/row → will not finish at 1.5M/26M (research §11).
- **Why not chosen:** Throughput.

### Alternative 2: Proxy XML through Spryker
- **Pros:** Fewer moving parts.
- **Cons:** Large files through app tier; memory/latency.
- **Why not chosen:** Best-practice is S3 + stream, not proxy (research §11).

## Open Questions
- Split large files on middleware vs importer-side chunking? (best-practice, not a quoted rule — §11 TODO)
- Can discount groups stay dynamic to avoid stored price-row explosion?

> **Named spike (Gate G1):** Load-test import + P&S with a 26M-scale catalog on prod-like Stage; measure hot-table + Redis behaviour and import time. Owner: solution architect + infra. Timebox: before Go-Live.

## Related Documentation
- **ADRs:** [ADR-001](../09-architecture-decisions/adr-001-single-shared-database-at-scale.md), [ADR-002](../09-architecture-decisions/adr-002-external-catalog-search-delegation.md)
- **External:** [Data import optimization](https://docs.spryker.com/docs/dg/dev/data-import/latest/data-import-optimization-guidelines), [P&S advanced](https://docs.spryker.com/docs/dg/dev/backend-development/data-manipulation/data-publishing/publish-and-synchronize-advanced-use-cases)

---

*Corresponds to [arc42 Section 4](https://docs.arc42.org/section-4/)*
