# Building Block View

Static decomposition of the Daimler Truck B2B Parts platform into building blocks and their relationships.

## C4 Level 2: Container View

**See diagram:** [C2 Container View](diagrams/c4/c2-spryker-container.mmd)

### Main Containers

| Container | Responsibility | Daimler notes |
|-----------|----------------|---------------|
| **Yves (Storefront)** | Customer-facing shop | Headless later; delegates search to external API |
| **Back Office (Zed)** | HQ + per-market admin | Per-market data isolation via Persistence ACL + BO roles |
| **Merchant Portal (Zed)** | Dealer customer/order management | Customer approval pages, merchant-specific prices |
| **Storefront API (Glue)** | REST for storefront | Kept build-ready for headless; DTAG IAM Glue SSO not GA |
| **Backend Gateway** | Yves↔Zed RPC | — |
| **MariaDB** | Single shared relational DB | All products global; hot tables scale to 26M |
| **Redis** | Single shared KV (sessions, storage) | Global product/price storage |
| **OpenSearch** | Search engine | **NOT used for product search/filter**; CMS/utility only |
| **RabbitMQ** | Message broker | Per-region vhost; Publish & Sync |
| **Scheduler (Jenkins + PHP)** | Import, P&S workers, OMS cron | Runs the large XML import + chunked P&S |
| **AWS S3** | Import bucket | IPS XML lands here; not proxied through Spryker |

## C4 Level 3: Component View

**See diagram:** [C3 Component View](diagrams/c4/c3-component-diagram.mmd)

The Daimler-specific behaviour lives in a set of custom project modules built on top of Spryker core features. Core features are cited from [research-docs](../../.claude/.cache/architecture-prep/2026-07-23-3-tad-architectures-v2/research-docs.md) and docs.spryker.com.

### Custom / Extended Domains

| Domain / Module | Responsibility | Built on (Spryker core) | T-shirt | Size driver |
|---|---|---|---|---|
| **ExternalCatalogSearch** | PLP search/filter/category tree via external API; maps responses to shop format | [Search-migration pattern](https://docs.spryker.com/docs/pbc/all/search/latest/base-shop/tutorials-and-howtos/tutorial-integrate-any-search-engine-into-a-project) (QueryInterface swap) | **L** | Custom query models, suggestion + update-event handling, response mapping at 26M scale |
| **XmlPartsImporter** | Stream S3 XML → CTE bulk import of abstract products | [Data import optimization](https://docs.spryker.com/docs/dg/dev/data-import/latest/data-import-optimization-guidelines) | **XL** | 1.5M `<SET>` tags / 30 min; CTE writers, memory management, chunking |
| **PriceImporter** | LLP + discount-class prices, CTE bulk | Data import optimization; Price | **L** | 300k+ prices, dynamic discount-group logic |
| **ImportExecutionTracker** | Record import type/created/finished/success per market | Data Import (custom extension) | **S** | Small table + hooks; feeds HQ monitoring |
| **DealerBranchHierarchy** | Main instance + branches; branches share main-instance prices; cross-branch login | [Merchant](https://docs.spryker.com/docs/pbc/all/merchant-management/latest/marketplace/marketplace-merchant-b2b-contracts-and-contract-requests-feature-overview) + custom | **L** | Hierarchy model, price inheritance, cross-branch access rules |
| **CustomerApprovalRequest** | Registration-request → approve/reject, merchant-specific customer ID, MP overview pages | [Merchant B2B Contracts](https://docs.spryker.com/docs/pbc/all/merchant-management/latest/marketplace/marketplace-merchant-b2b-contracts-and-contract-requests-feature-overview) | **M** | New MP pages + relation lifecycle + custom fields |
| **MerchantSpecificPrice** | Dealer list price + per-customer discount groups (dynamic) | [Price](https://docs.spryker.com/docs/pbc/all/price-management/latest/base-shop/prices-feature-overview) via merchant relation, custom | **L** | Complex discount-group logic per product+customer |
| **MerchantProductRestriction** (config) | Per-customer product visibility via product lists | [Merchant Product Restrictions](https://docs.spryker.com/docs/pbc/all/merchant-management/latest/base-shop/merchant-product-restrictions-feature-overview/merchant-product-restrictions-feature-overview) | **M** | Product-list wiring + catalog install; interaction with external search |
| **MarketDataIsolation** | Per-market BO row-level isolation | [Persistence ACL](https://docs.spryker.com/docs/pbc/all/user-management/latest/marketplace/persistence-acl-feature-overview/persistence-acl-feature-overview) + [BO roles](https://docs.spryker.com/docs/pbc/all/user-management/latest/base-shop/user-and-rights-overview) | **L** | ACL configuration/scopes across many entities; Propel-only caveat |
| **HqMonitoring** | Per-market dashboard: import status, dumps, revenue thresholds, legal status | Custom BO route/module + ImportExecutionTracker | **M** | New BO page + aggregation queries |
| **MarketLegalTemplate** | Per-market T&C/warranty/sales-regulation templates, fallback to default | Merchant profile extension + config | **M** | Template fields + fallback resolution + dealer usage |
| **AvailabilityAdapter** | Live availability from TruckLog / MB LogBus DIMS | Custom client + widget | **M** | SOAP + REST adapters, fallback logic, display integration |
| **VinPartSearch** | FIN/VIN (VIS) + UK licence-plate search | Custom | **M** | VIS integration + market-specific licence-plate search |
| **OrderCommunication** (config) | Customer↔dealer order dialog | [Comments + OMS](https://docs.spryker.com/docs/pbc/all/cart-and-checkout/latest/base-shop/feature-overviews/comments-feature-overview) | **S** | Install Comments+Order Management, wire BO/storefront forms |

### Zed Application Layers (reference)

- **Presentation** — Controllers, Forms, Twig, Tables
- **Communication** — Facades, Plugins, Gateway (RPC), Console commands
- **Business** — Business models and logic
- **Persistence** — Entities, Repositories, Entity Managers, Propel schema

---

*Corresponds to [arc42 Section 5](https://docs.arc42.org/section-5/)*
