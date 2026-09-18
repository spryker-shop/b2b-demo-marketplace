# PRD — MCP Commerce Server for the Storefront API

| | |
|---|---|
| **Feature name** | MCP Commerce Server |
| **Target branch / namespace** | `master-demo` / `Demo` |
| **Status** | Draft for implementation |
| **Date** | 2026-08-23 |
| **Code references** | [mcp-commerce-server.refs.md](mcp-commerce-server.refs.md) |

## Research header

Grounded in the following verified facts about this installation (no assumed behavior):

- **Reference model** — the public Silpo AI Factory MCP documentation (`https://ai-factory.silpo.ua/docs/mcp`) describes a Streamable HTTP MCP server at a single `/mcp` endpoint, an OAuth 2.1 flow (401 → `/.well-known/oauth-authorization-server` → Dynamic Client Registration at `POST /register` → PKCE → `/authorize` → MCP token), and the explicit security property that the AI client never sees the guest's internal JWT. This PRD adopts that flow shape and that security property, and scales the tool surface down from Silpo's 39 tools to a minimal happy-path set.
- **Runtime** — this project runs Symfony 6.4 with API Platform 4.3. API Platform's own MCP server component requires a newer Symfony baseline and is therefore **not** available here; the MCP transport is implemented as a project-level component within the Storefront API application.
- **Host application** — the feature targets the **Storefront API** application (`glue.eu.spryker.local`), not the Backend API. That application is a full Symfony application with its own bundle registration, route loading, and autowired service configuration, so additional routes and controllers can be served alongside the API Platform resources.
- **Existing commerce surface (reused, not rebuilt)** — the Storefront API already exposes generated resources for catalog search, carts, cart items, checkout, checkout data, and customer access tokens. Every MCP tool in this PRD maps onto behavior that already exists and is already authorized.
- **Existing auth surface** — the Storefront API authenticates Bearer tokens through the API Platform security layer against the Oauth module's local JWT validation. The OAuth persistence already stores registered clients with a redirect URI and a confidential-client flag. The underlying OAuth library that ships with the project supports the authorization-code grant with PKCE, but the platform currently wires only the password and refresh-token grants — enabling authorization-code + PKCE is the principal new capability.
- **Demo feature conventions** — demo-only features on this branch live in the `Demo` namespace and are toggled from Back Office Configuration Management via a `data/configuration/*.configuration.yml` feature descriptor. This feature follows both conventions. The `Demo` namespace has no Glue layer today; this feature introduces one.

---

## 1. Background

AI assistants (Claude, ChatGPT, Copilot-style agents, and any Model Context Protocol client) are becoming a primary surface through which buyers discover products and place orders. Today a B2B buyer using such an assistant cannot transact with this shop at all: the assistant has no machine-usable, self-describing entry point, and no safe way to act on the buyer's behalf.

The Storefront API already exposes everything needed to search the catalog, build a cart, and place an order for an authenticated customer. What is missing is a **protocol adapter and an authorization flow** that let an untrusted third-party AI client use that capability without ever holding the customer's shop credentials or the customer's shop session token.

Two constraints make this non-trivial:

1. **The transport cannot be borrowed.** API Platform's own MCP server component requires a newer Symfony baseline than this project runs, so the Streamable HTTP transport must be provided by the project itself.
2. **The AI client must never see the customer's shop token.** An AI client is third-party software running outside our infrastructure. If it held the customer's storefront JWT it would hold the customer's full account. The MCP token issued to the AI client must therefore be a distinct, independently revocable credential, with the customer's shop token minted and held only server-side.

The reference implementation for this shape is the public Silpo AI Factory MCP server, which solves the same problem for a grocery retailer. This feature delivers the equivalent for this B2B demo shop, deliberately scoped to a **minimum viable happy path** — discover, add to cart, check out — rather than a complete tool catalogue. The goal is a credible, demonstrable "order placed by an AI assistant" flow, not API surface parity.

## 2. Goals

1. **An MCP client can complete an unassisted end-to-end purchase.** From a cold start with no credentials, an off-the-shelf MCP client can discover the server, authorize a customer, search the catalog, add an item to a cart, and place an order — in **≤ 6 tool calls** after authorization, with **zero** manual steps outside the browser login.
2. **Authorization is standards-compliant and client-agnostic.** The server implements OAuth 2.1 discovery, Dynamic Client Registration, and authorization-code-with-PKCE such that **≥ 2 independent MCP client implementations** connect successfully without server-side per-client configuration.
3. **The customer's shop credential never leaves our infrastructure.** In **100%** of MCP responses and error payloads, the customer's storefront access token and refresh token are absent. Verified by automated assertion, not inspection.
4. **Commerce behavior is reused, not reimplemented.** **100%** of catalog, cart, and checkout operations are served through the existing Storefront API resources, with **zero** new business-logic or persistence modules for cart/checkout.
5. **The feature is demo-safe and reversible.** The MCP server can be disabled from Back Office Configuration Management, and when disabled **100%** of MCP and OAuth-metadata endpoints return a non-functional response within **1** configuration change and **no** code deployment.

