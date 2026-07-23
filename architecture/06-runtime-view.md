# Runtime View

Key runtime scenarios for the Scania Service Sales POC. Step order for auth and OMS is reconciled against the shared Spryker research (Federated Auth Authorization Code flow; OMS fire-and-forget via cron).

## Scenario 1 — CIAM SSO Login + JIT Customer Creation

Native login is disabled; the customer authenticates through My Scania and is provisioned into Spryker on first login.

**See diagram:** [CIAM SSO Login](diagrams/sequence/ciam-sso-login.mmd)

**Key Steps:**
1. Customer clicks "Log in with My Scania"; Yves redirects to CIAM using the OAuth2 Authorization Code flow.
2. Customer authenticates at CIAM; CIAM redirects back with an authorization code.
3. Yves exchanges the code for tokens and reads the claims (email, name, company, country, roles).
4. The Federated Auth plugin chain looks up the identity by `(provider, external_id)` / email.
5. If found, the existing customer + company user is used. If not, **JIT provisioning** creates the Customer, Company, and Company User from claims, and maps CIAM roles to Company Roles (ACL).
6. **POC fail-fast:** if creation fails, an exception is thrown (graceful handling is Future — ADR-001).
7. A Spryker session is established, independent of the IdP (no continuous IdP validation — a documented Federated Auth limitation, [research §15](https://docs.spryker.com/docs/pbc/all/oauth/latest/federated-authentication)).

> Claims→role mapping is **not** a GA Spryker capability; it is implemented at project level in ScaniaCiamAuth. See [research §15](https://docs.spryker.com/docs/pbc/all/oauth/latest/federated-authentication).

## Scenario 2 — Asset Selection (FMAT live fetch)

**See diagram:** [Asset Selection (FMAT)](diagrams/sequence/asset-selection-fmat.mmd)

**Key Steps:**
1. Customer opens "My Fleet"; Yves requests a page of assets from the ScaniaFmatAsset Zed module.
2. The FMAT Client performs a synchronous, paginated REST call to FMAT.
3. Vehicles are mapped to transfers and rendered as a paginated list — **no caching** (ADR-002).
4. The customer selects a vehicle, which becomes the context for the service catalog.
5. If FMAT is unavailable, the POC surfaces an exception (graceful error handling is Future).

## Scenario 3 — Service Browse + Select (SECM + PIM-SPISA)

**See diagram:** [Service Browse & Select](diagrams/sequence/service-browse-select.mmd)

**Key Steps:**
1. For the selected vehicle, Yves requests compatible services from ScaniaSecmService.
2. SECM returns up to ~100 compatible services **with prices**; only compatible services are shown.
3. On opening a service PDP, ScaniaSpisaDetail fetches description/images (and any config payload) from PIM-SPISA.
4. Yves merges the SECM price with the SPISA detail to render the PDP.
5. Future: a 1–2 step wizard via Configurable Product (SD-002) and optional SSP service date/time on the PDP.

## Scenario 4 — Add Dynamic Service to Cart → Invoice Checkout

The flagship flow — a service that does not exist in the catalog, priced from SECM, added as a dynamic line item and purchased on invoice.

**See diagram:** [Dynamic Service Cart → Checkout](diagrams/sequence/dynamic-service-cart-checkout.mmd)

**Key Steps:**
1. Customer adds a service (bound to a vehicle) to the cart.
2. ScaniaDynamicService reads the unit price from SECM and builds a dynamic line item carrying service ref, vehicle ref and the **locked SECM price** (ADR-004 — modelling is still Proposed).
3. The quote persists so the cart survives sessions; regular products and multiple assets may coexist.
4. Customer proceeds to checkout — **invoice only, no PSP** (ADR-003) — and clicks Place Order (no approval workflow).
5. A sales order is created; the customer receives the OOTB order-confirmation email.
6. OMS then fires the post-order FMAT notification(s) asynchronously (Scenario 5).

> **Open question (carried):** price re-validation — lock at add-to-cart (POC suggestion) vs re-validate at checkout (Open Q #18). The diagram reflects the lock option.

## Scenario 5 — OMS Post-Order FMAT Notification (2× async, fire-and-forget)

**See diagram:** [OMS Post-Order FMAT](diagrams/sequence/oms-post-order-fmat.mmd)

**Key Steps:**
1. On order placement the order enters its initial OMS state.
2. A transition with its `<event>` element omitted makes the step **non-blocking**: Zed drives the model via cron, and the customer request is not held ([research §12](https://docs.spryker.com/docs/pbc/all/order-management-system/latest/base-shop/state-machine-cookbook/state-machine-cookbook-state-machine-fundamentals)).
3. The **Order-Created Notify** OMS command (POC) POSTs to FMAT.
4. On success the item advances; on failure it stays in the source state and is retried on the next cron run.
5. A second command — **Payment + Activation Notify** (Future) — follows the same fire-and-forget pattern.

---

*Corresponds to [arc42 Section 6](https://docs.arc42.org/section-6/)*
