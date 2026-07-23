# Introduction and Goals

Describes the relevant requirements and driving forces for the **Daimler Truck B2B Parts After-Sales** platform — a re-platforming of a ~20-year-old home-brewed system onto a Spryker B2B Marketplace.

## Approval / Status

| Field | Value |
|-------|-------|
| **Status** | **Approved WITH RISKS (Yellow)** |
| **Approval date** | 2024-06-04 |
| **Go-Live** | October 2024 |
| **Scope** | **No MVP — 100% of functionality expected at go-live** |
| **Driver** | Yevhen Romanov |
| **Approver** | Andriy Tkachenko |
| **Sales** | Niko Wilsmann |
| **Space / TAD version** | ES / TAD V1 |
| **Source TAD** | https://spryker.atlassian.net/wiki/spaces/ES/pages/4098327775 |

> **TODO:** "Approved WITH RISKS (Yellow)" means the yellow risk items in [Section 11](11-risks-and-technical-debt.md) must be actively tracked to closure. Owner and cadence for that risk review are not stated in the TAD.

## Requirements Overview

- **Business model:** B2B spare-parts business in **After Sales** — end-customer spare parts to maintain and repair trucks for Mercedes-Benz, Fuso Europe and EvoBus (common parts; no bus-specific focus).
- **Legacy being re-platformed:** a ~20-year-old home-brewed solution, replaced in full (no phased MVP).
- **Frontend:** Yves for now; long-term intent is headless.
- **Delivery:** Scrum, 3-week sprints.

### Three-tier B2B marketplace hierarchy

The domain is a **3-tier sales model** that must be modelled explicitly across the architecture:

| Tier | Actor | Role in the platform | Scale (Go-Live) |
|------|-------|----------------------|-----------------|
| 1 | **Headquarter** (Daimler Germany) | Provides the online platform to markets and dealers; manages ALL markets; per-market monitoring. | ~10 concurrent BO users |
| 2 | **Markets** (Daimler subsidiaries / general distributors) | One per country; manage country-specific dealers and market-specific settings; see only own market data. | 22 markets (future); ~50 concurrent BO users |
| 3 | **Dealers** (Spryker **merchants**) | Sell HQ-provided products online; own the order & shipping; define discount groups or a dealer list price. | min. 2,500 dealer users; ~1,000 active dealer orgs / ~5,000 users concurrent in MP |
| — | **Customers** (buyers) | Organizations with own workshops (transportation cos., garbage disposal, independent workshops). | min. 9,000 customer **organizations** (not users) |

Products are provided globally by HQ and are **shared between all markets**. A customer registers with a dealer (merchant) pool before it can order; at order time the customer selects the active dealer, and only that dealer's prices/products are shown.

### Functional Scope

Daimler is 100% scope at Go-Live (October 2024). The "Release Phase" column distinguishes what is live at Go-Live from what scales up in the first year after release (GL+1Y).

