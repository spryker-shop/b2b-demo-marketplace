# Deployment View

Technical infrastructure used to execute and run the NORMA system.

## Infrastructure Overview

**Hosting model:** Spryker Cloud (PaaS) on **AWS, EU region**. Phase 1 is deliberately minimal: **one region, one store, one country (DE)**, one codebase, one dedicated database.

### Production Environment (Phase 1)

| Component | Technology | Purpose |
|-----------|-----------|---------|
| **Region** | AWS EU (single) | Data residency (EU / GDPR), one region for all Phase-1 traffic |
| **Application** | Spryker Cloud PaaS (Yves, Zed, Glue) | Storefront, back office, APIs |
| **Database** | MariaDB/MySQL (managed) — **single DB** | Products, prices, stock, orders, customers, returns |
| **Cache / KV** | Redis (managed) — **single KV** | Sessions + P&S storefront storage |
| **Search** | Elasticsearch/OpenSearch (managed) — **single index cluster** | OOTB category/catalog search |
| **Message Queue** | RabbitMQ | P&S + OMS async + Payone ACP events |
| **Scheduler** | Jenkins | Delta import (10 min), order export (10 min), OMS timeout/condition, P&S workers |
| **Object Storage** | AWS S3 | Import/export files, feed artifacts |
| **DWH connectivity** | Site-to-site VPN DB connection (opt. Data Exchange API) | BI data access — Cloud-network item ([SD-002](04-solution-designs/sd-002-dwh-integration-external-db-access.md)) |
| **Payone ACP app** | ACP (external, composed) | Payment processing |

### Environments

| Environment | Phase 1 | +1Y | +…Y |
|-------------|---------|-----|-----|
| Production | 1 | 1 | TBD |
| Staging | 1 | 1 | TBD |

> **TODO:** confirm pre-prod / additional staging needs for the multi-country expansion.

### Deployment Pattern

- **Managed PaaS** — Spryker Cloud handles orchestration, scaling and backups.
- **CI/CD** — standard Spryker deploy pipeline.
- **Monitoring** — Cloud-provided logging/metrics; storefront performance monitoring is a focus given the 3rd-party JS load ([§8](08-crosscutting-concepts.md), [§11](11-risks-and-technical-debt.md)).

## Multi-Store Setup

**Phase 1:** one region (EU), one store, one country (**DE**), one locale (**de_DE**), one currency (**EUR**), and a **single shared DB / Redis / Elasticsearch**. One codebase.

**Future (+…Y, ~2+ years, un-scheduled):** expansion to **4 countries** (DE, AT, FR, CZ), with a **separate store per country**, 3 languages, 2 currencies (EUR, CZK).

| Dimension | Go-Live | +1Y | +…Y |
|-----------|---------|-----|-----|
| Regions | 1 (EU) | 1 (EU) | 1 (EU) |
| Countries | 1 (DE) | 1 (DE) | 4 (DE, AT, FR, CZ) |
| Stores | 1 | 1 | 1 per country |
| Locales | 1 (de_DE) | 1 | 3 |
| Currencies | 1 (EUR) | 1 | 2 (EUR, CZK) |
| Per-store infra | Single DB/ES/KV | Same | **TBD** |
| Shared vs separate data | N/A | N/A | **TBD** |
| BO data access per store | N/A | N/A | **TBD** |

**Strategy considerations:**
- The per-country store split can be realised with Spryker **Dynamic Multi-Store** ([docs](https://docs.spryker.com/docs/pbc/all/dynamic-multistore/latest/base-shop/dynamic-multistore-feature-overview)) — multiple stores within one region managed from a single Back Office, store selected via the `Store` header. DMS GA status carries no explicit label in the docs — **confirm against the target release's release notes**.
- Same-region vs per-region topology, shared-vs-separate DB/ES/KV, and shared-vs-separate data/functionality are all **TBD** and must be finalised well before the expansion. See [SD-002](04-solution-designs/sd-002-dwh-integration-external-db-access.md) neighbours and a future multi-store SD.

> **TODO (architecture, pre-expansion):** decide multi-store topology (region strategy, shared vs separate infra, shared data across stores) before committing to the 4-country rollout. No timing/setup details exist yet in the TAD.

---

*Corresponds to [arc42 Section 7](https://docs.arc42.org/section-7/)*
