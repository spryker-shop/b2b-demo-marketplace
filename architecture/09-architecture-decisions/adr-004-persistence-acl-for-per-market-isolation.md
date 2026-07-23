# ADR-004: Persistence ACL for per-market Back Office data isolation

## Status

**Accepted** (2026-07-23)

> Accepted with an explicit caveat: the Propel-only limitation is a real constraint that must be respected in custom queries (defence-in-depth below). If a market-scoped read must run outside Propel, that path needs a controller-level guard.

## Context

Each of the 22 markets manages its own market-specific data in the Back Office and **must see only its own store's data**; only HQ manages all markets. Two documented options exist (TAD Challenges): **Persistence ACL** (DB-entity-level authorization enforced in the Persistence layer via Propel behaviors/hooks, with rules/scopes and inheritance — [research §4](https://docs.spryker.com/docs/pbc/all/user-management/latest/marketplace/persistence-acl-feature-overview/persistence-acl-feature-overview)) or a **less-effort custom controller restriction** based on the BO user's store assignment. Persistence ACL is a confirmed, real feature and documents a directly analogous use case ("split products by stores"). Its key limitation: **it only filters Propel-API queries** — queries outside Propel are not handled.

## Decision

We will **use Persistence ACL for per-market Back Office data isolation**, configuring market (store) scopes so a market BO user only sees their store's records while HQ sees all. As **defence-in-depth** against the Propel-only limitation, we will keep market-scoped Back Office reads on the Propel API, audit custom/raw queries for market leakage, and add a controller-level store guard where a non-Propel path is unavoidable. Back Office screen/action access is governed separately by [User Management + ACL roles](https://docs.spryker.com/docs/pbc/all/user-management/latest/base-shop/user-and-rights-overview) (research §18), including the dealer-branch role restrictions.

## Consequences

### Positive
- Row-level isolation enforced centrally in the Persistence layer, not scattered across controllers — less chance of a screen forgetting to filter.
- Confirmed Spryker feature with a matching documented use case; supports segment/inherited/composite scopes for related entities.
- Cleanly separates *which records* (Persistence ACL) from *which screens/actions* (BO roles).

### Negative
- **Propel-ORM only** — any raw/non-Propel query bypasses isolation; requires discipline + audit (the caveat above).
- Configuration effort across many ACL-protected entities is non-trivial (sized **L** in §5).
- Extra query joins from ACL rewriting add some overhead on large Back Office lists (mitigated by segment scopes, low perf impact per docs).

---

*Corresponds to [arc42 Section 9](https://docs.arc42.org/section-9/)*
