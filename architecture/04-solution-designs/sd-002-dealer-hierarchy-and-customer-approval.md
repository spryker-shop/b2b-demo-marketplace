# SD-002: Dealer hierarchy & customer approval

## Metadata

| Field | Value |
|-------|-------|
| **Status** | Draft |
| **Date** | 2026-07-23 |
| **Author(s)** | Architecture writer (from Daimler TAD) |
| **Stakeholders** | Solution architect, Daimler dealer/market business owners |

## Problem Statement

Daimler's 3-tier model needs a **dealer branch hierarchy** (main instance + subordinate branches, each with its own account; branch prices equal main-instance prices; a registered customer can be added by other branches without a new request; main-instance users can log into all branches; different roles within branches), and a **customer approval flow** (customer sends a registration request to a dealer to enter its pool; dealer has "new" and "approved" MP overview pages; reject/accept + assign a merchant-specific customer ID; dealer offers prices/delivery costs/methods per approved customer; customer selects an active dealer and sees only that dealer's prices/products). This must be built on Spryker's merchant primitives, not a bespoke relationship model ([ADR-003](../09-architecture-decisions/adr-003-merchant-relationship-for-dealer-customer-approval.md)).

## Goals & Requirements

### Functional Requirements
- Dealer = merchant; branches = subordinate merchants under a main instance sharing its prices.
- Customer registration-request → dealer approve/reject; merchant-specific customer ID.
- Merchant-specific prices + delivery costs/methods per approved customer.
- Per-customer product visibility; customer selects active dealer.
- Merchant assignment during business-unit create/edit (custom step).

### Non-Functional Requirements
- Pricing calculation performant with discount-group logic per product+customer.
- Aligns with core so upgrades stay cheap.

### Constraints
- Historical migration: customer/dealer relations + rebate classes seed this model.
- Branches cannot change prices (inherit main instance).

## Proposed Solution

### Overview

Model dealers as **merchants** and the dealer↔customer partnership as a **merchant relationship** (Merchant B2B Contract), established via the storefront **relation request → dealer approval** flow. Per-customer visibility uses **Merchant Product Restrictions**; buyer-specific prices attach to the merchant relation. Branch hierarchy, shared-price inheritance, cross-branch add-without-request, cross-branch login, and the merchant-specific customer ID are the **custom** additions layered on top.

### Architecture

**See diagram:** [Registration → Approval → Order](../diagrams/sequence/customer-registration-approval-order.mmd) · **ERD:** [Dealer hierarchy ERD](../diagrams/erd/dealer-hierarchy-erd.puml)

### Key Components

| Component | Responsibility | Technology |
|-----------|----------------|------------|
| MerchantB2bContracts (core) | Dealer↔customer relation + request/approval | Spryker Merchant |
| MerchantProductRestriction (core) | Per-customer product lists | Spryker |
| `DealerBranchHierarchy` (custom) | Main instance + branches, price inheritance, cross-branch access | Zed module |
| `CustomerApprovalRequest` (custom) | MP new/approved pages, reject/accept, merchant-specific ID | Zed + MP |
| `MerchantSpecificPrice` (custom) | Dealer list price + discount groups (dynamic) | Zed + Price calculator |

### Integration Points

- **Internal:** Merchant, MerchantRelationship, PriceProductMerchantRelationship, MerchantProductRestriction facades/plugins; CompanyBusinessUnit for buyer side; business-unit create/edit extension point for merchant assignment.
- **External:** RetailNet (dealer org seeding), CRISP (customer org), Dealer Locator (registration linkout).
- **Migration:** legacy relations + rebate classes → merchant relations + price scope.

### Data Model

**Core tables used** (see ERD):
- `spy_merchant` — dealer; branches reference a parent merchant.
- `spy_merchant_relationship` (+ `_to_company_business_unit`) — dealer↔buyer(business unit) relation.
- `spy_price_product_merchant_relationship` — buyer-specific prices bound to the relation.
- `spy_company` / `spy_company_business_unit` / `spy_company_user` — customer org structure.

**Custom tables (`pyz_*`):**

