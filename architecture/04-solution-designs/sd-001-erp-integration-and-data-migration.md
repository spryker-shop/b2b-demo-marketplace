# SD-001: ERP Integration & Data Migration

## Metadata

| Field | Value |
|-------|-------|
| **Status** | Draft |
| **Date** | 2024-07-25 |
| **Author(s)** | NORMA architecture (from TAD v47) |
| **Stakeholders** | Norma24, KPS (Talend), Spryker |

## Problem Statement

NORMA re-platforms from Shopware 5 to Spryker. Two coupled problems: (1) **ongoing ERP data sync** — Plentymarkets, via Talend, must keep the Spryker catalog (products/prices/stock) and order status in sync, and Spryker must export orders + payments back; (2) **historical migration** — 3M customers (+ encrypted passwords) from Shopware and orders/returns from the DWH. The ERP publishes **delta only** (~50 products / 10 min, no bulk feed, no SLA), so even the initial full load must be assembled from deltas.

## Goals & Requirements

### Functional Requirements
- Import product/price/stock and order-status updates from Plentymarkets via Talend over the **Glue Backend API**.
- Support **delta imports only**; build the initial/full catalog load on the delta mechanism.
- Export orders and payments to Talend every 10 minutes.
- Migrate historical customers (+ passwords) from Shopware and orders/returns from the DWH.

### Non-Functional Requirements
- **Performance:** import 60k–80k products without degrading P&S; steady state ~50 products/10 min.
- **Idempotency:** re-delivered deltas produce no duplicates.
- **Security:** authenticated Glue Backend API (API key / OAuth 2.0), EU data residency.

### Constraints
- Talend is the only ERP channel; Spryker never talks to Plentymarkets directly.
- No SLA on import; no bulk product feed.
- Apply Spryker **Data Import optimization guidelines**.

## Proposed Solution

### Overview

Talend calls the **Glue Backend API** (Data Exchange / Dynamic Entity endpoints, or thin custom resources) with delta batches. A `DeltaImportProcessor` validates and writes via **batch/CTE writers**, triggers events in bulk, and records a **delta cursor** for idempotency. **Publish & Synchronize** populates Redis/OpenSearch. Order/payment export is driven from OMS state changes on the same cadence.

### Architecture

- **See diagram:** [Delta import sequence](../diagrams/sequence/delta-import.mmd)
- **See diagram:** [ERP & migration ERD](../diagrams/erd/erp-migration-erd.puml)
- **See diagram:** [Integration overview](../diagrams/integration/integration-overview.mmd)

### Key Components

| Component | Responsibility | Technology |
|-----------|----------------|------------|
| Glue Backend API resources | Authenticated import/export surface for Talend | Spryker Glue Backend API / Data Exchange |
| DeltaImportProcessor | Validate + route delta batches; manage cursor/idempotency | Zed Business |
| Batch DataSet Writers | Bulk upsert product/price/stock with CTE | Zed Persistence |
| OrderPaymentExporter | Delta export of orders + payments | Zed Business |
| Migration importers | One-off Shopware customer/password + DWH order/return import | Console commands |

### Integration Points

- **Internal Modules:** DataImport, Product/Price/Stock storage, Publish & Synchronize, Sales/OMS, Glue Backend API.
- **External Systems:** Talend (REST → Glue Backend API); Plentymarkets behind Talend; DWH for historical orders/returns.
- **Data Flows:** delta batches inbound; event → sync messages via RabbitMQ; order/payment export outbound.

### Data Model

Core product/price/stock/customer entities are populated by the importer; two **custom control tables** support delta and migration.

- **Populated core entities:** `spy_product_abstract`, `spy_product`, `spy_price_product` (price types: standard, RRP, sale), `spy_availability` / stock, `spy_customer`.
- **`norma_import_cursor`** (custom): `id_import_cursor` (PK), `entity_type` (product|price|stock|order_status), `last_delta_token`, `last_run_at`. **Owner: ERP-integration domain, read-write.** Provides idempotency for delta batches.
- **`norma_migration_map`** (custom): `id_migration_map` (PK), `entity_type` (customer|order|return), `legacy_id` (Shopware/DWH id), `spryker_id`, `migrated_at`. **Owner: migration importers, write-once; read-only afterward.** Maps legacy identifiers to Spryker ids for idempotent re-runs and cross-references.
- **Transfers:** reuse core `ProductConcreteTransfer` / `PriceProductTransfer` / `StockProductTransfer` / `CustomerTransfer`; add a `NormaDeltaBatchTransfer` (batch metadata + cursor) and a `NormaMigrationRecordTransfer` for the migration path.