---

## 3. User Stories

### Story 1 — Discovering the server and its authorization requirements

**Story:** As a Customer, I want my AI assistant to discover how to authorize against the shop automatically when it is refused access, so that I do not have to configure endpoints, client IDs, or secrets by hand.

**Actor:** Customer

**Affected endpoints:** `http://glue.eu.spryker.local/mcp` (greenfield), `http://glue.eu.spryker.local/.well-known/oauth-authorization-server` (greenfield), `http://glue.eu.spryker.local/.well-known/oauth-protected-resource` (greenfield)

#### Acceptance criteria

```gherkin
Scenario: Unauthenticated MCP request is refused with a discovery pointer
  Given the MCP Commerce Server feature is enabled
  When an MCP client sends a tool call to /mcp without an Authorization header
  Then the response status is 401
  And the WWW-Authenticate header names the protected-resource metadata URL
```

```gherkin
Scenario: Authorization server metadata is published
  Given the MCP Commerce Server feature is enabled
  When a client requests /.well-known/oauth-authorization-server
  Then the response status is 200
  And the document lists the authorization, token, and registration endpoint URLs
  And the document declares "authorization_code" and "S256" as supported
```

```gherkin
Scenario: Protected resource metadata points back to the authorization server
  Given the MCP Commerce Server feature is enabled
  When a client requests /.well-known/oauth-protected-resource
  Then the response status is 200
  And the document names /mcp as the protected resource
  And the document names the authorization server issuer URL
```

```gherkin
Scenario: Metadata is unavailable when the feature is disabled
  Given the MCP Commerce Server feature is disabled in Configuration Management
  When a client requests /.well-known/oauth-authorization-server
  Then the response status is 404
```

---

### Story 2 — Registering an AI client without pre-provisioning

**Story:** As a Customer, I want my AI assistant to register itself with the shop on first use, so that I can connect a new assistant without an administrator creating credentials for it.

**Actor:** Customer

**Affected endpoint:** `http://glue.eu.spryker.local/register` (greenfield)

#### Acceptance criteria

```gherkin
Scenario: A new AI client registers itself successfully
  Given the MCP Commerce Server feature is enabled
  When a client posts a registration request with a client name and one redirect URI
  Then the response status is 201
  And the response returns a unique client identifier
  And the client is recorded as a public client requiring PKCE
```

```gherkin
Scenario: Registration is rejected without a usable redirect URI
  Given the MCP Commerce Server feature is enabled
  When a client posts a registration request with no redirect URI
  Then the response status is 400
  And the response body names "redirect_uris" as the invalid field
```

```gherkin
Scenario: Registration rejects a redirect URI that is not allow-listed
  Given the allowed redirect URI patterns are configured
  When a client posts a registration request with a redirect URI outside those patterns
  Then the response status is 400
  And no client record is created
```

---

### Story 3 — Authorizing with the shop in a browser

**Story:** As a Customer, I want to log in to the shop in my own browser and approve my AI assistant's access there, so that I never type my shop password into the assistant.

**Actor:** Customer

**Affected endpoints:** `http://glue.eu.spryker.local/authorize` (greenfield), `http://glue.eu.spryker.local/token` (greenfield)

#### Acceptance criteria

```gherkin
Scenario: Customer logs in and approves access
  Given a registered AI client has started an authorization request with a PKCE challenge
  When the Customer submits valid shop credentials and approves the requested access
  Then the browser is redirected to the client's redirect URI
  And the redirect carries a single-use authorization code and the original state value
```

```gherkin
Scenario: Authorization code is exchanged for an MCP token
  Given the Customer has approved access and the client holds an authorization code
  When the client posts the code with its PKCE code verifier to /token
  Then the response status is 200
  And the response returns an MCP access token with token type "Bearer"
  And the response contains no shop access token and no shop refresh token
```

```gherkin
Scenario: Token exchange fails on a mismatched PKCE verifier
  Given the client holds a valid authorization code
  When the client posts the code with an incorrect PKCE code verifier
  Then the response status is 400
  And no MCP access token is issued
```

