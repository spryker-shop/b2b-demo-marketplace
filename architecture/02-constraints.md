# Architecture Constraints

Requirements that constrain design and process freedom for the Daimler Truck B2B Parts platform.

## Technical Constraints

| Constraint | Background / Motivation |
|------------|------------------------|
| **Single shared DB / Redis / ES** for all 22 stores | TAD-mandated ("Single DB/Redis/ES per store setup"). All products are global/shared between markets. Central scaling constraint — see [ADR-001](09-architecture-decisions/adr-001-single-shared-database-at-scale.md). |
| **Catalog scales 300k → 26M products in +1Y** | Legacy status-quo volume; drives hot-table sizing (`spy_product_abstract`, `spy_price_product_store`) and import/P&S design. |
| **Product search/filter delegated to external Daimler catalog API** | Spryker Elasticsearch is not used for product search/filtering (TAD). Documented as a search-migration *pattern*, not a packaged connector — see [ADR-002](09-architecture-decisions/adr-002-external-catalog-search-delegation.md) and [research §16](https://docs.spryker.com/docs/pbc/all/search/latest/base-shop/tutorials-and-howtos/tutorial-integrate-any-search-engine-into-a-project). |
| **Full XML import via S3, ~1.5M `<SET>` tags, ~30 min** | IPS delivers prices + parts master as XML over SFTP → S3. Files must not be proxied through Spryker; CTE-based bulk import + chunked P&S required ([Data import optimization](https://docs.spryker.com/docs/dg/dev/data-import/latest/data-import-optimization-guidelines)). |
| **Yves storefront now, headless later** | Frontend is Yves for Go-Live; Storefront API kept build-ready for the long-term headless move. |
| **Dynamic Multistore (DMS)** for 22 stores in one region | TAD stated "DMS Early Access → GA in Q3". **Now resolved: DMS is GA since release 202410.0** ([research §1](https://docs.spryker.com/docs/pbc/all/dynamic-multistore/latest/base-shop/dynamic-multistore-feature-overview)). Treat as GA. |
| **DTAG IAM (Empower ID) via OAuth** | Federated Authentication (OAuth2/OIDC) for storefront/BO. **Glue/API-level OAuth SSO is NOT GA** ("coming later") — headless SSO needs the CIAM-provider path or a project solution ([research §15](https://docs.spryker.com/docs/pbc/all/oauth/latest/federated-authentication)). |
| **No PSP — Invoice payment only** | No payment gateway integration in scope. |
| **~12 external Daimler systems** | IParts, VIS, TruckLog (DIMS-SOAP), MB LogBus DIMS (REST), CRISP, IPS (XML/SFTP), RetailNet, Dealer Locator, FUSO/BPC, DTAG IAM. Availability and correctness depend on Daimler-side teams. |
| **19 languages, 12 currencies, 1 region (EU)** | Localisation footprint across 22 country stores. |
| **GDPR / EU data residency** | EU market presence; affects hosting region and PII handling (customer/dealer data). |

## Organizational Constraints

| Constraint | Background / Motivation |
|------------|------------------------|
| **Hard Go-Live October 2024, 100% scope** | No MVP; full functionality expected. Launch-blocking unknowns become decision gates (§11). |
| **Scrum, 3-week sprints** | Delivery cadence; scaling spikes must fit sprint boundaries. |
| **Historical data migration** | Customer/dealer relations, rebate classes, open orders migrate from the legacy system. |
| **Staging environments: Dev, Stage, Prod** | Three-tier environment topology (§7, §10). |
| **Multiple external integration owners** | Each Daimler system has a separate owning team → integration-readiness checklist needed (§7). |

## Conventions

| Convention | Background / Motivation |
|------------|------------------------|
| **Spryker module architecture** | Custom code follows Spryker layer/naming conventions (Zed/Yves/Glue/Client/Service/Shared); project code under `Pyz`/project namespace. |
| **arc42 + C4** | Architecture documentation standard (this folder). |
| **Bridges deprecated** | Dependency Providers wire dependencies directly via the locator, not via Bridges (project rule). |
| **Transfer Objects for facade I/O** | All facade methods use Transfers or native types; no leaking Propel entities beyond persistence. |
| **PHPStan / static analysis** | Project static-analysis gate before merge. |
| **GitFlow / PR review** | Architecture and code changes reviewed via pull requests. |

---

*Corresponds to [arc42 Section 2](https://docs.arc42.org/section-2/)*
