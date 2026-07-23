# ADR-001: Authentication via CIAM SSO only (native login disabled)

## Status

**Accepted** (2026-07-23) — for the POC.

## Context

Scania requires that all shop users authenticate through **My Scania (CIAM)**, the corporate identity provider. Registration happens in CIAM, not in Spryker, and there must be **no Spryker-native credentials**. On first login a Spryker customer must be created automatically from the CIAM profile, and CIAM's 6–8 roles must map to Spryker Company Roles for authorization.

Spryker offers **Federated Authentication (OAuth2/OIDC)**, which is GA for the storefront and supports exactly this delegated-login model with just-in-time (JIT) account provisioning ([research §15](https://docs.spryker.com/docs/pbc/all/oauth/latest/federated-authentication)). However, two relevant capabilities are **not GA**: Glue/API-level SSO, and automatic claims→ACL-role mapping. Since the POC uses the **Yves storefront** (not headless Glue), storefront Federated Auth fits; role mapping must be implemented at project level.

## Decision

We will **disable native Spryker login and use CIAM as the sole authentication method** via Spryker Federated Authentication (Authorization Code flow) on the Yves storefront. On first login we will **JIT-provision** the Customer, Company, and Company User from CIAM claims, and map CIAM roles to Company Roles in a project-level module (ScaniaCiamAuth). For the POC, customer-creation failure will **fail fast with an exception** rather than degrade gracefully.

## Consequences

### Positive
- Single corporate identity authority; no Spryker passwords to manage or secure.
- Uses a GA, storefront-supported Spryker feature; minimal custom auth code beyond the plugin chain.
- JIT provisioning removes any separate onboarding step.

### Negative
- Claims→role mapping is custom (not GA) — extra project code and a dependency on the CIAM role spec (Open Q #3).
- Fail-fast is deliberately un-friendly; graceful handling is deferred to Future.
- Spryker session is independent of the IdP (no continuous validation / federated logout — a Federated Auth limitation).
- Blocked until the CIAM provider and payload spec are supplied (Open Q #1, #2).

---

*Corresponds to [arc42 Section 9](https://docs.arc42.org/section-9/)*