**Password re-hash strategy (spike):** Shopware 5 stores bcrypt-based hashes; Spryker's customer auth uses its own hashing. Direct hash import is only viable if the algorithms are compatible.

> **SPIKE (Norma24 + Spryker, timebox 3 days):** Determine Shopware 5 password hash format and whether it can be validated by Spryker's password hasher directly, or whether a **rehash-on-first-login** approach (store legacy hash, verify legacy on first login, then re-hash to Spryker format) is required. Confirms the `spy_customer.password` migration path. — "under clarification" in the TAD.

## Implementation Plan

### Phases
1. **Glue Backend API + delta contract** — agree auth, fields, cursor semantics with KPS/Talend.
2. **Delta import (steady state)** — processor, batch/CTE writers, idempotency, P&S validation.
3. **Full catalog build on delta** — controlled replay path for the initial load.
4. **Order/payment export** — OMS-driven delta export.
5. **Historical migration** — customers/passwords (Shopware), orders/returns (DWH, pending clarification).

### Dependencies
- **Spryker modules:** DataImport, Product, PriceProduct, Availability/Stock, Sales, Oms, Publish & Synchronize, Glue Backend API, Customer.
- **External:** Talend contract; DWH access ([ADR-004](../09-architecture-decisions/adr-004-dwh-access-mechanism.md)).
- **Prerequisites:** Glue Backend API auth agreed; ADR-003 accepted.

### Rollout Strategy & Cost
- **Approach:** phased; steady-state import first, then full build, then migration.
- **Estimated Effort:** XL (central Phase-1 build); estimate in team-days at planning.
- **Risk Mitigation:** idempotent cursor allows safe replays; keep a catalog-rebuild path.

## Trade-offs & Considerations

### Advantages
- One code path for delta + full load.
- Idempotent, retry-safe, off-request-path writes.

### Disadvantages
- Full build is slow (delta chain); migration items partly unclarified.

### Risks

| Risk | Impact | Mitigation |
|------|--------|------------|
| Import performance at 80k products | High | Data Import optimization (batch/CTE/bulk events) |
| Password migration incompatibility | Medium | Rehash-on-first-login fallback (spike) |
| DWH order/return access undefined | Medium | Gate G2; ADR-004 |

## Alternatives Considered

### Alternative 1: Bulk file import via middleware
- **Cons:** ERP publishes no bulk feed; contradicts the delta-only reality.
- **Why not chosen:** not supported by the source system.

### Alternative 2: Direct DB integration to Plentymarkets
- **Cons:** violates the Talend-only constraint; brittle coupling.
- **Why not chosen:** organizationally and architecturally disallowed.

## Open Questions
- Orders/returns migration from DWH — "under clarification".
- Data volumes for order/payment export — TBD.

## Related Documentation
- **ADRs:** [ADR-003](../09-architecture-decisions/adr-003-delta-only-data-import-via-talend-glue-backend-api.md), [ADR-004](../09-architecture-decisions/adr-004-dwh-access-mechanism.md)
- **Sections:** [03](../03-system-scope-and-context.md), [06](../06-runtime-view.md), [10](../10-quality-requirements.md)
- **Research:** shared Spryker docs research §9–11 — [Data Exchange API](https://docs.spryker.com/docs/integrations/spryker-glue-api/backend-api/data-exchange-api/data-exchange-api), [Glue Backend API](https://docs.spryker.com/docs/integrations/spryker-glue-api/backend-api/backend-api), [Data Import optimization](https://docs.spryker.com/docs/dg/dev/data-import/latest/data-import-optimization-guidelines)

---

*Corresponds to [arc42 Section 4](https://docs.arc42.org/section-4/)*
