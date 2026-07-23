# Building Block View

Static decomposition of the Daimler Truck B2B Parts platform into containers and custom components.

## C4 Level 2: Container View

**See diagram:** [C2 Container View](diagrams/c4/c2-spryker-container.mmd)

### Main Containers

| Container | Responsibility |
|-----------|----------------|
| **Yves (Storefront)** | Customer-facing shop; PLP/PDP using IParts-built catalog, dealer-scoped prices, VIS/DIMS lookups, dealer selection & registration. |
| **Zed (Back Office)** | HQ + Market administration; per-market monitoring; market-isolated data access. |
| **Merchant Portal** | Dealer (merchant) self-service — customer-pool approval, prices/discount groups, orders & shipping. |
| **Glue Backend API** | System-to-system endpoints for Daimler back-ends and the import pipeline. |
| **MariaDB/MySQL (single DB)** | One relational database; all products global/shared across the 22 stores. |
| **Redis (single, DMS-scoped keys)** | Key-value store / storage & session for Publish & Sync. |
| **Elasticsearch/OpenSearch** | Present but **NOT used for product search/filtering** (delegated to IParts); minimal use only. |
| **RabbitMQ** | Async messaging for Publish & Sync and event queues; multi-queue for import throughput. |
| **ETL Middleware (import)** | Splits the large XML file(s) before Spryker ingests them ([ADR-003](09-architecture-decisions/adr-003-direct-s3-xml-import-not-proxied.md)). |
| **S3 bucket** | Landing zone for IPS full XML parts/price files. |

### Project-specific external adapters

| Adapter | Talks to | Purpose |
|---------|----------|---------|
| **External Catalog/Search adapter** | IParts (REST) | Catalog structure, category tree, PLP filtering ([ADR-004](09-architecture-decisions/adr-004-external-catalog-search-delegation.md)). |
| **Vehicle adapter** | VIS (REST) | FIN/VIN validation, vehicle data card, part-vehicle match. |
| **Availability adapter** | TruckLog DIMS (SOAP), MB LogBus DIMS (REST) | Live spare-parts availability. |
| **Customer org adapter** | CRISP (REST) | Customer organisation data. |
| **Dealer org adapter** | RetailNet (REST) | Dealer (merchant) organisation data. |
| **Identity adapter** | DTAG IAM (OAuth) | Federated customer authentication. |

## C4 Level 3: Component View (custom modules / domains)

**See diagram:** [C3 Component View](diagrams/c4/c3-component-diagram.mmd)

The custom domains layered on top of Spryker's standard PBCs:

| Custom domain / module | Built on (Spryker feature) | Responsibility |
|------------------------|----------------------------|----------------|
| **Dealer Merchant model** | Marketplace Merchant + Merchant Portal | Dealers as merchants; order & fulfilment ownership. |
| **Customer↔Dealer Registration** | Merchant Relationship — Merchant B2B Contracts / Contract Requests | Customer joins a dealer pool by request; dealer approves/rejects, assigns merchant-specific customer ID. |
| **Dealer Product Visibility** | Merchant Product Restrictions (Product Lists + Search) | Show only the active dealer's products/prices to a customer. |
| **Dealer Pricing & Discount Groups** | Merchant Custom Prices + custom discount-group logic | Per-product / per-customer discount groups or dealer list price ([SD-001](04-solution-designs/sd-001-dealer-prices-discount-groups.md)). |
| **Dealer Hierarchy (Branches)** | BO User roles & groups + custom hierarchy | Main instance + subordinate branches; shared prices; cross-branch login; branch roles ([SD-002](04-solution-designs/sd-002-dealer-hierarchy-branches.md)). |
| **Market Data Isolation** | Persistence ACL pattern / custom controller restriction | Each market BO user sees only its data; HQ sees all. |
| **HQ Monitoring** | Custom BO route/module + data-import tracking | Per-market import status, dump status, revenue alerts, legal-info/user-agreement status. |
| **Dealer↔Customer Comments** | Comments module + OMS comments | Threaded dialog in order detail (customer) / order history (dealer). |
| **Large XML Import** | Data Import (CTE-based) + P&S optimization | Split → bulk-load ~1.5M-tag full XML per store ([ADR-003](09-architecture-decisions/adr-003-direct-s3-xml-import-not-proxied.md)). |
| **External Catalog/Search** | Custom search client (delegation) | Catalog & PLP delegated to IParts ([ADR-004](09-architecture-decisions/adr-004-external-catalog-search-delegation.md)). |
| **Market Legal Templates** | Merchant profile extension + store default template | Per-market legal templates (T&C, warranty, sales regs, liability) with fallback to store default. |
| **License-plate search (UK)** | Custom, store-specific | UK-only license-plate lookup. |

---

*Corresponds to [arc42 Section 5](https://docs.arc42.org/section-5/)*
