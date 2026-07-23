# Building Block View

Static decomposition of the Scania Service Sales POC. The platform is standard Spryker B2B Marketplace;
the project-specific value is concentrated in a handful of custom modules that integrate the four
external systems and model dynamic service items.

## C4 Level 2: Container View

**See diagram:** [C2 Container View](diagrams/c4/c2-spryker-container.mmd)

### Main Containers

| Container | Responsibility | POC notes |
|-----------|----------------|-----------|
| **Yves (Storefront)** | Customer-facing shop: asset selection, service browsing, service detail, cart, checkout, order history. | Traditional Yves; standard theme + a few custom pages. |
| **Zed (BackendGateway + BackOffice)** | Business logic, OMS, integration clients, order/company/role admin. | No Merchant Portal for POC (Scania sole merchant). |
| **MariaDB** | Orders, customers, companies, cart. | Assets/services/prices are **not** persisted (live fetch). |
| **Redis** | Sessions + KV storage, per store. | |
| **OpenSearch** | Catalog search. | Minimal for POC — services are not a Spryker catalog. |
| **RabbitMQ** | Async OMS commands (FMAT post-order calls, confirmation email) + P&S. | |
| **Scheduler (Jenkins)** | `oms:check-timeout` / `oms:check-condition`, P&S workers. | Required for OMS timeouts/conditions to fire. |

> **Note — no Merchant Portal / no PSP app / no ETL middleware** in the POC container set. These are
> the containers a production or multi-merchant build would add later.

## C4 Level 3: Component View

**See diagram:** [C3 Component View](diagrams/c4/c3-component-diagram.mmd)

The POC reuses OOTB Cart, Checkout, Vouchers, Order History, Company Account, and OMS. Custom
project (`Pyz`) modules:

| Module (layer) | Type | Responsibility | Phase |
|----------------|------|----------------|-------|
| **CiamSso** (Zed / OAuth) | Auth strategy plugin | SSO callback handling; JIT customer creation; map CIAM roles → Spryker **Company Roles** (ACL). POC fails with an exception on creation failure. | POC |
| **FmatAssetClient** (Client) | Integration client | Live, paginated fetch of a customer's vehicles from FMAT. No cache. | POC |
| **SecmServiceCatalogClient** (Client) | Integration client | Fetch compatible services + prices per selected vehicle from SECM. | POC |
| **SpisaServiceDetailClient** (Client) | Integration client | Fetch service details (description, images) from PIM-SPISA. Later feeds the external configurator. | POC (configurator: Future) |
| **DynamicServiceCart** (Zed Business + Cart plugins) | Cart/price model | Represent services as cart items with API-sourced prices (services are not in the Spryker catalog). Central design area — **SD-001**. | POC |
| **FmatOrderNotification** (Zed / OMS) | OMS commands | Fire-and-forget post-order call(s) to FMAT bound to OMS transitions. | POC (call 1) / Future (call 2) |
| **Storefront pages** (Yves) | Controllers + Twig | Asset selection, service catalog, service detail, activation-date field (Future) — the few custom pages. | POC (activation date: Future) |

### Component Interactions (summary)

- Yves page controllers call the three **Client** modules to read from FMAT / SECM / PIM-SPISA.
- **CiamSso** plugs into the OAuth authentication flow and writes into OOTB Customer / Company /
  CompanyRole persistence.
- **DynamicServiceCart** hooks the cart (item expander / price) so an added service carries its
  SECM price through to checkout and order.
- **FmatOrderNotification** commands are triggered by OMS events after `Place Order`.

### Multi-store note

All custom modules are store-agnostic; DMS provides per-store locale/currency and store scoping. The
POC uses one global catalog and one global price, so no per-store logic lives in the custom modules.

---

*Corresponds to [arc42 Section 5](https://docs.arc42.org/section-5/)*
