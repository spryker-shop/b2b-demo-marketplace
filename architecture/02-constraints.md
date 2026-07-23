# Architecture Constraints

Requirements that constrain design and development decisions for the NORMA re-platforming.

## Technical Constraints

| Constraint | Background / Motivation |
|------------|-------------------------|
| **ERP is Plentymarkets, integrated only via Talend ETL** | All product/price/stock/order/payment exchange goes Spryker ↔ Talend ↔ Plentymarkets. Spryker never talks to Plentymarkets directly. |
| **Delta import types only; full/initial import built on delta** | The ERP publishes ~50 changed products every 10 min; there is no bulk feed. The initial catalog must be assembled from delta batches. Drives the import design in [SD-001](04-solution-designs/sd-001-erp-integration-and-data-migration.md). |
| **Integration surface is the Glue Backend API** | Talend calls Spryker's **Glue Backend API** (Data Exchange / Dynamic Entity endpoints or custom resources) for import and export. See research §9–10. |
| **Payone via ACP is the TAD's plan, but ACP is being sunset** | Spryker's App Composition Platform is being sunset ("no additional activations or changes are allowed"). New builds must use the **direct Payone module**. See [ADR-001](09-architecture-decisions/adr-001-payone-direct-module-over-acp.md) and research §13. |
| **Full-text search delegated to SiteSearch360** | Spryker has **no packaged SiteSearch360 connector** — it is a custom integration following the "integrate any search engine" pattern. Category PLPs stay on OOTB Elasticsearch. See [ADR-002](09-architecture-decisions/adr-002-external-full-text-search-sitesearch360.md) and research §16. |
| **DWH access needs a non-default mechanism** | Spryker does not support external DB connections by default. DWH access is either a secure **site2site VPN** DB connection (confirmed feasible with Spryker Cloud) or the **Data Exchange API**. See [ADR-004](09-architecture-decisions/adr-004-dwh-access-mechanism.md). |
| **~15 storefront third-party JS integrations** | Tag managers, analytics, consent, A/B testing, recommendations, forms, tracking widgets. Constrains storefront performance budget and requires consent-gated loading. |
| **"Drop Shipment" must be built on Marketplace primitives** | No Spryker "Drop Shipment" feature exists; use Product Offer + Marketplace Shipment. See research §17. |
| **Hosting in a single EU AWS region** | One region, one store, one database, one codebase for Phase 1. Data residency in the EU. |
| **GDPR compliance** | German B2C with 3M customer records; consent management (Usercentrics) and address handling (Melissa) must be compliant. |

## Organizational Constraints

| Constraint | Background / Motivation |
|------------|-------------------------|
| **Hard, fixed Go-Live deadlines** | Technical Go-Live 2024-09-09, Public Go-Live 2024-09-23. Non-negotiable; drives the decision-gate approach in [Section 11](11-risks-and-technical-debt.md). |
| **TAD started ~2 months before Go-Live** | Compressed discovery window increases the risk of late-discovered blockers; several items remain "under clarification". |
| **Integration partner KPS + business owner Norma24** | Multiple external systems are owned/operated by KPS and Norma24; integration readiness depends on third-party availability (see [Section 7](07-deployment-view.md) readiness checklist). |
| **One staging environment (Phase 1)** | Limits parallel test streams; test data and load-test scheduling must be coordinated. |
| **External approval (Felix Jungermann) still open** | Architecture is internally approved but external sign-off is pending at TAD time. |

## Conventions

| Convention | Background / Motivation |
|------------|-------------------------|
| **Spryker module architecture** | Layered Zed (Presentation → Communication → Business → Persistence), Yves, Glue, Client, Service, Shared; custom code under the project namespace. |
| **Transfer objects for all facade boundaries** | Facade methods take/return transfers or native types; no leaking of Propel entities. |
| **Publish & Synchronize for read models** | Storefront reads come from Redis/Elasticsearch populated via P&S, never directly from the DB. |
| **arc42 + C4** | Architecture documented as version-controlled arc42 sections with C4 and sequence diagrams. |
| **ADRs for decisions, SDs for exploration** | Launch-blocking-but-open decisions stay `Proposed` until their gate closes. |
| **Data Import optimization guidelines** | Batch writes, CTE bulk inserts, event bulk-triggering during import (see research §11). |

---

*Corresponds to [arc42 Section 2](https://docs.arc42.org/section-2/)*
