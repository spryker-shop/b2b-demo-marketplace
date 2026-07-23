# SD-001: Dealer Prices & Discount Groups

## Metadata

| Field | Value |
|-------|-------|
| **Status** | Draft |
| **Date** | 2024-06-04 |
| **Author(s)** | Daimler Truck project team |
| **Stakeholders** | HQ, Markets, Dealers |

## Problem Statement

Dealers price parts with a **complex, per-product and per-customer discount-group structure** on top of a dealer list price (LLP). Prices are always tied to a specific customer of a specific dealer; customer-specific prices are calculated dynamically from ~300,000 dealer list prices plus discounts. OOTB Merchant Custom Prices sets a price per merchant relation but does not express per-product discount-group logic. Branches share the main instance's prices and cannot change them ([SD-002](sd-002-dealer-hierarchy-branches.md)).

## Goals & Requirements

### Functional Requirements
- Dealer defines either a new dealer list price or discount groups that differ per customer.
- Discount groups apply per product (or product group) and per customer.
- A customer sees only the resolved prices of the dealer they selected as active.
- Branch prices equal main-instance prices; branches cannot change them.

### Non-Functional Requirements
- Correctness: 100% of prices shown match the resolved dealer/customer rule.
- Performance: dynamic price resolution must not degrade PLP/PDP within stated concurrency.

### Constraints
- Built on Spryker **Merchant Custom Prices** ([docs](https://docs.spryker.com/docs/pbc/all/price-management/latest/base-shop/merchant-custom-prices-feature-overview)).
- Invoice-only checkout; no PSP.

## Proposed Solution

### Overview
Use Merchant Custom Prices as the per-merchant-relation base, and add a **custom discount-group model** that resolves a customer's effective price from (dealer list price) × (applicable discount group for the product + customer). Resolution runs in the price-resolution flow so cart/checkout and PLP/PDP all use the same computed price.

### Key Components

| Component | Responsibility | Technology |
|-----------|----------------|------------|
| Merchant Custom Prices | Per-relation base price dimension | Spryker Price Management PBC |
| Discount-group model (custom) | Per-product/per-customer discount definitions | Custom Zed module (schema + facade) |
| Price resolver plugin | Compute effective customer price | Custom PriceProduct plugin |

### Integration Points
- **Internal:** PriceProduct dimension/resolver plugins; Merchant Relationship for the dealer↔customer link.
- **External:** IPS import feeds the dealer list prices (via [ADR-003](../09-architecture-decisions/adr-003-direct-s3-xml-import-not-proxied.md)).

### Data Model
> **TODO:** Define the discount-group schema (group → product/product-group, group → customer, discount value/type) and transfers. Owner: pricing team. TAD links a dedicated solution doc that must be reconciled here.

## Trade-offs & Considerations

### Advantages
- Reuses Merchant Custom Prices; discount logic isolated in one module.

### Disadvantages
- Custom pricing increases complexity and upgrade risk.
- If a business unit has multiple merchant relations, Spryker offers the **lowest** price OOTB — confirm this matches Daimler's expectation for multi-dealer customers.

### Risks
| Risk | Impact | Mitigation |
|------|--------|------------|
| Discount rules mis-resolve | High | Strong unit/functional test suite over resolver |
| Performance of dynamic resolution | Medium | Cache/pre-calc resolved prices in P&S where possible |

## Open Questions
- Exact discount-group cardinality (per product vs product group vs both)?
- Behaviour when customer belongs to multiple dealer pools with conflicting prices?

## Related Documentation
- **ADRs:** [ADR-001](../09-architecture-decisions/adr-001-merchant-relationship-for-dealer-customer-model.md)
- **SDs:** [SD-002](sd-002-dealer-hierarchy-branches.md)

---

*Corresponds to [arc42 Section 4](https://docs.arc42.org/section-4/)*