```gherkin
Scenario: An authorization code cannot be replayed
  Given an authorization code has already been exchanged successfully
  When the client posts the same authorization code again
  Then the response status is 400
  And no MCP access token is issued
```

---

### Story 4 — Discovering the available tools

**Story:** As a Customer, I want my AI assistant to learn which shop actions it can perform, so that it can help me without me knowing the shop's API.

**Actor:** Customer

**Affected endpoint:** `http://glue.eu.spryker.local/mcp` (greenfield)

#### Acceptance criteria

```gherkin
Scenario: Session initialization succeeds over Streamable HTTP
  Given the client holds a valid MCP access token
  When the client sends an initialize request to /mcp
  Then the response status is 200
  And the response declares the negotiated MCP protocol version
  And the response declares tool support
```

```gherkin
Scenario: The minimum tool set is advertised
  Given an initialized MCP session
  When the client requests the list of tools
  Then the response lists exactly 5 tools
  And each tool carries a name, a description, and an input schema
```

```gherkin
Scenario: Tool listing is refused with an invalid token
  Given the client sends an expired MCP access token
  When the client requests the list of tools
  Then the response status is 401
  And no tool list is returned
```

---

### Story 5 — Finding products by description

**Story:** As a Customer, I want to describe what I need in plain language and have my AI assistant find matching products with prices and availability, so that I can choose without browsing the catalog myself.

**Actor:** Customer

**Affected endpoint:** `http://glue.eu.spryker.local/mcp` (greenfield; wraps the existing catalog-search and product resources of the Storefront API)

#### Acceptance criteria

```gherkin
Scenario: A product search returns usable results
  Given an initialized MCP session for an authenticated Customer
  When the client calls the product search tool with the term "camera"
  Then the response returns at most 12 products
  And each product carries a SKU, a name, and a price
```

```gherkin
Scenario: A search with no matches returns an empty result, not an error
  Given an initialized MCP session
  When the client calls the product search tool with a term matching no products
  Then the response is a successful tool result
  And the product list contains 0 entries
```

```gherkin
Scenario: Product detail is retrievable for a found product
  Given a product search returned a product with a known SKU
  When the client calls the product detail tool with that SKU
  Then the response returns the product name, price, and availability
```

---

### Story 6 — Building a cart

**Story:** As a Customer, I want my AI assistant to put a chosen product into my cart at the quantity I asked for, so that I can review the total before committing.

**Actor:** Customer

**Affected endpoint:** `http://glue.eu.spryker.local/mcp` (greenfield; wraps the existing carts and cart-items resources of the Storefront API)

#### Acceptance criteria

```gherkin
Scenario: An item is added to the customer's cart
  Given an initialized MCP session for an authenticated Customer
  When the client calls the add-to-cart tool with a valid SKU and quantity 2
  Then the response returns the cart identifier
  And the cart contains 1 item with quantity 2
  And the response returns the cart total
```

```gherkin
Scenario: Adding the same product again increases the quantity
  Given the customer's cart already contains a product at quantity 2
  When the client calls the add-to-cart tool with the same SKU and quantity 1
  Then the cart contains 1 item with quantity 3
```

```gherkin
Scenario: An unknown SKU is rejected without changing the cart
  Given an initialized MCP session with an empty cart
  When the client calls the add-to-cart tool with a SKU that does not exist
  Then the response is a tool error naming the unknown SKU
  And the cart contains 0 items
```

```gherkin
Scenario: The cart is scoped to the authorizing customer
  Given two Customers have each authorized their own AI client
  When the first client calls the add-to-cart tool
  Then the second Customer's cart contains 0 items
```

---

### Story 7 — Placing the order

**Story:** As a Customer, I want my AI assistant to place the order for the cart it built, so that I complete the purchase without leaving my assistant.

**Actor:** Customer

**Affected endpoint:** `http://glue.eu.spryker.local/mcp` (greenfield; wraps the existing checkout and checkout-data resources of the Storefront API)

#### Acceptance criteria

```gherkin
Scenario: An order is placed for a cart with a single item
  Given an initialized MCP session for an authenticated Customer with a cart holding 1 item
  When the client calls the checkout tool for that cart
  Then the response returns an order reference
  And the order is retrievable for that Customer in the Back Office
```

```gherkin
Scenario: Checkout of an empty cart is refused
  Given an initialized MCP session for an authenticated Customer with an empty cart
  When the client calls the checkout tool for that cart
  Then the response is a tool error stating the cart is empty
  And no order is created
```

```gherkin
Scenario: Checkout is refused for a cart the customer does not own
  Given an initialized MCP session for an authenticated Customer
  When the client calls the checkout tool with another Customer's cart identifier
  Then the response is a tool error
  And no order is created
```

