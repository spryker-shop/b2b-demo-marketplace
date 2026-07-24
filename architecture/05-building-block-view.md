# Building Block View

Static decomposition of the Scania Service Shop into building blocks. The platform is standard Spryker B2B Marketplace; the project-specific value is a small set of **custom integration modules** wrapping the four external systems plus the dynamic-service cart logic.

## C4 Level 2: Container View

**See diagram:** [C2 Container View](diagrams/c4/c2-spryker-container.mmd)

### Main Containers

| Container | Responsibility |
|-----------|----------------|
| **Yves (Storefront)** | Customer-facing shop: SSO login, fleet/asset selection, service browsing & PDP, cart, checkout. |
| **Zed (BackOffice + Backend)** | Business logic, order/OMS management, store & company-role configuration, hosts the Scania integration modules' Zed side. |
| **Backend Gateway** | Yves ↔ Zed RPC. |
| **Scania Integration Modules** | Custom Client + Zed modules for CIAM, FMAT, SECM, PIM-SPISA and the dynamic-service cart. |
| **MySQL/MariaDB** | Orders, customers, companies, quotes, dynamic service line items. |
| **Redis/Valkey** | Sessions and KV storage (per store). |
| **OpenSearch** | Search indexes (per store) — minimal use in POC (services are not indexed). |
| **RabbitMQ** | Message broker; per-region vhost under DMS. |
| **Scheduler (Jenkins + PHP)** | OMS process cron (drives fire-and-forget transitions), Publish & Sync workers. |
| **Mailer** | OOTB order-confirmation email via SMTP. |

## C4 Level 3: Component View — Custom Integration Domains

**See diagram:** [C3 Component View](diagrams/c4/c3-component-diagram.mmd)

The C3 diagram shows the project modules that differentiate Scania from OOTB Spryker. Each external-system module follows the Spryker **Client** (external HTTP adapter) + **Zed** (facade/business/optional persistence) split.

### Custom Modules and t-shirt sizing

| Module (proposed) | Responsibility | Size | Sizing driver |
|---|---|---|---|
| **ScaniaCiamAuth** (Yves + Zed) | Federated Auth plugin chain, JIT customer/company-user provisioning, CIAM-role → Company-Role mapping | **L** | Auth is security-critical; claims→role mapping is project-level (not GA); JIT + company structure creation; fail-fast POC behaviour. |
| **ScaniaFmatAsset** (Client + Zed) | Live paginated asset fetch; asset selection UI; feeds selected vehicle downstream | **M** | External HTTP + pagination + Yves UI; no persistence (live fetch). |
| **ScaniaSecmService** (Client + Zed) | Fetch compatible services + prices per vehicle | **M** | External HTTP + compatibility filtering; feeds cart pricing. |
| **ScaniaSpisaDetail** (Client + Zed) | Fetch service description/images; carry config payload | **S–M** | External HTTP + PDP expander; grows to M if it also drives the configurator (SD-002). |
| **ScaniaDynamicService** (Zed) | Represent a dynamically-priced service line item in cart/quote/order; lock SECM price at add-to-cart | **L** | Flagship problem; touches Cart/Quote/Checkout/Sales; option choice pending ADR-004. |
| **ScaniaFmatOrderNotify** (Zed / OMS) | OMS commands for the two async post-order FMAT notifications | **M** | Two OMS commands, two external specs, retry semantics; call #2 is Future. |
| **ScaniaStore** (config) | DMS store/locale/currency setup for GB/PL/DE | **S** | Configuration-heavy, low custom code; DMS is GA OOTB. |

### Reused Spryker capabilities (no/low custom code)

- **Cart, Checkout (invoice), Vouchers, Order History, Sales, OMS** — OOTB.
- **Company Account** (companies, business units, company users, roles/permissions) — OOTB, configured; role mapping wired by ScaniaCiamAuth.
- **Configurable Product** — OOTB, candidate for the Future service wizard (SD-002).
- **SSP (Self-Service Portal)** — Asset Management as the asset entry-point concept; Service Management for the Future date/time-at-checkout capability.
- **Dynamic Multistore** — OOTB, configured by ScaniaStore.

> **TODO:** Module names above are proposals; confirm final namespacing/grouping (e.g. one `ScaniaIntegration` umbrella vs per-system modules) at build kickoff. Owner: Spryker delivery team.

---

*Corresponds to [arc42 Section 5](https://docs.arc42.org/section-5/)*
