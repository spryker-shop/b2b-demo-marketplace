# Architecture Constraints

Any requirement that constrains software architects in their freedom of design decisions or the development process.

## Technical Constraints

| Constraint | Background / Motivation |
|------------|------------------------|
| **Spryker Cloud (PaaS), EU AWS region** | Managed hosting; Phase 1 = one region, one store, one country (DE). Shapes deployment ([§7](07-deployment-view.md)). |
| **ERP is Plentymarkets, integrated only via Talend** | No direct Spryker↔ERP connection; all catalog/order traffic passes the Talend ETL service bus over REST. Backend integration surface must be the Glue **Backend API**. |
| **Delta-only catalog import** | Plentymarkets/Talend deliver **delta** feeds (~50 products / 10 min); a full/initial import must be built **on top of** the delta solution, not as a separate bulk path. Drives [ADR-004](09-architecture-decisions/adr-004-delta-only-import-strategy.md). |
| **Payone as the only PSP (ACP-based)** | Payment is delivered as an App Composition Platform (ACP) payment app (iframe/redirect + ACP events bus); the OMS must integrate via the ACP/foreign payment state machine. |
| **DWH access without native Spryker support** | Spryker does not support external DB connections by default; DWH BI needs either a Spryker-Cloud **site-to-site VPN** DB connection (confirmed with Cloud team) or, alternatively, the **Data Exchange API**. See [SD-002](04-solution-designs/sd-002-dwh-integration-external-db-access.md). |
| **~24 external storefront integrations, mostly JS widgets** | Third-party JS on the storefront affects performance and can conflict; consent must be gated via Usercentrics. Cross-cutting concern ([§8](08-crosscutting-concepts.md)). |
| **Search split across two engines** | SiteSearch360 (custom, full-text) alongside OOTB Elasticsearch (category pages); no OOTB external-search-delegation feature — custom search-client integration required. |
| **Return labels are carrier-specific (DHL/DPD/GLS)** | Carrier label generation is **not** OOTB Spryker Returns; must be a custom OMS-command integration hung off the return state machine. |
| **GDPR / EU data** | German B2C retailer, 3M customers; affects data storage, processing location, and consent management. |
| **Yves storefront (Twig/PHP)** | Standard Spryker storefront, customised per Norma CI/CD. |

## Organizational Constraints

| Constraint | Background / Motivation |
|------------|------------------------|
| **Strict, short timeline** | Technical Go-Live 2024-09-09; Public Go-Live 2024-09-23. Phase 1 runs ~2024-05 → ~2024-09. |
| **TAD started ~2 months before go-live** | Risk of late-found blockers / inaccuracies; details aligned within the TAD ([§11](11-risks-and-technical-debt.md)). |
| **Integration partner KPS** | Most 3rd-party / storefront integrations are delivered by KPS; some (OMQ, Facebook, Emarsys, DWH) owned by Norma24. Requires cross-org coordination. |
| **No SLAs from customer/partner for imports** | Import cadence/perf targets are engineering-set (10-min delta), not contractually fixed. |
| **Phase 2 (~3 months from 2024-09-24)** | SSO/social login and future multi-country expansion deferred to Phase 2. |

## Conventions

| Convention | Background / Motivation |
|------------|------------------------|
| **Spryker architecture & coding standards** | Layered Yves/Zed/Glue/Client/Service; facades, plugins, P&S — maintainability and upgradability. |
| **arc42 + C4 + ADRs (docs-as-code)** | Version-controlled architecture documentation. |
| **Mermaid for diagrams** | Diagrams-as-code, renders in Git/IDE. |
| **Delta-first data pipeline** | All ERP catalog/order data flows through Talend + Glue Backend API. |

---

*Corresponds to [arc42 Section 2](https://docs.arc42.org/section-2/)*
