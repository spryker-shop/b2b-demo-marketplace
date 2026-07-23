# Runtime View

The three runtime scenarios that carry the POC's end-to-end story: SSO login with JIT customer
creation, the asset → catalog → detail → cart browse-and-add flow, and invoice checkout with the async
FMAT notification + confirmation email. Each follows canonical Spryker flow shapes (OAuth customer
strategy, cart plugins, OMS commands) confirmed against the docs research.

## Scenario 1 — SSO login + JIT customer create + role mapping

**See diagram:** [SSO Login (JIT customer)](diagrams/sequence/sso-login-jit-customer.mmd)

**Key Steps:**
1. Unauthenticated fleet customer is redirected to CIAM (My Scania) — no Spryker-native login exists.
2. Customer authenticates in CIAM; callback returns token + profile (name, email, company, country,
   roles, products).
3. A **custom** `OauthCustomerAuthenticationStrategyPlugin` resolves the customer by email.
4. If not found: create customer + company relation, then map CIAM roles → Spryker **Company Roles**
   (ACL). **POC:** on failure throw an exception (no graceful handling).
5. If found: load existing company context + roles.
6. Session is established with the full B2B context; redirect to asset selection.

> Grounded in Spryker OAuth storefront account bootstrapping — the OOTB auto-create strategy is
> B2C-only (no company context), so a custom strategy is required.
> [docs](https://docs.spryker.com/docs/pbc/all/oauth/latest/storefront-account-bootstrapping)
> **TODO:** role-mapping table depends on the CIAM role spec (6–8 roles). Start with a simple 1:1
> subset for the POC.

## Scenario 2 — Asset selection → service catalog (SECM) → service detail (PIM-SPISA) → add to cart

**See diagram:** [Asset → Service → Cart](diagrams/sequence/asset-to-service-to-cart.mmd)

**Key Steps:**
1. Asset page loads → `FmatAssetClient` fetches the customer's vehicles from FMAT (live, paginated).
2. Customer selects a vehicle → `SecmServiceCatalogClient` fetches compatible services + prices (max
   ~100).
3. Customer opens a service → `SpisaServiceDetailClient` fetches description + images from PIM-SPISA.
4. Customer adds the service (for vehicle X) to the cart as a **dynamic service item** with a synthesised
   SKU and the SECM price.
5. **POC suggestion:** lock the price at add-to-cart; re-validation before checkout is a client
   question (see SD-001 / ADR).

> **TODO:** the exact cart modelling (virtual product on-the-fly vs SSP-based vs pre-synced catalog)
> is a discovery area — see **SD-001**. Quantity-per-service-per-asset (always 1 vs changeable) is an
> open client question.

## Scenario 3 — Checkout (invoice) → order → async FMAT post-order + confirmation email

**See diagram:** [Checkout + FMAT notifications](diagrams/sequence/checkout-order-fmat-notifications.mmd)

**Key Steps:**
1. Customer proceeds to checkout; payment is **Invoice only** (no PSP), single "Place Order" button,
   **no approval workflow**.
2. Order is validated and placed; OMS moves the order to its initial state (OOTB statuses, no
   customization).
3. **Async (fire-and-forget):** an OMS command posts the *order-created* notification to FMAT.
4. **In parallel:** an OMS command sends the OOTB order-confirmation email via SMTP.
5. **Future:** a second async OMS command notifies FMAT of *payment + activation date*.

> Post-order external calls and the confirmation email are implemented as **OMS commands** bound to
> transitions — the documented Spryker pattern for post-order side-effects.
> [docs](https://docs.spryker.com/docs/pbc/all/order-management-system/latest/base-shop/state-machine-cookbook/state-machine-cookbook-state-machine-fundamentals)
> **TODO:** POC is fire-and-forget — no retry/back-off/error handling. Robust delivery is Future.

---

*Corresponds to [arc42 Section 6](https://docs.arc42.org/section-6/)*
