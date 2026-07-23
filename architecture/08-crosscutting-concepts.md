# Cross-Cutting Concepts

Principles and patterns that span multiple building blocks of the Daimler Truck B2B Parts platform.

## Authentication & Authorization

**Authentication** is delegated to **DTAG IAM (Empower ID)** via Spryker **Federated Authentication (OAuth2/OIDC)** — Authorization Code flow, `email` as the required claim ([research §15](https://docs.spryker.com/docs/pbc/all/oauth/latest/federated-authentication)). Coverage: Storefront (Yves) and Back Office (Zed) are supported; **Glue/API-level OAuth SSO is not GA** ("coming later"), so the long-term headless move needs the CIAM-provider path or a project-level solution. First login links by email or JIT-provisions an account; the Spryker session is independent of the IdP.

**Authorization** has two distinct layers, which must not be conflated:
- **Back Office user rights** — [User Management + ACL](https://docs.spryker.com/docs/pbc/all/user-management/latest/base-shop/user-and-rights-overview): Users → Groups → Roles → Resources (`/module/controller/action`). Controls *which screens/actions* a market or dealer-branch BO user can reach.
- **Row-level data isolation** — [Persistence ACL](https://docs.spryker.com/docs/pbc/all/user-management/latest/marketplace/persistence-acl-feature-overview/persistence-acl-feature-overview): controls *which records* a user sees, enforced in the Persistence layer via Propel behaviors/hooks. Used for per-market isolation (HQ sees all; a market sees only its store's data). **Limitation: Propel-ORM only** — queries outside Propel are not filtered ([ADR-004](09-architecture-decisions/adr-004-persistence-acl-for-per-market-isolation.md)).

**Customer-side authorization** (which products/prices a customer can access) is governed by the **merchant relationship** and **Merchant Product Restrictions**, not by BO ACL — see §5/§6 and [SD-002](04-solution-designs/sd-002-dealer-hierarchy-and-customer-approval.md).

## Multi-Store & Internationalisation

Dynamic Multistore (GA since 202410.0) runs 22 country stores in one EU region on one codebase/DB. 19 languages, 12 currencies. All products are global/shared; per-market differences are settings, legal templates, and a few functions (discount-class upload, price upload, UK licence-plate search). See §7 for the full strategy.

## Pricing Model

Prices are **customer-specific and calculated dynamically**: a dealer list price plus the customer's discount group per product. Prices attach to the **merchant relation** (dealer↔customer), and **branch prices equal main-instance prices** (branches cannot change prices). LLP (list price) enters via the IPS import; discount groups are applied at calculation time. See [SD-002](04-solution-designs/sd-002-dealer-hierarchy-and-customer-approval.md).

## Catalog Search Delegation

Product search, filtering, and the category tree are **delegated to an external Daimler catalog API** rather than Spryker OpenSearch. Implemented as the documented [search-migration pattern](https://docs.spryker.com/docs/pbc/all/search/latest/base-shop/tutorials-and-howtos/tutorial-integrate-any-search-engine-into-a-project): project `QueryInterface` implementations call the external engine and map responses to the shop's expected format. Merchant Product Restrictions still constrain what a given customer may see — the external results must be filtered/reconciled against the customer's allowed product set. See [ADR-002](09-architecture-decisions/adr-002-external-catalog-search-delegation.md).

## Large-Scale Data Import & Publish & Sync

The load-bearing performance concern. Principles (research §11):
- Files land on **S3**, streamed and read in chunks; never proxied through Spryker.
- **CTE-based bulk** INSERT/UPDATE for millions of rows; avoid ORM per row and per-row facade calls.
- **Chunked Publish & Sync**: operate on chunks, never per message; bulk save; manually batch events (`disableEvent` → `triggerBulk` → `enableEvent`).
- Release memory per batch; use `data:import --progress-bar` for diagnostics (not in production runs).

See [SD-001](04-solution-designs/sd-001-large-scale-data-import-and-publish-sync.md) and [ADR-001](09-architecture-decisions/adr-001-single-shared-database-at-scale.md).

## Availability (Read-Through, Not Stored)

Availability is read live from **TruckLog DIMS** (SOAP, phased per market) with **MB LogBus DIMS** (REST) as fallback. It is displayed, non-blocking, and **not persisted as Spryker stock** — keeping the huge catalog free of a stock table that would otherwise have to scale to 26M.

## Order Communication

Customer↔dealer order dialog uses the [Comments feature + Order Management integration](https://docs.spryker.com/docs/pbc/all/cart-and-checkout/latest/base-shop/feature-overviews/comments-feature-overview) (comments bind to sales orders). No real-time notifications are in scope (per TAD assumption). A custom SalesComments module is the alternative if Comments+OMS proves insufficient.

## Logging, Observability & Error Handling

- **Import observability** is a first-class concern: `ImportExecutionTracker` records import type, `created_at`, `finished_at`, and success per market — surfaced in the HQ monitoring dashboard.
- **External-call resilience**: availability, VIS, and search calls are read-through and must fail soft (degrade display, do not block cart/checkout).
- Standard Spryker logging (Yves/Zed/queue/console); guard-clause error handling per project conventions.

## Security

- All external traffic over HTTPS; DTAG IAM over OAuth2/OIDC.
- PII (customer/dealer data) handled under GDPR; EU hosting region.
- Persistence ACL enforces market data boundaries at the DB-entity level (with the Propel-only caveat noted above).

---

*Corresponds to [arc42 Section 8](https://docs.arc42.org/section-8/)*