```gherkin
Scenario: A placed order is visible to the assistant
  Given the Customer has placed an order through the checkout tool
  When the client calls the order-list tool
  Then the response contains that order reference and its total
```

---

### Story 8 — Turning the server off

**Story:** As a Back Office administrator (Back Office user), I want to enable or disable the MCP Commerce Server from Configuration Management, so that I can control whether AI clients can transact against this environment.

**Actor:** Back Office user

**Affected endpoint:** `http://backoffice.eu.spryker.local/configuration` (existing; Configuration module)

#### Acceptance criteria

```gherkin
Scenario: The MCP server toggle is available in Configuration Management
  Given a Back Office user with configuration access
  When the user opens the MCP Commerce Server settings
  Then an enable/disable setting is shown with its current value
```

```gherkin
Scenario: Disabling the server stops MCP traffic
  Given the MCP Commerce Server feature is enabled and a client holds a valid MCP token
  When a Back Office user disables the feature
  Then a subsequent tool call to /mcp returns 404
```

```gherkin
Scenario: Re-enabling restores service without a deployment
  Given the MCP Commerce Server feature has been disabled
  When a Back Office user re-enables the feature
  Then a subsequent initialize request to /mcp returns 200
```

---

## 4. Testing Plan (requirement)

Testing is a **delivery requirement of this feature**, not a follow-up. The feature is not complete until every layer below is present and passing.

### 4.1 Coverage requirements

| Layer | Scope | Requirement |
|---|---|---|
| **Unit** | PKCE challenge verification, MCP-token↔customer-session resolution, tool input-schema validation, tool-result shaping | Every unit above has tests for its success path and each documented rejection path |
| **Functional / API** | Each endpoint in Stories 1–4 and 8: discovery metadata, registration, authorize, token exchange, session initialize, tool listing, feature-flag off | Each acceptance-criteria scenario in those stories has a corresponding automated test |
| **Functional / commerce tools** | Each of the 5 tools in Stories 5–7 | Each tool has a success test plus every rejection scenario stated in its criteria |
| **End-to-end** | The full cold-start journey: 401 → discovery → registration → PKCE authorize → token → initialize → search → add to cart → checkout → order visible | At least 1 automated end-to-end test covering the whole chain in a single run |
| **Security regression** | Token isolation and cross-customer isolation | Dedicated tests asserting the shop access token and refresh token appear in **no** MCP response or error body, and that one customer's token cannot read or mutate another's cart or order |
| **Interoperability** | Real MCP client connectivity | The full journey verified manually against **≥ 2** independent MCP client implementations, with the result recorded |

### 4.2 Mandatory test scenarios

These must be automated regardless of how the implementation is structured:

1. Cold start with no credentials completes an order — the primary happy path.
2. Expired MCP token is refused on a tool call.
3. Revoked MCP token is refused on a tool call.
4. Authorization code cannot be replayed.
5. PKCE verifier mismatch blocks token issuance.
6. Redirect URI outside the allow-list is rejected at registration.
7. Customer A's MCP token cannot see or modify Customer B's cart.
8. Customer A's MCP token cannot place an order against Customer B's cart.
9. No MCP response or error payload contains a shop access or refresh token.
10. Feature flag off disables `/mcp` and both metadata documents.
11. Unknown SKU leaves the cart unchanged.
12. Empty-cart checkout creates no order.

### 4.3 Exit criteria

The feature ships only when **all** of the following hold:

- Every acceptance-criteria scenario in Stories 1–8 has a passing automated test.
- All 12 mandatory scenarios in §4.2 pass.
- The end-to-end journey passes **3** consecutive runs without intervention, to establish it is not flaky.
- The interoperability check passes against **≥ 2** MCP clients, with evidence recorded.
- Zero security-regression tests are skipped, weakened, or marked as expected failures.

### 4.4 Test data

Tests use the demo customer and demo catalog already present in this shop's imported demo data. Any additional fixture is created through the standard data-import path so that a fresh environment reproduces the suite from a clean seed — no test may depend on identifiers that vary with import order.

---

## 5. Non-functional Requirements

### Performance

- Tool listing and session initialize respond within **300 ms** at the 95th percentile.
- The product-search tool responds within **800 ms** at the 95th percentile for a result set of 12 products.
- The add-to-cart and checkout tools respond within **1500 ms** at the 95th percentile.
- Discovery metadata documents respond within **100 ms** at the 95th percentile and are cacheable for **3600 s**.

### Security

