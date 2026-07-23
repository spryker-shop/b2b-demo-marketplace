# ADR-001: Model dealers as merchants and dealer↔customer links via Merchant Relationship

## Status

**Accepted** (2024-06-04)

## Context

Daimler's 3-tier sales model requires dealers (Tier 3) to sell HQ-provided products online, own order & shipping, and control which customers may buy from them and at what price. A customer must join a dealer's pool by request before ordering, and at order time sees only the active dealer's products/prices. This is a marketplace shape: many sellers, buyer-scoped catalog and pricing, seller-owned fulfilment.

## Decision

Model **dealers as Spryker marketplace merchants**, and realize the customer→dealer relationship on **Merchant Relationship — Merchant B2B Contracts / Contract Requests** ([docs](https://docs.spryker.com/docs/pbc/all/merchant-management/latest/base-shop/merchant-b2b-contracts-and-contract-requests-feature-overview)). Buyer-scoped visibility uses **Merchant Product Restrictions** ([docs](https://docs.spryker.com/docs/pbc/all/merchant-management/latest/base-shop/merchant-product-restrictions-feature-overview/merchant-product-restrictions-feature-overview)); buyer-scoped pricing uses **Merchant Custom Prices** ([docs](https://docs.spryker.com/docs/pbc/all/price-management/latest/base-shop/merchant-custom-prices-feature-overview)). Federated customer login via DTAG IAM uses a **custom `OauthCustomerAuthenticationStrategyPlugin`** to establish company/dealer context on first login ([docs](https://docs.spryker.com/docs/pbc/all/oauth/latest/storefront-account-bootstrapping)). A custom step handles merchant assignment during business-unit creation/edit.

## Consequences

### Positive
- Reuses GA marketplace features for approval workflow, product restriction and per-relation pricing — minimal reinvention.
- Dealer fulfilment maps naturally onto merchant orders + Merchant Portal.

### Negative
- Per-product/per-customer **discount groups** exceed OOTB Merchant Custom Prices and require custom logic ([SD-001](../04-solution-designs/sd-001-dealer-prices-discount-groups.md)).
- OOTB `AcceptOnly` / `CreateCustomer` OAuth strategies are insufficient for B2B company context — a custom strategy is mandatory.
- Merchant Product Restrictions has documented set/bundle edge cases (single excluded item still shows the rest); low exposure since bundles are N/A at Go-Live.

---

*Corresponds to [arc42 Section 9](https://docs.arc42.org/section-9/)*
