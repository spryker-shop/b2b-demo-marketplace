# Introduction and Goals

Describes the relevant requirements and driving forces for the NORMA B2C food-discounter re-platforming onto Spryker.

## Document Status

| Field | Value |
|-------|-------|
| Source | NORMA Target Architecture Definition (TAD), v47 |
| Status | Approved internally (Green) |
| Driver | Yevhen Romanov |
| Internal Approver | Andriy Tkachenko (approved 2024-07-25) |
| External Approver | Felix Jungermann (external approval: TBD) |
| Handover session | 2024-08-15 (Spryker + Norma/KPS) |
| Release phases | Phase 1 (Go-Live), Phase 2 (post Go-Live) |
| Technical Go-Live | 2024-09-09 |
| Public Go-Live | 2024-09-23 |

## Requirements Overview

NORMA is a food discounter operating 1,300+ stores across Germany, France, Czech Republic and Austria (brands *Norma* and *Norma-Rodi*), covering food and non-food business. This architecture covers the **re-platforming of the existing non-food B2C online shop from legacy Shopware 5 to Spryker**, driven by:

- **End of support for Shopware 5** — the legacy platform must be replaced.
- **A stable base for future enhancements** on current technology.
- **A long-term vision to expand into a Marketplace** — Phase 1 uses only limited Marketplace primitives.

The business model is **B2C with (limited) Marketplace capabilities**. Phase 1 is a single store (Germany, EUR, `de_DE`); a multi-country expansion (DE/AT/FR/CZ) is a 2+ year vision (see [Section 7](07-deployment-view.md)).

### Functional Scope

| Scope Item | Description | Release Phase |
|------------|-------------|---------------|
| Configuration & setup | Single-store setup; Yves storefront customized per NORMA CI/CD; currency EUR; locale `de_DE` | Phase 1 |
| ERP integration | Plentymarkets ERP via Talend ETL: products, prices, stock, order updates, payments | Phase 1 |
| Data migration (delta) | Import product + related data (prices/stock/categories) via **Glue Backend API** through Talend from ERP. **Delta import types only**; the initial/full import is built on the delta mechanism. Order status updates from ERP; export of orders + payments via Glue Backend API through Talend | Phase 1 |
| Search | **SiteSearch360** for full-text search; **OOTB Elasticsearch** for category listing pages | Phase 1 |
| Custom product feed | API-based product export to **Feed Dynamix** | Phase 1 |
| Returns management | Custom returns portal in Yves; return labels from **DHL / DPD / GLS** | Phase 1 |
| Payment | **Payone** for order payment + OMS integration (incl. COINs). See risk on ACP below | Phase 1 |
| CRM | **Emarsys** for newsletters and email campaigns | Phase 1 |
| Order splitting | Shipments created in checkout based on **warehouse allocation** | Phase 1 |
| Marketplace / Drop Shipment | Limited Marketplace capabilities; parts of the Marketplace data model used to implement **drop-shipment** fulfilment | Phase 1 (offers/merchants scale in Phase 2+) |
| Social login / SSO | Not yet defined | Phase 2 |

> **Note on "Drop Shipment":** this is **not an official Spryker feature name**. It is implemented with Marketplace primitives (Product Offer + Marketplace Shipment). See [Section 8](08-crosscutting-concepts.md) and research §17.

### Migration Requirements

| Data entity | Source | Volume | Status |
|-------------|--------|--------|--------|
| Customers | Shopware 5 | ~3,000,000 | Planned |
| Encrypted passwords | Shopware 5 | ~3,000,000 | **Under clarification** (re-hash strategy — see [SD-001](04-solution-designs/sd-001-erp-integration-and-data-migration.md)) |
| Orders | DWH | TBD | **Under clarification** |
| Returns | DWH | TBD | **Under clarification** |
| Catalog (products/prices/stock/categories) | Plentymarkets ERP | 60,000 products, 2,000+ categories | Via delta import (see [SD-001](04-solution-designs/sd-001-erp-integration-and-data-migration.md)) |

The initial catalog load is **not a bulk one-shot import** — it is built on the same delta mechanism used for ongoing sync. See [SD-001](04-solution-designs/sd-001-erp-integration-and-data-migration.md).

## Quality Goals

Top quality goals for the architecture, ordered by priority.

| Priority | Quality Goal | Scenario |
|----------|--------------|----------|
| 1 | **On-time, low-risk Go-Live** | Technical Go-Live 2024-09-09 and Public Go-Live 2024-09-23 are met with all launch-blocking decisions closed (see [Section 11](11-risks-and-technical-debt.md) decision gates). TAD started only ~2 months before Go-Live, so schedule risk is the primary driver. |
| 2 | **Storefront performance under 3rd-party load** | With ~15 storefront JS integrations active, a product listing page renders in < 2s (p95) and passing Core Web Vitals; no cross-service JS conflicts. |
| 3 | **Reliable, idempotent ERP data sync** | Delta import of ~50 products every 10 minutes reflects on the storefront within one P&S cycle without duplicates or drift, and full-catalog build completes within the migration window. |
| 4 | **Catalog scalability** | 60,000 → 80,000 products and 2,000 → 2,600 categories are served without PLP/navigation degradation. |
| 5 | **Payment correctness & continuity** | Payone payment authorize/capture and order status are correct for 4,000–6,000 messages/day, on a payment integration that survives the ACP sunset ([ADR-001](09-architecture-decisions/adr-001-payone-direct-module-over-acp.md)). |

## Stakeholders

| Role / Name | Organization | Expectations |
|-------------|--------------|--------------|
| Customers (B2C shoppers) | — | Fast, reliable shopping experience; smooth returns |
| Back Office users (~20) | Norma24 | Efficient catalog, order and returns management |
| Yevhen Romanov (Driver) | Spryker | Architecture delivered on schedule |
| Andriy Tkachenko (Internal Approver) | Spryker | Architecture soundness |
| Felix Jungermann (External Approver) | Norma / KPS | Business fit, external sign-off |
| KPS | Integration partner | Owns/operates several integrations (Talend, various systems) |
| Norma24 | Business owner | Owns ERP, DWH, storefront requirements |
| Spryker Cloud team | Spryker | Confirms DWH access mechanism (VPN vs Data Exchange API) |

---

*Corresponds to [arc42 Section 1](https://docs.arc42.org/section-1/)*
