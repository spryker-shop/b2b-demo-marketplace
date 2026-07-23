# Cross-Cutting Concepts

Principles and patterns that span multiple building blocks of the Daimler Truck B2B Parts platform.

## Authentication & Authorization

- **Customer authentication:** federated via **DTAG IAM (Empower ID)** over OAuth. A **custom `OauthCustomerAuthenticationStrategyPlugin`** resolves the customer and establishes company/dealer-pool context, because the OOTB `AcceptOnly` / `CreateCustomer` strategies do not set up B2B company context ([OAuth account bootstrapping](https://docs.spryker.com/docs/pbc/all/oauth/latest/storefront-account-bootstrapping)).
- **Back Office authorization / market isolation:** Market users must only read/write their own market's data; HQ sees all. Implemented via the **Persistence ACL pattern / custom controller restriction** built on the ACL model (roles, groups, privileges, resources — [User Management](https://docs.spryker.com/docs/pbc/all/user-management/latest/user-management)). See [ADR-002](09-architecture-decisions/adr-002-persistence-acl-for-market-isolation.md); the exact row-level mechanism is an uncertain named feature and must be confirmed in the installed modules.
- **Dealer hierarchy roles:** branch roles (view-only orders, dealer-specific customer management, cross-branch login) via **BO User roles & groups** best practice (assign roles to groups, users to groups — [docs](https://docs.spryker.com/docs/pbc/all/user-management/latest/base-shop/manage-in-the-back-office/best-practices-manage-users-and-their-permissions-with-roles-and-groups)). See [SD-002](04-solution-designs/sd-002-dealer-hierarchy-branches.md).

## Internationalization & Multi-Store

- **22 markets** as dynamic stores in one EU region under one domain via **Dynamic Multi-Store** ([overview](https://docs.spryker.com/docs/pbc/all/dynamic-multistore/latest/base-shop/dynamic-multistore-feature-overview)).
- 19 languages, 12 currencies; products global/shared, market settings & legal templates isolated per store.
- Store must be selected per request (`Store` header / switcher); stores appear on the Storefront only after Publish & Sync.

## Catalog & Search Delegation

- Catalog structure, category tree (≤10 categories, manually configured) and **PLP/search filtering are delegated to IParts**. Spryker abstract products carry only minimal data (name + price) to be listable. Spryker Search/ES is **not** the product search engine. See [ADR-004](09-architecture-decisions/adr-004-external-catalog-search-delegation.md).

## Data Import / Export

- **Import:** FULL per-store XML (~1.5M `<SET>#productdata` tags, ≤30 min) landed on S3, split on middleware, ingested via CTE/PDO bulk import, propagated by tuned multi-queue Publish & Sync. Import runs are tracked for HQ monitoring. See [ADR-003](09-architecture-decisions/adr-003-direct-s3-xml-import-not-proxied.md) and [data-import optimization](https://docs.spryker.com/docs/dg/dev/data-import/latest/data-import-optimization-guidelines).
- **Export:** **None** — each merchant fulfils orders via the Merchant Portal; no order export.

## Pricing

- Prices are always tied to a specific customer of a specific dealer. Built on **Merchant Custom Prices** ([docs](https://docs.spryker.com/docs/pbc/all/price-management/latest/base-shop/merchant-custom-prices-feature-overview)) plus custom discount-group logic (per-product/per-customer) — see [SD-001](04-solution-designs/sd-001-dealer-prices-discount-groups.md). Customer-specific prices are calculated dynamically from a base of 300,000 dealer list prices + discounts.

## Dealer↔Customer Communication

- Threaded comment/reply dialog inside order detail (dealer) / order history (customer), assumed non-real-time. Built on the **Comments** feature + **OMS comments** integration ([Comments overview](https://docs.spryker.com/docs/pbc/all/cart-and-checkout/latest/base-shop/feature-overviews/comments-feature-overview)), or a custom SalesComments module if the OOTB channel is insufficient.

## Error Handling & Resilience

- External availability (DIMS), catalog (IParts), vehicle (VIS) calls are synchronous on-demand — adapters must degrade gracefully (timeouts, fallbacks; MB LogBus DIMS is itself the fallback where TruckLog is unavailable).

> **TODO:** Define timeout/retry/circuit-breaker policy per external adapter, and behaviour when IParts (catalog/search) is unavailable. Owner: integration team.

## Caching (Redis + Publish & Sync)

- Standard Spryker Publish & Sync into a single Redis; only Storefront-relevant product data is exported at pre-calc; multi-queue publishers with tuned chunk sizes to sustain import volume.

---

*Corresponds to [arc42 Section 8](https://docs.arc42.org/section-8/)*
