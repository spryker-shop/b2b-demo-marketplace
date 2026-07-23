# Architecture Constraints

Requirements that constrain the architects' freedom of design for the Daimler Truck B2B Parts platform.

## Technical Constraints

| Constraint | Background / Motivation (why) |
|------------|-------------------------------|
| **Yves storefront framework** | Chosen frontend for now; long-term headless plan exists but is out of scope for Go-Live. |
| **External catalog/search delegated to IParts** | Catalog structure, category tree and PLP filtering are built by Daimler's 3rd-party IParts service; Spryker Search (Elasticsearch/OpenSearch) is **not** used for product search/filtering — abstract products carry minimal data (name + price) only to be listable. |
| **Single DB / Redis / Elasticsearch across all 22 stores** | TAD mandates one relational DB, all products global and shared; ES not used for product search. Constrains multi-store to shared-infrastructure DMS. |
| **Dynamic Multi-Store (DMS)** | Required to run 22 markets as stores in one region under one domain. DMS maturity is a flagged risk (TAD notes Early Access; committed GA in Q3). |
| **Invoice-only payment (no PSP)** | Business decision — no payment gateway integration; only native Invoice. |
| **Full XML import per store via S3, ~1.5M `<SET>#productdata` tags, ≤30 min** | Volume + latency budget forces import optimization (split on middleware, CTE-based import, P&S tuning) and forbids proxying files through Spryker. |
| **10 external systems, mixed protocols** | REST (IParts, VIS, CRISP, RetailNet, MB LogBus DIMS), SOAP (TruckLog DIMS), SFTP/XML (IPS), OAuth (DTAG IAM), Linkout (Dealer Locator, FUSO/BPC). Each connector must respect the partner's protocol; no single integration style. |
| **DTAG IAM (Empower ID) as identity provider** | Customer authentication is federated via OAuth against Daimler's IAM — no local credential store for federated users. |
| **Single storefront under one domain** | DMS requirement — 22 markets served from one storefront/domain, store selected per request. |

## Organizational Constraints

| Constraint | Background / Motivation (why) |
|------------|-------------------------------|
| **Go-Live October 2024, 100% scope, no MVP** | Full replacement of the legacy system; nothing can be deferred to a later release. Drives risk-heavy, parallel delivery. |
| **Scrum, 3-week sprints** | Delivery cadence for the project team. |
| **Approved WITH RISKS (Yellow)** | Approval is conditional on managing the flagged risks (DMS maturity, import scale, hierarchy/pricing complexity). |
| **Daimler owns all 10 external systems** | Integration readiness depends on Daimler-side teams; connector availability (esp. DIMS availability services, IPS replacement services) is on Daimler's timeline. |

## Conventions

| Convention | Background / Motivation (why) |
|------------|-------------------------------|
| **Spryker architecture & coding standards** | Custom modules build on named Spryker features to stay upgradeable. |
| **arc42 + C4** | Architecture documentation standard for this record. |
| **Staging environments: Dev, Stage, Prod** | Standard promotion path for changes before production. |

> **TODO:** Team size, PHPStan level, branching workflow and Spryker product/release version are not stated in the TAD. Owners: project lead / Spryker delivery.

---

*Corresponds to [arc42 Section 2](https://docs.arc42.org/section-2/)*
