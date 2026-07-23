# Cross-Cutting Concepts

Concepts that span the Scania POC. Only those that actually apply are covered; each is grounded in the
Spryker docs research and marked with its POC/Future stance.

## Authentication & Authorization

- **SSO only, no native login.** CIAM (My Scania) is the sole IdP. Spryker resolves/creates a
  `CustomerTransfer` on first SSO login via a **custom** `OauthCustomerAuthenticationStrategyPlugin`.
  The OOTB `CreateCustomerOauthCustomerAuthenticationStrategyPlugin` is **B2C-only** (no company
  context) and `AcceptOnly...` requires pre-provisioned accounts — neither fits B2B JIT provisioning,
  so a project strategy is required.
  [docs](https://docs.spryker.com/docs/pbc/all/oauth/latest/storefront-account-bootstrapping)
- **Authorization** uses the **Company Account** model: CIAM roles (6–8) map to Spryker **Company
  Roles & Permissions** for ACL.
  [docs](https://docs.spryker.com/docs/pbc/all/customer-relationship-management/latest/base-shop/company-account-feature-overview/company-user-roles-and-permissions-overview)
- **Back Office** access uses standard Spryker User Management (ACL roles/groups).
  [docs](https://docs.spryker.com/docs/pbc/all/user-management/latest/base-shop/user-and-rights-overview)

> **TODO:** the CIAM role → Company Role mapping table depends on the CIAM spec; start 1:1 with a POC
> subset. Registration is out of scope (done in CIAM).

## Internationalization & Multi-Store

- **Dynamic Multi-Store**: GB/PL/DE stores, ≤2 locales each (native + English), currencies GBP/PLN/EUR.
  Global catalog + one global price for POC. Store selected via `Store` header/switcher. Publish & Sync
  required for stores to appear.
  [docs](https://docs.spryker.com/docs/pbc/all/dynamic-multistore/latest/base-shop/dynamic-multistore-feature-overview)
- Currency/locale formatting is OOTB per store. See §7 for the store table and maturity note.

## Caching & Publish-and-Sync

- **No caching of external data for the POC.** Assets (FMAT), services (SECM) and details (PIM-SPISA)
  are fetched **live** on each request. This is an explicit POC trade-off (integration proof over
  performance); external latency directly affects UX with no SLA.
- Standard Redis (sessions/KV) and Publish & Sync remain in place for the OOTB shop parts.
- **Future:** asset caching/sync to reduce FMAT load and latency.

## Dynamic Service & Price Modelling

- Services do **not** exist in the Spryker catalog; they are fetched from SECM with a price. Modelling
  them as cart items with API-sourced prices is the central design area — see **SD-001** (options:
  virtual product on-the-fly / SSP service request / pre-synced catalog with dynamic price).
- **Price validity:** POC suggestion is to **lock the price at add-to-cart**; re-validation before
  checkout is an open client question.

## Configurable Product / External Configurator (Future)

- A service **wizard** could use Spryker **Configurable Product** with **PIM-SPISA as an external
  configurator**. Spryker interacts with an external configurator purely by passing parameters
  (customer + product context, `store_name`, return URL) on redirect out and receiving updated
  parameters back — an external configurator is a standalone web app on its own host.
  [docs](https://docs.spryker.com/docs/pbc/all/product-information-management/latest/base-shop/feature-overviews/configurable-product-feature-overview/configurable-product-feature-overview)
- Feasibility depends on PIM-SPISA's API capabilities — see **SD-002** (discovery).

## Error Handling (POC stance)

- **Deliberately minimal.** Customer-creation failure during SSO → exception (no graceful path). FMAT
  post-order calls are **fire-and-forget** (no retry/back-off). Robust, graceful error handling across
  all four integrations is **Future**.

## Order Side-Effects (OMS)

- Post-order FMAT notification(s) and the confirmation email are **OMS commands** bound to
  transitions/events — the documented pattern for post-order external side-effects. Timeouts/conditions
  rely on the periodic `oms:check-*` console commands running (scheduler).
  [docs](https://docs.spryker.com/docs/pbc/all/order-management-system/latest/base-shop/state-machine-cookbook/state-machine-cookbook-state-machine-fundamentals)
- No OMS customization beyond these commands for the POC.

## Security

- Auth via CIAM only; **no Spryker-native credentials** stored. Otherwise standard Spryker security.
- Invoice-only checkout means **no PCI/PSP surface** in the POC.

> **TODO:** outbound connectivity/allow-listing to the four external systems and secret management for
> the (TBD) API credentials must be defined once specs land.

---

*Corresponds to [arc42 Section 8](https://docs.arc42.org/section-8/)*
