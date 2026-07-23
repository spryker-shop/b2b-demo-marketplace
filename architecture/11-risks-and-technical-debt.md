# Risks and Technical Debt

Known risks, technical debt, and the pre-Go-Live decision gates for the Daimler Truck B2B Parts platform. The project has a hard Go-Live (October 2024) and TAD status **Approved WITH RISKS (Yellow)** — launch-blocking unknowns are promoted to decision gates below.

## Technical Risks

Mitigations name real Spryker features and link the docs.

| Risk | Impact | Mitigation |
|------|--------|------------|
| **Single-DB scaling to 26M products** — 300k → 26M in +1Y on one shared DB/Redis/ES | Hot tables (`spy_product_abstract`, `spy_price_product_store`) and Redis memory may not hold; storefront/import degrade | Spike + load test; [Data import optimization](https://docs.spryker.com/docs/dg/dev/data-import/latest/data-import-optimization-guidelines) (CTE, chunked P&S); keep product search off ES ([search-migration pattern](https://docs.spryker.com/docs/pbc/all/search/latest/base-shop/tutorials-and-howtos/tutorial-integrate-any-search-engine-into-a-project)). See [ADR-001](09-architecture-decisions/adr-001-single-shared-database-at-scale.md), [SD-001](04-solution-designs/sd-001-large-scale-data-import-and-publish-sync.md) — **Gate G1** |
| **Import throughput** — ~1.5M `<SET>` XML per store in ~30 min, ×22 stores | Missed import window; stale catalog/prices | S3 (no proxy) + CTE bulk + chunked P&S + event batching (`disableEvent`/`triggerBulk`/`enableEvent`), research §11 — **Gate G1** |
| **External catalog search dependency** — PLP/search/category tree owned by Daimler API | Storefront browsing unusable if API is slow/down; restriction reconciliation complexity | Documented search-migration pattern (custom, not a connector); fail-soft; reconcile results with [Merchant Product Restrictions](https://docs.spryker.com/docs/pbc/all/merchant-management/latest/base-shop/merchant-product-restrictions-feature-overview/merchant-product-restrictions-feature-overview). See [ADR-002](09-architecture-decisions/adr-002-external-catalog-search-delegation.md) — **Gate G2** |
| **DTAG IAM over Glue/headless** — Glue-API OAuth SSO is NOT GA | Long-term headless SSO cannot use standard federated auth | Storefront/BO use [Federated Authentication](https://docs.spryker.com/docs/pbc/all/oauth/latest/federated-authentication) (GA); for headless use the CIAM-provider path or a project solution; claims→role mapping done at project level (research §15) — **Gate G3** |
| **Availability integration phasing** — TruckLog not live in all markets | Inconsistent availability across markets at Go-Live | MB LogBus DIMS fallback (REST); read-through, non-blocking display |
| **Persistence ACL Propel-only limitation** — non-Propel queries bypass isolation | A market user could see other markets' data via a raw/non-Propel query | Keep market-scoped reads on Propel API; audit custom queries; add controller-level guard as defence-in-depth. See [ADR-004](09-architecture-decisions/adr-004-persistence-acl-for-per-market-isolation.md) |
| **Complex dynamic pricing** — discount-group logic per product+customer | Pricing errors, calculation performance | Build on merchant-relation prices + calculator; see [SD-002](04-solution-designs/sd-002-dealer-hierarchy-and-customer-approval.md); the TAD referenced an external Google Doc for pricing detail (carry-over TODO below) |
| **Historical data migration** — customer/dealer relations, rebate classes, open orders | Wrong customer pools / prices / open orders at launch | One-off migration mapped onto merchant relations + price scope; validate before Go-Live |
| **Integration readiness across ~12 systems + owners** | Any late/unstable API risks the hard deadline | Integration-readiness checklist with owners (§7); contract stubs for parallel dev |

## Decision Gates (pre-Go-Live)

Launch-blocking decisions that must close before/around Go-Live (October 2024).

| Gate | Decision to close | Spike / Action | Owner | Target |
|------|-------------------|----------------|-------|--------|
| **G1 — Single-DB scaling** | Is a single shared DB viable to 26M, and does import + P&S meet the window? | Load-test import + P&S with a 26M-scale catalog on prod-like Stage; measure hot-table + Redis behaviour | Solution architect + infra + Daimler IPS | Before Go-Live; **ADR-001 stays Proposed until closed** |
| **G2 — External search contract** | Is the external catalog search API contract sufficient (search, filter, tree, suggestions, update events) and reconcilable with restrictions? | Confirm response schema; build mapping PoC; validate restriction reconciliation | Solution architect + Daimler catalog team | Before Go-Live; **ADR-002 stays Proposed until closed** |
| **G3 — DTAG IAM scope** | Storefront-only federated auth for Go-Live, headless SSO path deferred? | Confirm OIDC client + claim schema; decide headless approach (CIAM path vs project solution); design claims→role mapping | Solution architect + Daimler IAM team | Before Go-Live |

## Technical Debt

| Item | Impact | Plan |
|------|--------|------|
| **Product search coupled to external API** | Storefront browsing depends on a system outside Spryker's SLA | Define SLA + caching/fail-soft; revisit if Spryker ES becomes viable at scale |
| **Claims → ACL role mapping at project level** | Custom mapping until Spryker ships it | Isolate mapping in one module; adopt native feature when GA (research §15) |
| **Availability not stored (read-through)** | Every PDP/cart hits an external system | Add short-TTL caching if call volume/latency requires |
| **Legacy pricing rules** | Complex discount-group logic ported from a 20-yo system | Encapsulate in a price calculator; add regression tests from migrated data |

## Open Questions (carried verbatim from the TAD)

- **Dealer Prices** — "Complex prices with discount-group logic per product and customer" — the TAD linked an external Google Doc with the detailed solution. > **TODO:** obtain and fold the pricing-solution detail into [SD-002](04-solution-designs/sd-002-dealer-hierarchy-and-customer-approval.md). Owner: solution architect.
- **Dealer/Customer Communication** — Comments module (+ OMS comments) **or** a custom SalesComments module. > **TODO:** decide Comments+OMS vs custom SalesComments. Owner: solution architect.
- **Multi Market / Backoffice isolation** — Persistence ACL **or** a less-effort custom controller restriction. > **TODO:** confirm Persistence ACL (with Propel-only caveat) vs controller restriction. Owner: solution architect — see [ADR-004](09-architecture-decisions/adr-004-persistence-acl-for-per-market-isolation.md).
- **Data Import** — Split large files on Spryker Middleware **and/or** CTE-based import. > **TODO:** confirm file-splitting approach (middleware vs importer-side chunking) — the "split on middleware" phrasing is best-practice, not a quoted rule (research §11). Owner: solution architect — see [SD-001](04-solution-designs/sd-001-large-scale-data-import-and-publish-sync.md).

---

*Corresponds to [arc42 Section 11](https://docs.arc42.org/section-11/)*
