# Building Block View

Static decomposition of the NORMA system into building blocks and their relationships.

## C4 Level 2: Container View

**See diagram:** [C2 Container View](diagrams/c4/c2-spryker-container.mmd)

### Main Containers

| Container | Responsibility |
|-----------|----------------|
| **Yves (Storefront)** | Customer-facing shop (Twig/PHP), customised per Norma CI/CD; hosts the custom return portal and all ~24 storefront JS-widget integrations (consent-gated). |
| **Zed (Backend)** | Business logic, Back Office, OMS/state machines, data import, P&S publishers; back-office user management. |
| **Glue Backend API** | System-to-system API surface for Talend (catalog import, order export) and optionally the Data Exchange API for DWH. |
| **Glue Storefront API** | REST for storefront/headless operations where needed. |
| **MariaDB/MySQL** | Primary persistent store (products, prices, stock, orders, customers, returns). |
| **Redis (Key-Value)** | Sessions + P&S key-value storefront storage. Single KV in Phase 1. |
| **Elasticsearch/OpenSearch** | OOTB catalog/category search index (SiteSearch360 handles full-text externally). |
| **RabbitMQ** | Async event/queue infrastructure for Publish & Sync and OMS side-effects (incl. Payone ACP events). |
| **Scheduler (Jenkins)** | Runs delta imports (every 10 min), order export, OMS `check-timeout`/`check-condition`, P&S workers. |
| **File Storage (S3)** | Import/export files, feed artifacts. |
| **Payone ACP App** | External payment app composed into the platform via ACP. |
| **Talend (ETL service bus)** | External middleware bridging Plentymarkets and DWH. |
| **SiteSearch360** | External full-text search service. |

## C4 Level 3: Component View

**See diagram:** [C3 Component View](diagrams/c4/c3-component-diagram.mmd)

### Custom / project modules and domains

| Domain / module | Layer | Responsibility |
|-----------------|-------|----------------|
| **Catalog Import (delta)** | Zed (Glue Backend API + DataImport) | Receive delta product/price/stock/category feeds from Talend; batch-apply; feed P&S. Full import built on delta ([ADR-004](09-architecture-decisions/adr-004-delta-only-import-strategy.md)). |
| **Order Export** | Zed (Glue Backend API) | Export orders + payments to Talend → Plentymarkets every 10 min. |
| **Payone Payment (ACP)** | Zed OMS + Payment PBC | ACP payment app integration; foreign/ACP payment state machine; ACP events bus consumer ([ADR-002](09-architecture-decisions/adr-002-payone-acp-payment.md)). |
| **Order Splitting** | Zed (checkout/OMS) | Split shipments by warehouse allocation at checkout. |
| **Returns + Carrier Labels** | Zed OMS + custom command | Custom return portal (Yves) + DHL/DPD/GLS label generation as OMS commands on return transitions. |
| **SiteSearch360 Search Client** | Client/Yves | Custom search-client delegating full-text to SiteSearch360; ES for category pages ([ADR-003](09-architecture-decisions/adr-003-sitesearch360-plus-ootb-search.md)). |
| **Feed Export (Feed Dynamix)** | Zed/Glue | API-based product export for feed generation. |
| **CRM / Address / Carrier connectors** | Client/Zed | REST connectors to Emarsys, Melissa, carriers. |
| **DWH Export** | Glue Backend API / Cloud VPN | Data Exchange API endpoints and/or DB access for BI. |
| **Storefront Integration widgets** | Yves | JS-widget wiring (GTM/GA, Clarity, Kameleoon, etc.), all gated by Usercentrics consent. |
| **Shopware Migration** | one-off scripts | Customer + password migration from Shopware; historical orders/returns from DWH (all under clarification, [SD-001](04-solution-designs/sd-001-shopware5-data-migration.md)). |

### Zed Application Layers (standard Spryker)

- **Presentation** — Back Office controllers, forms, UI
- **Communication** — Facades, Plugins, Gateway (RPC), console commands
- **Business** — Business models, OMS commands/conditions
- **Persistence** — Entities, Repositories, Entity Managers, Propel schema

---

*Corresponds to [arc42 Section 5](https://docs.arc42.org/section-5/)*
