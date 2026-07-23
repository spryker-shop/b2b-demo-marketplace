# ADR-002: Isolate market data in Back Office via Persistence ACL pattern

## Status

**Proposed** (2024-06-04)

## Context

Each of the 22 markets manages its own market-specific settings in the Back Office and must see **only its own store's data**; only HQ manages all markets and needs the cross-market monitoring view. Products remain global/shared. This requires row-level (store-scoped) restriction of what a BO user can read and write, on top of Spryker's standard ACL (roles/groups/privileges/resources).

## Decision

Restrict Back Office data access by the BO user's assigned market (store) using a **Persistence ACL pattern**, built on the ACL model ([User Management](https://docs.spryker.com/docs/pbc/all/user-management/latest/user-management)). If a discrete row-level Persistence-ACL mechanism is not available/suitable in the installed release, fall back to a **lighter custom controller restriction** filtering grids/queries by the user's store assignment. HQ users bypass the filter to see all markets.

## Consequences

### Positive
- Enforces the multi-market isolation requirement centrally rather than per controller.
- Builds on Spryker's mature ACL model (roles → groups → users best practice).

### Negative
- **Uncertain named feature:** public docs do not surface a discrete "Persistence ACL" page for row-level store/market scoping of BO users — the exact mechanism must be confirmed against installed modules (see [Section 11](../11-risks-and-technical-debt.md) technical debt). Status stays **Proposed** until confirmed.
- A custom controller-level fallback risks inconsistency if new BO grids are added without applying the filter.

---

*Corresponds to [arc42 Section 9](https://docs.arc42.org/section-9/)*
