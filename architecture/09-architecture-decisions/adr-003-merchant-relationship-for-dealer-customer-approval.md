# ADR-003: Build dealer approval / customer pool / pricing on Merchant B2B Contracts

## Status

**Accepted** (2026-07-23)

## Context

Daimler's 3-tier model needs: dealers as sellers, customers who must be **approved into a dealer's pool** before ordering, **merchant-specific customer IDs**, and **merchant-specific prices and product visibility**. Not all customers order from all dealers; a customer selects an active dealer and sees only that dealer's approved products and prices. Spryker provides these as first-class, confirmed features: **Merchant B2B Contracts & Contract Requests** model the dealer↔customer relation with a request→approval lifecycle and buyer-specific prices/products ([research §2](https://docs.spryker.com/docs/pbc/all/merchant-management/latest/marketplace/marketplace-merchant-b2b-contracts-and-contract-requests-feature-overview)); **Merchant Product Restrictions** control per-customer product visibility via allow/exclude product lists ([research §3](https://docs.spryker.com/docs/pbc/all/merchant-management/latest/base-shop/merchant-product-restrictions-feature-overview/merchant-product-restrictions-feature-overview)).

## Decision

We will **build the dealer approval, customer pool, and merchant-specific pricing/visibility on Merchant B2B Contracts + Merchant Product Restrictions**, adding project-specific pieces on top: the customer registration-request UI + dealer new/approved overview pages, the merchant-specific customer ID field, discount-group pricing, and merchant assignment during business-unit create/edit. Branch/hierarchy specifics are layered on the merchant model ([SD-002](../04-solution-designs/sd-002-dealer-hierarchy-and-customer-approval.md)).

## Consequences

### Positive
- Reuses confirmed Spryker primitives (relation request → approval, buyer-specific prices/products) instead of a custom relationship model.
- Merchant Product Restrictions natively suppress price, search appearance, and PDP access for excluded products.
- Aligns the data model with core so future upgrades are cheaper.

### Negative
- Merchant Product Restrictions with external search needs explicit reconciliation ([ADR-002](adr-002-external-catalog-search-delegation.md)) — the external engine is unaware of per-customer lists.
- The branch hierarchy (shared main-instance prices, cross-branch add-without-request, cross-branch login) is **not** covered by core and is custom (SD-002).
- Product-set/bundle exclude edge cases exist in Merchant Product Restrictions (research §3) — not expected to bite (no bundles), but noted.

---

*Corresponds to [arc42 Section 9](https://docs.arc42.org/section-9/)*