```
pyz_dealer_branch
  id_dealer_branch         INTEGER  PK
  fk_merchant              INTEGER  FK -> spy_merchant   (this branch)
  fk_parent_merchant       INTEGER  FK -> spy_merchant   (main instance; null if main)
  fk_store                 INTEGER  FK -> spy_store      (market)
  is_main_instance         BOOLEAN

pyz_customer_approval_request
  id_customer_approval_request  INTEGER  PK
  fk_merchant                   INTEGER  FK -> spy_merchant
  fk_company_business_unit      INTEGER  FK -> spy_company_business_unit
  status                        VARCHAR      -- pending | approved | rejected
  merchant_specific_customer_id VARCHAR      (nullable until approved)
  message                       TEXT
  created_at                    TIMESTAMP
```

**Transfers:** `DealerBranchTransfer`, `CustomerApprovalRequestTransfer`; reuse `MerchantRelationshipTransfer`, `MerchantProductRestrictionTransfer`, `PriceProductTransfer`.

**Ownership / read-write:**
- `DealerBranchHierarchy` **owns** `pyz_dealer_branch`; resolves effective prices (branch → main instance) **read-only** on `spy_price_product_merchant_relationship`.
- `CustomerApprovalRequest` **owns** `pyz_customer_approval_request`; on approval it **creates** the merchant relation (write via MerchantRelationship facade).
- `MerchantSpecificPrice` **writes** `spy_price_product_merchant_relationship` for approved customers.

## Implementation Plan

### Phases
1. **Phase 1** — Merchant relationship + request/approval + MP new/approved pages + merchant-specific ID.
2. **Phase 2** — Branch hierarchy (parent/child merchants), price inheritance, cross-branch add/login.
3. **Phase 3** — Discount-group pricing + migration of legacy relations/rebate classes.

### Dependencies
- **Spryker modules:** Merchant, MerchantRelationship, PriceProductMerchantRelationship, MerchantProductRestriction, Company, CompanyBusinessUnit.
- **External:** RetailNet/CRISP feeds; migration data set.

### Rollout Strategy & Cost
- **Approach:** phased per market.
- **Estimated effort:** DealerBranchHierarchy **L**, CustomerApprovalRequest **M**, MerchantSpecificPrice **L** (§5).
- **Risk mitigation:** validate migrated relations/prices on Stage before enabling a market.

## Trade-offs & Considerations

### Advantages
- Confirmed Spryker primitives for relation + restrictions + relation-scoped prices.

### Disadvantages
- Branch hierarchy + price inheritance + cross-branch behaviours are custom.
- Discount-group logic complexity (TAD referenced an external doc — §11 TODO).

### Risks

| Risk | Impact | Mitigation |
|------|--------|------------|
| Price inheritance edge cases (branch vs main) | Wrong prices | Single resolution path (branch → main), regression tests |
| External-search unaware of restrictions | Customer sees disallowed parts | Reconcile results ([ADR-002](../09-architecture-decisions/adr-002-external-catalog-search-delegation.md)) |
| Migration mismatch | Wrong pools/prices at launch | Validate on Stage; reconcile counts |

## Alternatives Considered

### Alternative 1: Custom relationship model (no Merchant B2B Contracts)
- **Pros:** Full control.
- **Cons:** Reinvents request/approval, buyer-specific prices, restrictions; upgrade cost.
- **Why not chosen:** Core covers most of it (ADR-003).

### Alternative 2: Company-account-only (no merchants)
- **Pros:** Simpler.
- **Cons:** No seller/dealer entity, no relation-scoped prices/restrictions.
- **Why not chosen:** Dealers must be sellers with own pricing/pools.

## Open Questions
- Discount-group pricing detail (external Google Doc referenced by TAD) — > **TODO** obtain + fold in. Owner: solution architect.
- Cross-branch "add without request" exact rules across markets?

## Related Documentation
- **ADRs:** [ADR-003](../09-architecture-decisions/adr-003-merchant-relationship-for-dealer-customer-approval.md), [ADR-004](../09-architecture-decisions/adr-004-persistence-acl-for-per-market-isolation.md)
- **External:** [Merchant B2B Contracts](https://docs.spryker.com/docs/pbc/all/merchant-management/latest/marketplace/marketplace-merchant-b2b-contracts-and-contract-requests-feature-overview), [Merchant Product Restrictions](https://docs.spryker.com/docs/pbc/all/merchant-management/latest/base-shop/merchant-product-restrictions-feature-overview/merchant-product-restrictions-feature-overview)

---

*Corresponds to [arc42 Section 4](https://docs.arc42.org/section-4/)*
