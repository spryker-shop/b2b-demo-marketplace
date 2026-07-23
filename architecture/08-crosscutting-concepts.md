# Cross-Cutting Concepts

Principles and patterns that span multiple building blocks of the Scania Service Sales POC. Only concepts that actually apply to the POC are documented.

## Authentication & Authorization

- **Authentication — CIAM only.** Native Spryker login is disabled. Login is delegated to My Scania via Spryker **Federated Authentication (OAuth2/OIDC)**, which is GA for the storefront ([research §15](https://docs.spryker.com/docs/pbc/all/oauth/latest/federated-authentication)). The Authorization Code flow is used; only the `email` claim is strictly required, with name/company/country/roles consumed when present. The Spryker session is independent of the IdP once established (no continuous validation / federated logout — a documented Federated Auth limitation).
- **JIT provisioning.** On first login, if no matching customer exists, the customer + company + company user are created from claims. POC behaviour is **fail-fast**: creation failure raises an exception (ADR-001).
- **Authorization — Company Account roles.** CIAM roles (6–8) map to Spryker **Company Roles & permissions** for ACL ([research §7](https://docs.spryker.com/docs/pbc/all/customer-relationship-management/latest/base-shop/company-account-feature-overview/company-user-roles-and-permissions-overview)). **Automatic claims→role mapping is not a GA Spryker capability** — it is implemented at project level in ScaniaCiamAuth. Start with a simple 1:1 mapping for the POC subset (see Open Q #3).

## Dynamic Data from External Systems (no local ownership)

The defining cross-cutting concept: assets (FMAT), services & prices (SECM), and details (PIM-SPISA) are **not owned by Spryker**. They are fetched **live** through Client-layer HTTP adapters and mapped to transfers per request (no caching — ADR-002). This keeps the POC simple and always-fresh at the cost of latency and availability coupling. The one place external data is **captured** in Spryker is the **dynamic service line item**, where the SECM price is **locked** into the cart/quote/order (SD-001, ADR-004).

## Error Handling

POC policy: **exceptions are acceptable; graceful error handling is Future.** External-call failures (CIAM, FMAT, SECM, PIM-SPISA) surface as exceptions rather than user-friendly fallbacks. The one deliberately resilient path is the **OMS fire-and-forget** post-order notification, which retries safely: a failing command leaves the order item in its source state for re-processing on the next cron run ([research §12](https://docs.spryker.com/docs/pbc/all/order-management-system/latest/base-shop/state-machine-cookbook/state-machine-cookbook-state-machine-fundamentals)).

## Internationalisation & Multi-Store

Dynamic Multistore (GA since 202410.0) provides one store per country (GB/PL/DE for POC) within one region. Locales and currencies are per store; the service catalog and price are global for the POC. DMS changes store-context handling in Back Office vs Storefront and console `--store` semantics — see [section 7](07-deployment-view.md).

## Caching (Redis + Publish & Sync)

Standard Spryker Redis/OpenSearch and Publish & Sync are present but lightly used: services are **not** published into the catalog for the POC, so search/storage carry little Scania-specific data. Sessions and OOTB storefront data use Redis as normal. **Assets are explicitly not cached** (ADR-002); asset caching is a named Future workstream.

## Security

- All external calls over HTTPS; credentials/tokens for CIAM/FMAT/SECM/PIM-SPISA held in environment configuration (`config/Shared/config_default.php` keys), never in code.
- CIAM is the single identity authority; there are no Spryker-native passwords to protect.
- Standard Spryker security posture otherwise; hardening beyond OOTB is out of POC scope.

## Observability & Logging

Standard Spryker logging. No custom APM/observability integration is in POC scope; external-call latency and failures are visible via application logs. Formalised observability is a Future concern alongside graceful error handling.

---

*Corresponds to [arc42 Section 8](https://docs.arc42.org/section-8/)*
