# Deployment View

Infrastructure and deployment topology for the Daimler Truck B2B Parts platform.

## Infrastructure Overview

Single EU region, single shared data tier, 22 stores (markets) via Dynamic Multistore.

| Component | Technology | Purpose | Daimler notes |
|-----------|-----------|---------|---------------|
| **Load Balancer** | Cloud LB | Traffic distribution | Single domain (one entry point for all markets) |
| **Application** | Spryker (Yves, Zed, Glue) | Application runtime | One codebase, DMS-on |
| **Database** | MariaDB (managed) | Primary storage | **Single shared DB, all products global** — hot tables scale to 26M |
| **Cache** | Redis (managed) | Sessions + KV storage | **Single shared Redis** |
| **Search** | OpenSearch (managed) | CMS/utility search | **NOT product search/filter** (delegated externally) |
| **Message Queue** | RabbitMQ | Publish & Sync, events | Per-region vhost |
| **Scheduler** | Jenkins + PHP | Import + P&S workers + OMS cron | Runs the large XML import |
| **Object Storage** | AWS S3 | IPS XML import bucket | Files land here; not proxied through Spryker |

## Deployment Pattern

- **Environments:** Dev → Stage → Prod (three tiers, per TAD).
- **DMS install recipe:** `-r dynamic-store`; deploy file sets `SPRYKER_DYNAMIC_STORE_MODE: true` with a region-scoped `regions:` block (research §1).
- **Store-aware operations:** Back Office & Merchant Portal operate across all stores in the region (no store context). Storefront + Glue require a store context (Store HTTP header for APIs). Console commands use `--store=<CODE>`; store-aware commands implement `StoreAwareConsole` (research §1).
- **Monitoring:** Centralized logging/metrics; HQ monitoring dashboard is an application feature (§5) on top of infra monitoring.

## Multi-Store Strategy

Daimler runs **22 stores in one region (EU)**, one per country, on Dynamic Multistore.

| Dimension | Strategy |
|---|---|
| **Regions** | 1 (EU) at Go-Live and +1Y |
| **Stores** | 1 at Go-Live → **22 (one per country)** within +1Y |
| **Domain** | Single storefront, single domain; store switching within the region (`_store` → `current_store`) |
| **Database** | **One shared DB** — all products global/shared between markets |
| **Redis / OpenSearch** | **One shared** Redis and OpenSearch; keys/indexes carry store as part of the name |
| **RabbitMQ** | Per-region vhost (`eu-*`); Jenkins jobs per-region |
| **DMS status** | **GA since release 202410.0** (research §1). The TAD's "EA → GA in Q3" concern is resolved — treat DMS as GA. |
| **Per-store separation** | BO data isolation per market via Persistence ACL + BO roles (§5, §8); legal templates per market; some functionality per store (discount-class upload, price upload auto/manual, UK licence-plate search) |
| **Import cost of scale** | Data-import speed scales with store count (research §1: ~40 stores ≈ 5× slower than ~8). Full import is **per store** → 22-store import cost is a sizing driver (§10, [SD-001](04-solution-designs/sd-001-large-scale-data-import-and-publish-sync.md)). |

## First-Pass Infrastructure Sizing

The 300k → 26M product jump on a single shared DB is the dominant sizing question — see [ADR-001](09-architecture-decisions/adr-001-single-shared-database-at-scale.md) and [SD-001](04-solution-designs/sd-001-large-scale-data-import-and-publish-sync.md).

| Concern | Go-Live (300k) | +1Y (26M) | Note |
|---|---|---|---|
| `spy_product_abstract` rows | ~300k | ~26M | 1:1 concrete → similar on `spy_product_concrete`; storage tables similar magnitude |
| `spy_price_product_store` rows | ~300k LLP × stores + discount rows | grows with product count × stores × currencies | Hot table; per-store, per-currency multiplies row count — a primary sizing risk |
| Redis storage keys | ~300k × stores | ~26M × stores | Product/price storage keys; **memory sizing must be validated at 26M** |
| OpenSearch | Small (no product index) | Small | Product search delegated → ES stays small |
| Import throughput | ~1.5M `<SET>` / 30 min target | Must hold as catalog grows | CTE + chunked P&S; **load test required (§10)** |

> **TODO:** Produce concrete DB/Redis instance sizes (CPU/RAM/IOPS/storage) for the 26M projection. Owner: infrastructure architect + Daimler platform team. Blocked on the ADR-001 load-test spike results.

## Integration-Readiness Checklist (pre-integration)

Daimler is integration-heavy (~12 external systems, multiple owning teams). Each must be confirmed ready before wiring.

| Integration | Ready when | Owner | Status |
|---|---|---|---|
| IParts (catalog) | REST contract + sample payloads + rate limits confirmed | Daimler IParts team | > **TODO** |
| VIS (FIN/VIN) | REST contract + test FIN/VINs available | Daimler VIS team | > **TODO** |
| TruckLog DIMS (SOAP) | WSDL + per-market rollout plan confirmed | Daimler TruckLog team | > **TODO** |
| MB LogBus DIMS (REST) | REST contract + fallback trigger rules defined | Daimler LogBus team | > **TODO** |
| CRISP (customer org) | REST contract + sync cadence defined | Daimler CRISP team | > **TODO** |
| IPS (XML → S3) | S3 bucket + XML schema + sample full file (~1.5M tags) delivered | Daimler IPS team | > **TODO** — needed for SD-001 load test |
| RetailNet (dealer org) | REST contract + merchant seeding mapping | Daimler RetailNet team | > **TODO** |
| Dealer Locator | Linkout URLs + return contract | Daimler Dealer Locator team | > **TODO** |
| FUSO / BPC | Linkout + basket-detail REST contract | Daimler FUSO/BPC team | > **TODO** |
| DTAG IAM (OAuth) | OIDC client credentials + claim schema; **decide storefront-only vs headless** (Glue SSO not GA) | Daimler IAM team + solution architect | > **TODO** — see §11 gate |
| External Catalog Search API | REST contract + response schema for mapping to shop format | Daimler catalog team | > **TODO** — see §11 gate |

---

*Corresponds to [arc42 Section 7](https://docs.arc42.org/section-7/)*
