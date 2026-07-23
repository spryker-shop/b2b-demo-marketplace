# Deployment View

Technical infrastructure for the Daimler Truck B2B Parts platform.

## Hosting Model

Spryker Cloud PaaS (managed), single region (EU), single codebase, single domain serving all markets.

| Component | Technology | Purpose |
|-----------|-----------|---------|
| **Load Balancer** | Cloud LB | Traffic distribution; single domain, store selected per request. |
| **Application (Yves/Zed/Glue/MP)** | Managed app runtime | Storefront, Back Office, Merchant Portal, Backend API. |
| **Database** | Single relational DB (MariaDB/MySQL) | **One DB for all 22 stores**; all products global/shared. |
| **Cache / KV** | Single Redis | Storage & session; Publish & Sync target. |
| **Search** | Elasticsearch/OpenSearch | Present but **not used for product search** (delegated to IParts). |
| **Message Queue** | RabbitMQ | Publish & Sync + multi-queue import throughput. |
| **Object Storage** | S3 | Landing zone for IPS full XML parts/price files. |
| **ETL Middleware** | Import middleware | Splits large XML before ingestion. |

## Environments

Dev → Stage → Prod (per TAD).

> **TODO:** Environment sizing (pod counts, DB instance class, worker scaling per queue) and CI/CD topology are not specified in the TAD. Owner: Spryker Cloud / platform team.

## Multi-Store Setup

The platform runs on **Dynamic Multi-Store (DMS)** — [feature overview](https://docs.spryker.com/docs/pbc/all/dynamic-multistore/latest/base-shop/dynamic-multistore-feature-overview).

| Aspect | Decision |
|--------|----------|
| **Regions** | 1 (EU) at Go-Live and GL+1Y. |
| **Stores** | 1 country at Go-Live → **22 dynamic stores** (1 per country) at GL+1Y, all in the one region. |
| **Domain** | Single storefront under **one domain**; store selected per request (DMS `Store` header / switcher). |
| **Database** | **Single shared DB** — all products global and shared between markets. |
| **Redis / ES** | Single shared Redis; single ES (not used for product search). |
| **Codebase** | One codebase for all markets. |
| **Shared vs separate** | Products & catalog **shared globally**; market-specific settings, legal templates, dealers, and BO data are **isolated per store** (see Market Data Isolation, [ADR-002](09-architecture-decisions/adr-002-persistence-acl-for-market-isolation.md)). |
| **Search** | Search Service usage limited; **PLP/search building delegated to IParts** ([ADR-004](09-architecture-decisions/adr-004-external-catalog-search-delegation.md)). |

**Maturity note:** The TAD flags DMS as **Early Access** at approval time (committed GA in Q3). The public docs model DMS as multiple stores in one region managed from one Back Office and served via the `Store` header, with no explicit EA/GA label — confirm GA status against the target release notes before relying on it for 100%-scope Go-Live. This is a tracked yellow risk ([Section 11](11-risks-and-technical-debt.md)).

---

*Corresponds to [arc42 Section 7](https://docs.arc42.org/section-7/)*
