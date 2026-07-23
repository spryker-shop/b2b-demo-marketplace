# SD-002: Dealer Hierarchy — Main Instance & Subordinate Branches

## Metadata

| Field | Value |
|-------|-------|
| **Status** | Draft |
| **Date** | 2024-06-04 |
| **Author(s)** | Daimler Truck project team |
| **Stakeholders** | Dealers, Markets, HQ |

## Problem Statement

A dealer is not a single flat merchant: it is a **main instance with subordinate branches**, each with its own account. Branch prices equal main-instance prices and branches cannot change them. A customer registered for one branch can be added by other branches **without a new request**. Certain main-instance users can log into **all** branches. Different roles exist within branches (view-only orders; dealer-specific customer management).

## Goals & Requirements

### Functional Requirements
- Model main instance + subordinate branches, each with own account.
- Branches inherit (shared) prices from the main instance; branches cannot change prices.
- A customer approved at one branch is reusable across sibling branches without re-request.
- Designated main-instance users can log into all branches.
- Branch-level roles: view-only orders, dealer-specific customer management.

### Non-Functional Requirements
- Isolation: a branch user only operates within its permitted scope.
- Consistency: shared prices remain identical across branches.

### Constraints
- Built on **BO User roles & groups** best practice ([docs](https://docs.spryker.com/docs/pbc/all/user-management/latest/base-shop/manage-in-the-back-office/best-practices-manage-users-and-their-permissions-with-roles-and-groups)) — assign roles to groups, users to groups.
- Dealer is a Spryker merchant ([ADR-001](../09-architecture-decisions/adr-001-merchant-relationship-for-dealer-customer-model.md)); pricing per [SD-001](sd-001-dealer-prices-discount-groups.md).

## Proposed Solution

### Overview
Represent the main instance and its branches within the merchant model, with a **custom hierarchy restriction** governing login scope and role capability. Prices are owned by the main instance and shared read-only to branches. Customer approvals are shared across sibling branches. Cross-branch login is granted to designated main-instance users via role/group assignment plus a custom login-scope check.

### Key Components

| Component | Responsibility | Technology |
|-----------|----------------|------------|
| Merchant hierarchy (custom) | Main instance ↔ branch relationship | Custom Zed module |
| BO roles & groups | Branch-level capability (view-only / customer mgmt) | Spryker User Management (ACL) |
| Cross-branch login check | Allow main-instance users into all branches | Custom auth/scope plugin |
| Shared-price link | Branches read main-instance prices | Reuse of [SD-001](sd-001-dealer-prices-discount-groups.md) resolution |

### Integration Points
- **Internal:** ACL roles/groups; Merchant Relationship (customer approval reused across branches); price resolver from SD-001.
- **External:** RetailNet supplies dealer (merchant) organisation info.

### Data Model
> **TODO:** Define the branch hierarchy schema (main-instance ↔ branch, shared-price ownership, cross-branch login grants) and the branch-role definitions. Owner: dealer-management team.

## Trade-offs & Considerations

### Advantages
- Leverages mature ACL roles/groups; hierarchy contained in one module.
- Shared-price ownership prevents branch price drift by construction.

### Disadvantages
- Cross-branch login and cross-branch customer reuse are custom behaviours not OOTB.

### Risks
| Risk | Impact | Mitigation |
|------|--------|------------|
| Branch can change/see wrong prices | High | Read-only shared-price link + tests |
| Login-scope errors (wrong branch) | High | Explicit scope check + audit |

## Open Questions
- Depth of hierarchy — only one branch level, or deeper?
- Which main-instance user roles get all-branch login?

## Related Documentation
- **ADRs:** [ADR-001](../09-architecture-decisions/adr-001-merchant-relationship-for-dealer-customer-model.md)
- **SDs:** [SD-001](sd-001-dealer-prices-discount-groups.md)

---

*Corresponds to [arc42 Section 4](https://docs.arc42.org/section-4/)*
