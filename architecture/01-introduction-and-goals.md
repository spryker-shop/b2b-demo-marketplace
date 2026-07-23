# Introduction and Goals

## Approval & Status

| Field | Value |
|-------|-------|
| **Project** | NORMA — B2C food-discount retailer with limited Marketplace capabilities |
| **Status** | Approved internally (Green) |
| **Internal approval** | 2024-07-25 by Andriy Tkachenko |
| **Driver** | Yevhen Romanov |
| **Internal Approver** | Andriy Tkachenko |
| **External Approver** | Felix Jungermann (Norma/KPS) |
| **Sales** | Michael Türk |
| **Handover** | 2024-08-15 — Spryker: Yevhen Romanov, Vitalii Ivanov; Norma/KPS: Vitali Adam, Felix Jungermann, Sonja Bouwers |
| **TAD version** | V1 (Confluence space ES, page 4317610023) |
| **Technical Go-Live** | **2024-09-09** |
| **Public Go-Live** | **2024-09-23** |

> **Deadline note:** the TAD was started roughly 2 months before go-live. This compressed timeline is carried as a project risk in [§11 Risks](11-risks-and-technical-debt.md).

## Requirements Overview

NORMA is a food discount retailer operating 1,300+ stores across Germany, France, Czech Republic and Austria (brands *Norma* and *Norma-Rodi*), with food and non-food businesses. This programme **re-platforms the existing non-food B2C online shop from Shopware 5 to Spryker** because Shopware 5 has reached end of support; Spryker provides a stable base for future enhancements.

**Business model:** B2C **with limited Marketplace capabilities**. Parts of the Marketplace (merchant) data model are used *only* to handle **Drop Shipments** — the merchant/merchant-order model is the structural basis, not a full marketplace launch. Long-term vision is expansion toward a marketplace (~300 dropshippers in the +…Y horizon).

**MVP vs 100%:** Phase 1 (this TAD) delivers a single DE store (EUR, de_DE) with the full re-platform scope and integrations below. Bundles and SSO/social login are explicitly **out of MVP** (Phase 2). Multi-country (4 countries) is a long-term, un-scheduled expansion.

### Functional Scope

| Scope item | Description | Release Phase |
|------------|-------------|---------------|
| Re-platforming (Shopware 5 → Spryker) | Replace the non-food B2C shop on a supported platform | Phase 1 (Green) |
| Marketplace (limited) | Merchant data model reused **only for Drop Shipments** | Phase 1 (Green) |
| Configuration & setup | Single store; Yves storefront customised per CI/CD; currency EUR, locale de_DE | Phase 1 (Green) |
| Data migration (catalog) | Import products + prices/stock/categories via Glue **Backend API** through Talend service bus from ERP; **delta-only** import types; initial/full import built on the delta solution; order-status updates from ERP | Phase 1 (Green) |
| Data export (orders) | Export orders + payments from Spryker via Glue Backend API through Talend | Phase 1 (Green) |
| Search | Custom search on **SiteSearch360** for full-text; **OOTB Elasticsearch** for category pages | Phase 1 (Green) |
| Product feed generation | API-based product export for **Feed Dynamix** | Phase 1 (Green) |
| Returns management | Return voucher/labels from DHL/DPD/GLS; custom "return" portal in Yves storefront | Phase 1 (Green) |
| ERP integration | Plentymarkets ERP via Talend ETL: products, prices, stock, orders & payments | Phase 1 (Green) |
| Payment integration | **Payone** (ACP-based); handles order payment + OMS integration | Phase 1 (Green) |
| CRM | Emarsys for newsletters / email campaigns | Phase 1 (Green) |
| Order splitting | Shipments created at checkout based on warehouse allocation | Phase 1 (Green) |
| ~24 storefront integrations | Mostly JS-widget storefront integrations (analytics, consent, tracking, recommendations) — see [§3](03-system-scope-and-context.md) | Phase 1 (Green) |
| Social login / SSO | Storefront single sign-on / social login | **Phase 2 (Red)** |
| Bundles | Product bundles | Phase 2 (+1Y) |
| Multi-country expansion | AT, FR, CZ stores | Long-term (+…Y) |

### Migration Requirements

| Aspect | Detail |
|--------|--------|
| **Source system** | Shopware 5 (non-food B2C shop) |
| **Customers** | Migrated from Shopware |
| **Encrypted passwords** | From Shopware — **under clarification** |
| **Orders (historical)** | From DWH — **under clarification** |
| **Returns (historical)** | From DWH — **under clarification** |
| **Catalog** | Products/prices/stock/categories delivered live from Plentymarkets via Talend (delta), not a one-off migration |
| **Timeline** | Must be ready for Technical Go-Live 2024-09-09 |

> **TODO (Norma/KPS):** confirm the migration approach and volumes for encrypted passwords, historical orders and returns — all marked "under clarification" in the TAD. See [SD-001](04-solution-designs/sd-001-shopware5-data-migration.md).

## Quality Goals

| Priority | Quality Goal | Scenario |
|----------|--------------|----------|
| 1 | **Deadline reliability** | The full Phase-1 scope is production-ready and validated by Technical Go-Live 2024-09-09, ahead of Public Go-Live 2024-09-23. |
| 2 | **Catalog import performance** | A delta import of ~50 products every 10 minutes completes and is published to Storefront well within the 10-minute window at 60k-product scale, without degrading the storefront. |
| 3 | **Storefront performance under 3rd-party load** | With ~24 storefront integrations (mostly JS widgets) active, PDP/PLP/category pages remain within performance budget during the March–August main season (60k visitors/day). |
| 4 | **Scalability of catalog structures** | 2,000+ active categories build (category nodes / storages) without unacceptable load-time degradation. |
| 5 | **Payment reliability** | Payone ACP payments and their OMS integration process 4,000–6,000 messages/day with no lost order-payment state. |

## Stakeholders

| Role / Name | Organisation | Expectations |
|-------------|--------------|--------------|
| Yevhen Romanov (Driver) | Spryker | Technically sound, on-time delivery of the re-platform |
| Vitalii Ivanov | Spryker | Architecture and delivery |
| Andriy Tkachenko (Internal Approver) | Spryker | Architecture meets Spryker standards |
| Felix Jungermann (External Approver) | Norma / KPS | Solution meets Norma business requirements |
| Vitali Adam | Norma / KPS | Integration correctness (ERP, DWH, storefront services) |
| Sonja Bouwers | Norma / KPS | Business / project alignment |
| Michael Türk (Sales) | Spryker | Commercial alignment |
| KPS (integration partner) | KPS | Delivery of most 3rd-party / storefront integrations |
| Norma24 | Norma | Owns OMQ, Facebook, Emarsys, DWH integrations |
| End customers | — | Fast, reliable food/non-food discount shopping |

---

*Corresponds to [arc42 Section 1](https://docs.arc42.org/section-1/)*
