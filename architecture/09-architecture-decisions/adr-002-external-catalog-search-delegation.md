# ADR-002: Delegate catalog search / filtering to an external Daimler API

## Status

**Proposed** (2026-07-23)

> Depends on the external-search contract gate (G2 in §11). Stays **Proposed** until the contract + mapping PoC close.

## Context

The TAD states the Single DB/Redis/ES setup "won't be used for products search/filtering" — catalog **search, filtering, and the category tree** are delegated to a customer (Daimler) third-party service. At 26M parts, running product search on Spryker's own Elasticsearch would be a major scaling and indexing burden, and Daimler already owns a catalog-search capability. Spryker documents external-search delegation as a **pattern**, not a packaged feature: "you can use any external search provider instead of the default Elasticsearch" by swapping the `QueryInterface`/search plugins and mapping responses back to the shop format ([research §16](https://docs.spryker.com/docs/pbc/all/search/latest/base-shop/tutorials-and-howtos/tutorial-integrate-any-search-engine-into-a-project)). There is **no turnkey connector** — it is a custom integration.

## Decision

We will **delegate PLP search, filtering, suggestions, and the category tree to the external Daimler catalog search API**, implemented via the documented Spryker search-migration pattern: project `QueryInterface` implementations call the external API and map results back to the shop's expected format, and we handle search update/populate events. Spryker Elasticsearch is **not** used for product search/filtering (it remains for CMS/utility search only). External results are reconciled against the customer's allowed product set via [Merchant Product Restrictions](https://docs.spryker.com/docs/pbc/all/merchant-management/latest/base-shop/merchant-product-restrictions-feature-overview/merchant-product-restrictions-feature-overview).

## Consequences

### Positive
- Removes the need to index and search 26M products in Spryker ES — a large scaling win ([ADR-001](adr-001-single-shared-database-at-scale.md)).
- Uses an officially documented, supported pattern.
- Daimler keeps ownership of catalog search relevance at their scale.

### Negative
- **Custom build, not a connector** — response mapping, suggestions, and update-event handling are all project work.
- Storefront browsing depends on an external system's availability/latency → must fail soft; needs an SLA (tech debt in §11).
- **Restriction reconciliation is non-trivial:** the external engine does not know per-customer product lists, so results must be filtered/validated against Merchant Product Restrictions before display.
- Two sources of truth for "what exists" (Spryker catalog vs external search index) must be kept consistent.

---

*Corresponds to [arc42 Section 9](https://docs.arc42.org/section-9/)*
