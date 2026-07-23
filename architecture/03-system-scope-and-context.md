# System Scope and Context

Delimits the Scania Service Sales POC from its communication partners and specifies the external
interfaces. This is an **integration-centric POC**: the Spryker platform orchestrates four external
systems plus email; it owns almost no master data itself (identity in CIAM; assets in FMAT; catalog and
pricing in SECM; service details in PIM-SPISA).

## Business Context

**See diagram:** [C1 System Context](diagrams/c4/c1-system-context.mmd)

- **Fleet Customer** (B2B Company User) logs in through **CIAM (My Scania)** via SSO, sees the vehicles
  of their fleet (from **FMAT**), browses **compatible services** for a selected vehicle (from
  **SECM**), views service **details** (from **PIM-SPISA**), adds services to the cart, and checks out
  on invoice. An **order confirmation email** is sent via SMTP.
- **Back Office User** (Scania internal) manages orders, companies/roles and configuration.
- **Merchant** (Scania-licensed provider) is a **Future** actor — the Marketplace runs with Scania as
  sole merchant for the POC, but the infrastructure is multi-merchant-ready.

## Technical Context

### External Systems

| System | Responsibility | Technology | Stakeholders |
|--------|----------------|------------|--------------|
| **CIAM (My Scania)** | SSO authentication; provides customer data (name, email, company, country, roles, purchased/licensed products). | REST (Sync) — **protocol TBD** | > **TODO:** CIAM team / provider (client — spec required) |
| **FMAT** | Asset management: returns a customer's vehicles + connected services; also **receives** two async post-order notifications. | REST (Sync fetch, Async post-order) — **protocol TBD** | > **TODO:** FMAT team (client — spec required) |
| **SECM** | Service catalog: available/compatible services per vehicle + pricing. | REST (Sync) — **protocol TBD** | > **TODO:** SECM team (client — spec required) |
| **PIM-SPISA** | Service details (description, images); potential **external configurator** for Configurable Product. | REST (Sync) — **protocol TBD** | > **TODO:** PIM-SPISA team (client — spec required) |
| **SMTP** | Email delivery for the OOTB order-confirmation email. | SMTP (Push) | > **TODO:** stakeholder TBD |

> **Open question (all systems):** confirm the API protocol (REST / SOAP / GraphQL). Full payload/spec
> for each of the four systems is required before integration can be finalised.

### Integration Details

| Integration | Direction | Protocol | Sync/Async | Frequency / Constraints | Release Phase |
|-------------|-----------|----------|-----------|--------------------------|---------------|
| CIAM → Spryker | Pull (on login) | REST (TBD) | Sync | Every login; must return name, email, company, country, roles, products. | POC |
| FMAT → Spryker | Pull | REST (TBD) | Sync | On asset-page load; paginated; volume/attributes unknown. | POC |
| SECM → Spryker | Pull | REST (TBD) | Sync | After asset selection; max ~100 services/vehicle; returns prices. | POC |
| PIM-SPISA → Spryker | Pull | REST (TBD) | Sync | On service selection; returns description + images. | POC |
| Spryker → FMAT (Post-Order 1) | Push | REST (TBD) | **Async (OMS)** | Per order; notify order created; **fire-and-forget** (no retry/error handling). | POC |
| Spryker → FMAT (Post-Order 2) | Push | REST (TBD) | **Async (OMS)** | Per order; notify payment + activation date; fire-and-forget. | **Future** |
| Spryker → SMTP | Push | SMTP | Sync | Per order; OOTB confirmation email. | POC |

**See diagram:** [Integration Overview](diagrams/integration/external-systems-overview.mmd)

### Components Rationale

Why each Spryker-side building block exists (detailed decomposition in §5):

| Component | Rationale |
|-----------|-----------|
| **CiamSso** (OAuth strategy plugin, Zed) | SSO is the only login; a **custom** `OauthCustomerAuthenticationStrategyPluginInterface` implementation is required because the OOTB auto-create strategy is B2C-only and establishes no company context. Handles JIT customer creation + CIAM-role → Company-Role mapping. [docs](https://docs.spryker.com/docs/pbc/all/oauth/latest/storefront-account-bootstrapping) |
| **FmatAssetClient** (Client) | Isolates the live, paginated FMAT asset fetch behind one module so a protocol/spec change or a later switch to caching (Future) is contained. |
| **SecmServiceCatalogClient** (Client) | Isolates the per-vehicle service+price lookup; keeps SECM's compatibility/pricing rules out of Yves. |
| **SpisaServiceDetailClient** (Client) | Isolates service-detail retrieval; the same boundary can later feed the external configurator (Future, SD-002). |
| **DynamicServiceCart** (Business/Cart, Zed) | Models services (which don't exist in the Spryker catalog) as cart items with API-sourced prices. Central discovery area — see **SD-001**. |
| **FmatOrderNotification** (OMS commands, Zed) | Post-order side-effects as OMS commands bound to transitions — the documented Spryker pattern for fire-and-forget external calls. |
| OOTB Cart / Checkout / Vouchers / Order History | Reused as-is; the POC deliberately avoids customizing them. |

### Connectors Rationale

| Connector | Rationale |
|-----------|-----------|
| **Spryker ← CIAM (SSO, Sync)** | Login must resolve the full B2B context (company, country, roles) in one synchronous exchange so the session is correctly scoped from the first request. |
| **Spryker ← FMAT (assets, Sync, paginated)** | Assets are read live at page-load (no cache, POC); pagination bounds response size for large fleets. |
| **Spryker ← SECM (services+price, Sync)** | Compatibility filtering and price must be authoritative from SECM at the moment of browsing — hence synchronous per request. |
| **Spryker ← PIM-SPISA (detail, Sync)** | Detail is fetched on demand when a service is opened; keeps the catalog list lightweight. |
| **Spryker → FMAT (post-order, Async/OMS)** | Order creation must not block on FMAT availability; OMS commands decouple the call from the checkout request (fire-and-forget for POC). |
| **Spryker → SMTP (Push)** | OOTB confirmation email; no custom notification strategy for POC. |

---

*Corresponds to [arc42 Section 3](https://docs.arc42.org/section-3/)*
