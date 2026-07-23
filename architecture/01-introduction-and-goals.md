# Introduction and Goals

Describes the relevant requirements and driving forces for the **Daimler Truck B2B Parts (After Sales)** platform — a re-platforming of a 20-year-old home-brewed solution onto Spryker.

## Status

| Field | Value |
|-------|-------|
| **Status** | Approved WITH RISKS (Yellow) |
| **Driver** | Yevhen Romanov |
| **Approver** | Andriy Tkachenko |
| **Sales contact** | Niko Wilsmann |
| **Approved at** | 2024-06-04 |
| **Go-Live** | October 2024 (hard deadline — no MVP, 100% scope) |
| **Source TAD** | Daimler Truck TAD v28 (Confluence) |

> The Yellow status is driven by the scaling profile (300k → 26M products on a single shared DB/Redis/ES) and by integrations that are not yet Spryker-GA (see §11). Launch-blocking items are tracked as decision gates in §11.

## Requirements Overview

Daimler Truck sells spare parts for Mercedes-Benz, FUSO Europe and EvoBus (common parts) in an After-Sales B2B model. This project re-platforms the legacy home-brewed shop onto Spryker with the **full functional scope at Go-Live** (no MVP staging of functionality).

Driving forces:
- **Re-platforming**, not greenfield — historical data (customer/dealer relations, rebate classes, open orders) must migrate.
- **3-tier sales model**: Headquarter (Daimler Germany) provides the platform → **Markets** (22 country subsidiaries/distributors) → **Dealers** (Spryker **merchants**, sell HQ-provided products, handle order/shipping) → **Customers** (workshops, transport companies, independent service providers).
- **Yves storefront** now; long-term plan to go headless (Storefront API kept build-ready).
- **Scrum**, 3-week sprints.
- **Extreme catalog growth**: ~300,000 part numbers at Go-Live, up to **26 million within one year** — on a single shared DB/Redis/ES. This is the central scaling decision of the whole document (see [ADR-001](09-architecture-decisions/adr-001-single-shared-database-at-scale.md), [SD-001](04-solution-designs/sd-001-large-scale-data-import-and-publish-sync.md), §10, §11).
- **Product search/filtering is delegated to an external Daimler catalog API** — Spryker Elasticsearch is not used for product search/filter (see [ADR-002](09-architecture-decisions/adr-002-external-catalog-search-delegation.md)).

### Functional Scope

| Capability | Description | Spryker basis | Release Phase |
|---|---|---|---|
| Parts catalog + PDP | Minimal abstract product set (name + price); catalog structure & fitment from IParts | Product, Data Import | Phase 1 (Go-Live) |
| Catalog search / filter / category tree | Delegated to external Daimler catalog API (not Spryker ES) | Search-migration pattern | Phase 1 |
| Dealer-as-merchant + branch hierarchy | Main instance + subordinate branches; branch prices = main-instance prices | Merchant, custom hierarchy | Phase 1 |
| Customer approval (pool) | Registration-request → dealer approve/reject, merchant-specific customer ID | Merchant B2B Contracts | Phase 1 |
| Merchant-specific pricing / discount groups | Dealer list price + per-customer discount groups (calculated dynamically) | Price (merchant relation), custom | Phase 1 |
| Per-customer product visibility | Only a dealer's approved products/prices shown to a customer | Merchant Product Restrictions | Phase 1 |
| Per-market BO data isolation | Each market sees only its own data; HQ sees all | Persistence ACL + BO roles | Phase 1 |
| HQ monitoring | Per-market import status, dump status, revenue thresholds, legal status | Custom BO module | Phase 1 |
| Market legal templates | T&C / warranty / sales-regulation templates per market, fallback to default | Merchant profile + config | Phase 1 |
| Order communication | Customer↔dealer dialog on order | Comments + OMS | Phase 1 |
| Availability lookup | Live stock from TruckLog / MB LogBus DIMS | Custom adapter | Phase 1 (TruckLog phased per market) |
| FIN/VIN + licence-plate search | VIS integration; UK licence-plate search | Custom | Phase 1 (UK licence plate market-specific) |
| Invoice-only checkout | No PSP; Invoice payment only | Payment (Invoice) | Phase 1 |
| Headless storefront | Long-term move to headless | Storefront API | Future |

### Migration Requirements

| Item | Detail |
|---|---|
| Source system | 20-year-old home-brewed Daimler After-Sales shop |
| Data to migrate | Customer/dealer relations, customer rebate classes, open orders |
| Functionality replaced | Entire legacy shop (100% at Go-Live) |
| Catalog source | Ongoing full XML import from IPS via S3 (~1.5M `<SET>` product-data tags), not a one-off migration |
| Timeline | Go-Live October 2024 |

Migration of the customer/dealer relation and rebate-class data seeds the merchant-relationship and pricing model — see [SD-002](04-solution-designs/sd-002-dealer-hierarchy-and-customer-approval.md).

## Quality Goals

| Priority | Quality Goal | Scenario |
|----------|--------------|----------|
| 1 | **Scalability (catalog)** | Catalog persistence + Publish & Sync remain viable as product count grows 300k → 26M on a single shared DB/Redis/ES within one year of Go-Live |
| 2 | **Import throughput** | A full parts+price XML import (~1.5M `<SET>` tags) completes in ~30 minutes (target, not strict) without blocking the storefront |
| 3 | **Data isolation / correctness** | A market BO user sees only their market's data; a customer sees only their active dealer's approved products and merchant-specific prices |
| 4 | **Availability of storefront** | Storefront stays responsive during large imports and does not depend on Spryker ES for product search (delegated externally) |
| 5 | **Maintainability** | Custom dealer/market/import domains follow Spryker module conventions so features evolve independently |

## Stakeholders

| Role/Name | Contact | Expectations |
|-----------|---------|--------------|
| Headquarter (Daimler Germany) | HQ BO team | Global catalog control, per-market monitoring, one platform for all markets |
| Markets (22 subsidiaries/distributors) | Market BO users (~50 concurrent) | Manage own market settings and data only; legal templates per country |
| Dealers (merchants, min 2500 users) | Merchant Portal users (~5000, 1000 active orgs) | Manage customer pool, merchant-specific prices, orders/shipping |
| Customers (min 9000 orgs) | Company users | Find parts, see own dealer's prices/availability, order (Invoice) |
| Driver | Yevhen Romanov | Delivery to Go-Live scope |
| Approver | Andriy Tkachenko | Architecture soundness within risk appetite |
| Daimler integration owners | Per-system teams | Timely, stable APIs (IParts, VIS, DIMS, CRISP, IPS, RetailNet, DTAG IAM, external search) |

---

*Corresponds to [arc42 Section 1](https://docs.arc42.org/section-1/)*