| Capability | Description | Spryker grounding | Release Phase |
|-----------|-------------|-------------------|---------------|
| Multi-market storefront | 22 markets as dynamic stores, one region, one domain | Dynamic Multi-Store (DMS) | Go-Live (1 country) → GL+1Y (22) |
| Dealer-as-merchant model | Dealers are Spryker merchants owning order & fulfilment | Marketplace Merchant + Merchant Portal | Go-Live |
| Customer→dealer registration & approval | Customer joins a dealer pool by request; dealer accepts/rejects | Merchant Relationship (Merchant B2B Contracts) | Go-Live |
| Per-customer product visibility | Only the active dealer's products/prices shown | Merchant Product Restrictions | Go-Live |
| Dealer prices & discount groups | Per-product / per-customer discount groups or dealer list price | Merchant Custom Prices + custom discount logic ([SD-001](04-solution-designs/sd-001-dealer-prices-discount-groups.md)) | Go-Live |
| Dealer hierarchy (branches) | Main instance + subordinate branches, shared prices, cross-branch login | BO User roles & groups + custom hierarchy ([SD-002](04-solution-designs/sd-002-dealer-hierarchy-branches.md)) | Go-Live |
| Market data isolation in BO | Each market sees only its own data; HQ sees all | Persistence ACL pattern / custom controller restriction | Go-Live |
| Dealer↔customer communication | Comment/reply dialog inside order detail | Comments module + OMS comments | Go-Live |
| HQ per-market monitoring | Import status, dump status, revenue alerts, legal-info status | Custom BO route/module | Go-Live |
| Large XML parts/price import | FULL import per store, ~1.5M `<SET>#productdata` tags, ≤30 min | Data Import + P&S optimization ([ADR-003](09-architecture-decisions/adr-003-direct-s3-xml-import-not-proxied.md)) | Go-Live |
| External catalog & search | Catalog structure, filtering and PLP building delegated to IParts (3rd party) | Custom search delegation ([ADR-004](09-architecture-decisions/adr-004-external-catalog-search-delegation.md)) | Go-Live |
| Vehicle / FIN-VIN lookup | Part-to-vehicle matching, vehicle data card | VIS integration | Go-Live |
| Parts availability | Global stock/availability lookup | TruckLog DIMS (SOAP) / MB LogBus DIMS (REST) | Phased — not all markets at Go-Live |
| Customer authentication (SSO) | Federated login via Daimler IAM | OAuth SSO (DTAG IAM Empower ID) | Go-Live |
| License-plate search (UK) | Store-specific functionality | Custom, UK store only | Go-Live |
| Payment | **Invoice only, no PSP** | Native Invoice payment method | Go-Live |

### Migration Requirements

| Field | Value |
|-------|-------|
| **Source system** | ~20-year-old home-brewed after-sales solution |
| **Entities to migrate** | Customer/dealer relations, customer rebate classes, open orders |
| **Historical migration** | Yes (required) |
| **Timeline** | Complete before Go-Live October 2024 |

> **TODO:** Volumes for the historical migration (number of relations, rebate classes, open orders) and the migration tooling/cutover plan are not quantified in the TAD. Owner: migration/data team.

## Quality Goals

| Priority | Quality Goal | Scenario |
|----------|--------------|----------|
| 1 | **Import Performance & Scalability** | A FULL XML parts/price import per store (~1.5M `<SET>#productdata` tags) completes in ~30 min (not strict) without exhausting memory, scaling toward 26 mio part numbers within a year. |
| 2 | **Data Isolation (multi-market)** | A logged-in Market BO user can read/edit only their own market's data; only HQ users see all markets. |
| 3 | **Correctness of dealer-scoped pricing** | A customer sees exactly the prices/products of the single dealer they selected as active, including the dealer's per-customer discount groups. |
| 4 | **Scalability (catalog & concurrency)** | The platform serves ~120,000 sessions/month and ~5,000 concurrent MP dealer users with a catalog growing from 300k to 26 mio part numbers. |
| 5 | **Maintainability / upgradeability** | Custom hierarchy, restriction and import logic build on named Spryker features so they survive core upgrades. |

## Stakeholders

| Role | Expectations |
|------|--------------|
| **Headquarter (Daimler Germany)** | Central control of all markets, per-market monitoring, global product catalog. |
| **Markets (22 subsidiaries)** | Country-specific settings and dealer management, isolated to their own data. |
| **Dealers (merchants, ≥2,500 users)** | Manage their customer pool, prices/discount groups, own orders & shipping via Merchant Portal. |
| **Customers (≥9,000 orgs)** | Find correct parts for their vehicles, order from their chosen dealer at agreed prices. |
| **Daimler IT (system owners)** | Reliable integration with 10 external systems (IParts, VIS, DIMS, CRISP, IPS, RetailNet, DTAG IAM, etc.). |
| **Project team (Spryker)** | Deliver 100% scope by October 2024 while managing the yellow-flagged risks. |

---

*Corresponds to [arc42 Section 1](https://docs.arc42.org/section-1/)*