- The MCP access token is opaque to the AI client and independent of the customer's shop token; the shop access and refresh tokens are never serialized into any MCP response, error payload, or log line.
- PKCE with the **S256** challenge method is mandatory; a plain challenge is rejected.
- Authorization codes are single-use with a lifetime of **≤ 60 s**.
- MCP access tokens expire after **≤ 8 h** and are individually revocable without affecting the customer's other sessions.
- Registration accepts only redirect URIs matching a configured allow-list.
- Every MCP tool call is authorized as the customer who completed the browser login — a tool can reach no data that customer could not reach through the storefront.
- Authorization events (registration, code issuance, token issuance, token rejection) are written to the security audit log.

### Reliability

- Feature availability target **99%** in the demo environment.
- A failure inside any single tool returns a structured MCP tool error and never a raw stack trace or unhandled exception to the client.
- Disabling the feature flag takes effect within **60 s** without a code deployment.

### Scalability

- The server sustains **20** concurrent MCP sessions with no degradation beyond the latency targets above.
- Rate limiting is enforced per MCP token at **60** tool calls per minute, returning a structured throttling error beyond that.

### Compatibility

- Existing Storefront API consumers are unaffected: **zero** changes to the request or response shape of any resource that exists today.
- The MCP endpoints are additive and coexist with the current API Platform routing.

---

## 6. Success Metrics

| Metric | Baseline | Target | Measured by |
|---|---|---|---|
| MCP clients able to complete a purchase unassisted | 0 | ≥ 2 independent clients | Interoperability check in §4.1 |
| Tool calls needed after authorization to place an order | n/a (impossible today) | ≤ 6 | End-to-end test call count |
| Manual configuration steps for a new AI client | n/a (impossible today) | 0 | Registration flow test |
| MCP responses leaking a shop token | n/a | 0 | Security-regression tests |
| New business-logic modules for cart/checkout | n/a | 0 | Code review against §Goals item 4 |
| End-to-end journey stability | n/a | 3/3 consecutive passes | Exit criteria in §4.3 |
| Demo failure rate during a scripted assistant-driven purchase | n/a | ≤ 5% of attempts | Demo dry runs |

---

## 7. Out of Scope

- **Tool-surface parity with the reference implementation.** Only the 5 tools needed for the happy path are delivered; loyalty, coupons, certificates, favorites, delivery slots, store locator, family and dietary profiles, and offline order history are excluded.
- **Guest (unauthenticated) checkout via MCP.** Only an authenticated Customer can transact.
- **Phone + OTP login.** Authorization uses this shop's existing customer email-and-password login; OTP is not introduced.
- **Backend API (Glue Backend) exposure.** The MCP server is on the Storefront API only; no Back Office or merchant-facing tools.
- **Agent, Merchant user, and Merchant Agent actors.** No impersonation and no merchant-portal tooling.
- **Payment method selection, shipment method selection, address entry, and voucher handling through MCP.** Checkout uses the customer's existing default checkout data.
- **Multi-cart management, shared carts, shopping lists, quote requests, and B2B approval flows.**
- **Server-initiated streaming.** Streamable HTTP is used for request/response; server-pushed notifications, subscriptions, and long-lived event streams are not delivered.
- **MCP resources and prompts primitives.** Tools only.
- **Production hardening and cloud rollout.** The target is the demo environment.
- **Localization of tool descriptions.** English only.

---

## 8. Dependencies

**Internal (already present in this installation):**

- **Storefront API application** — hosts the MCP and OAuth endpoints alongside the existing API Platform resources.
- **API Platform integration module** — provides the storefront resource generation and the Bearer-token security layer the MCP tools authorize through.
- **Catalog Search API, Products API, Carts API, Checkout API, Auth API** — the existing storefront resources every MCP tool wraps; no new commerce logic.
- **Oauth module and its customer connector** — customer credential validation, token issuance, and the OAuth client, scope, and access-token persistence that Dynamic Client Registration extends.
- **Configuration Management** — supplies the Back Office toggle for the feature flag, following the AI Commerce configuration descriptor convention.
- **Log module** — receives the security audit entries for authorization events.

**External:**

- **The OAuth server library already vendored in this project** — supplies the authorization-code grant with PKCE support that must be wired up; no new dependency is required for it.
- **A Streamable HTTP MCP transport implementation at project level** — required because the runtime's Symfony baseline is below what API Platform's own MCP server component needs.
- **At least two third-party MCP client implementations** — needed for the interoperability requirement in §4.1.

**Constraints:**

- The `Demo` namespace has no Glue layer today; this feature introduces one, and all new code stays inside the `Demo` namespace so it remains demo-branch-only.
- The feature must not alter any existing storefront resource contract.
