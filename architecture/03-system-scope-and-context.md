# System Scope and Context

Delimits the Scania Service Shop (Spryker) from its external communication partners and specifies the external interfaces.

## Business Context

The Scania Service Shop lets a **fleet customer** log in through **My Scania (CIAM)**, view the vehicles they own from **FMAT**, browse the **compatible services and prices** for a selected vehicle from **SECM**, read **service details** from **PIM-SPISA**, place an **invoice** order, and receive an **order-confirmation email**. After an order is placed, Spryker **notifies FMAT** asynchronously.

**See diagram:** [C1 System Context](diagrams/c4/c1-system-context.mmd) (harvested from the Scania TAD, restyled)

## Technical Context

### External Systems

| System | Description | Technology | Stakeholders |
|--------|-------------|------------|--------------|
| **CIAM (My Scania)** | 3rd-party SSO. Sole authentication method. Returns customer profile (name, email, company, country), 6–8 roles, and potentially licensed products. Triggers JIT customer creation. | REST + OAuth2/OIDC (**protocol TBD**) | Client — spec required |
| **FMAT** | Fleet/asset management. Returns the customer's vehicles and connected services (sync). Receives two post-order notifications (async, via OMS). | REST (**protocol TBD**) | Client — spec required |
| **SECM** | Service catalog. Returns available/compatible services per vehicle, with pricing. | REST (**protocol TBD**) | Client — spec required |
| **PIM-SPISA** | Service detail provider ("control package": description, images). Potential external configurator for Spryker Configurable Product. | REST (**protocol TBD**) | Client — spec required |
| **SMTP / Email** | Delivery of the OOTB order-confirmation email. | SMTP (push) | TBD |

> **TODO:** Confirm the API protocol for all four integrations (REST / SOAP / GraphQL) — Open Question #16. Owner: Client.

### Integration Details

| Integration | Direction | Protocol | Sync/Async | Frequency / Constraints | Phase |
|-------------|-----------|----------|-----------|------------------------|-------|
| Spryker ← CIAM | Inbound (login) | OAuth2/OIDC + REST | Sync | Every login; must return name, email, company, country, roles | POC |
| Spryker ← FMAT | Pull | REST | Sync | On asset page load; paginated; per-customer volume unknown | POC |
| Spryker ← SECM | Pull | REST | Sync | After asset selection; ≤ ~100 services/vehicle | POC |
| Spryker ← PIM-SPISA | Pull | REST | Sync | On service selection (PDP) | POC |
| Spryker → FMAT (Post-Order #1) | Push | REST | **Async (OMS)** | Per order placed; fire-and-forget | POC |
| Spryker → FMAT (Post-Order #2) | Push | REST | **Async (OMS)** | Per order placed; payment + activation date | Future |
| Spryker → SMTP | Push | SMTP | Sync/queued | Per order placed (order confirmation) | POC |

**See diagram:** [Integration Overview](diagrams/integration/integration-overview.mmd)

### Components — Rationale

Why each participant is in scope and what Spryker capability it maps to.

| Component | Why it is here | Spryker capability it maps to |
|-----------|----------------|-------------------------------|
| Scania Service Shop (Spryker) | The platform under validation | B2B Marketplace + Yves storefront |
| CIAM (My Scania) | Sole identity source; no native accounts | **Federated Authentication (OAuth2/OIDC)** — storefront GA ([research §15](https://docs.spryker.com/docs/pbc/all/oauth/latest/federated-authentication)) |
| FMAT | Source of truth for which vehicles a customer owns; receiver of order events | Custom Client/Zed module; **SSP Asset Management** as the asset entry-point concept ([research §6](https://docs.spryker.com/docs/pbc/all/self-service-portal/latest/ssp-asset-management-feature-overview)); **OMS commands** for post-order ([research §12](https://docs.spryker.com/docs/pbc/all/order-management-system/latest/base-shop/state-machine-cookbook/state-machine-cookbook-state-machine-fundamentals)) |
| SECM | Prices/compatibility live outside the catalog | Custom Client/Zed + dynamic cart item (SD-001) |
| PIM-SPISA | Service descriptions/images; potential configurator | Custom Client/Zed + **Configurable Product** ([research §5](https://docs.spryker.com/docs/pbc/all/product-information-management/latest/base-shop/feature-overviews/configurable-product-feature-overview/configurable-product-feature-overview)) (SD-002) |
| Company Account | CIAM roles → ACL | **Company Account roles & permissions** ([research §7](https://docs.spryker.com/docs/pbc/all/customer-relationship-management/latest/base-shop/company-account-feature-overview/company-user-roles-and-permissions-overview)) |
| Dynamic Multistore | One store per country | **DMS**, GA since 202410.0 ([research §1](https://docs.spryker.com/docs/pbc/all/dynamic-multistore/latest/dynamic-multistore)) |

### Connectors — Rationale

| Connector | Description | Type | Frequency / Volume / Constraints |
|-----------|-------------|------|----------------------------------|
| Spryker ← CIAM | SSO login; receive profile, roles, products | Sync (per login) | Every login. Must return name, email, company, country, roles. Claims→role mapping is a **project-level** concern (not GA in Spryker — [research §15](https://docs.spryker.com/docs/pbc/all/oauth/latest/federated-authentication)). |
| Spryker ← FMAT | Fetch customer vehicles/assets | Sync (per request) | On asset page load; paginated; **max assets/customer TBD** (Open Q #6). |
| Spryker ← SECM | Fetch compatible services + prices | Sync (per request) | After asset selection; **≤ ~100 services/vehicle**. |
| Spryker ← PIM-SPISA | Fetch service details / config payload | Sync (per request) | On service selection. |
| Spryker → FMAT (#1) | Notify order creation | Async (OMS) | Per order; fire-and-forget; retries by OMS on failure. |
| Spryker → FMAT (#2) | Notify payment + activation date | Async (OMS) | Per order; Future. |
| Spryker → SMTP | Order confirmation | Push | Per order; OOTB. |

---

*Corresponds to [arc42 Section 3](https://docs.arc42.org/section-3/)*
