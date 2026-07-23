# ADR-001: CIAM (SSO) is the Sole Authentication Method

## Status

**Accepted (for POC)** (2026-07-23)

## Context

Identity for Scania fleet customers is owned by **CIAM (My Scania)**. The TAD mandates SSO via CIAM as
the sole authentication method, with **no Spryker-native login**, registration handled entirely in
CIAM, and **just-in-time (JIT)** creation of the Spryker customer on first login. CIAM returns name,
email, company, country, roles and products.

Spryker resolves/creates a `CustomerTransfer` on SSO login via an
`OauthCustomerAuthenticationStrategyPluginInterface` strategy. The two OOTB strategies are unsuitable:
`AcceptOnly...` requires the customer to already exist, and `CreateCustomer...` is **B2C-only** — it
creates a bare customer with **no company context**, which B2B needs.
[docs](https://docs.spryker.com/docs/pbc/all/oauth/latest/storefront-account-bootstrapping)

## Decision

We will make **CIAM SSO the only authentication path** and implement a **custom OAuth customer
authentication strategy plugin** that, on first login, creates the customer **and** establishes the B2B
company context, then maps CIAM roles to Spryker **Company Roles** for ACL. No Spryker-native login or
registration is exposed. For the POC, a customer-creation failure results in an exception (no graceful
fallback).

## Consequences

### Positive
- Single source of identity (CIAM); no duplicated credentials in Spryker; no PCI-style credential store.
- Company context and roles are correct from the first authenticated request.
- Clean extension point — the strategy plugin isolates all SSO/JIT logic.

### Negative
- Requires custom development (no OOTB strategy fits B2B JIT).
- Fully dependent on the (still **TBD**) CIAM spec — payload, role model, and protocol.
- POC has no graceful handling of SSO/creation failures (accepted debt; see §11).
- Role mapping starts as a simplified 1:1 POC subset; full 6–8 role fidelity is deferred.

---

*Corresponds to [arc42 Section 9](https://docs.arc42.org/section-9/)*
