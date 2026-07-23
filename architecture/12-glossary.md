# Glossary

Terms used across the Scania Service Sales POC architecture. Definitions match the shared Spryker research where a Spryker feature is named.

## Business Terms

| Term | Definition |
|------|------------|
| **B2B** | Business-to-Business commerce. |
| **Fleet Customer** | A Scania company customer that owns one or more vehicles (assets) and buys services for them; represented as a Company User. |
| **Asset** | A vehicle (truck or bus) owned by the customer, sourced live from FMAT. |
| **Fleet** | The set of assets a customer owns. |
| **Service** | A vehicle service (maintenance, repair, inspection) sold to fleet customers; sourced from SECM (availability/price) and PIM-SPISA (details), not from the Spryker catalog. |
| **Dynamic service line item** | A cart/quote/order item representing a service with an API-sourced price locked at add-to-cart (SD-001). |
| **POC** | Proof of Concept — a feasibility validation, not a production build. |
| **Sole merchant** | Marketplace configured with Scania as the only merchant; infra kept ready for future multi-merchant. |
| **Activation date** | The service activation date sent to FMAT in the (Future) second post-order notification; related to the optional SSP service date/time. |
| **Control package** | PIM-SPISA's per-service payload of description, images, and (potential) configuration. |

## Technical Terms

| Term | Definition |
|------|------------|
| **API** | Application Programming Interface. |
| **REST / SOAP / GraphQL** | Candidate API protocols for the four integrations; the actual protocol is TBD (Open Q #16). |
| **OAuth2 / OIDC** | Authorization framework / identity layer used for CIAM SSO (Authorization Code flow). |
| **JIT provisioning** | Just-in-time creation of a Spryker customer/company user on first SSO login, from IdP claims. |
| **Claim** | A field in the IdP token (email, name, company, country, roles). |
| **Mock API** | A stand-in for an external system used to test the flow before real specs/sandboxes exist. |
| **Fire-and-forget** | An async call that returns control immediately; in Spryker OMS, a transition whose `<event>` is omitted so Zed drives the model via cron. |

## Spryker-Specific Terms

| Term | Definition |
|------|------------|
| **Yves** | Storefront (customer-facing) application layer. |
| **Zed** | Backend application layer (Back Office, Merchant Portal, Backend Gateway). |
| **Glue** | API layer (Backend API, Storefront API); not the primary surface for this POC (Yves-based). |
| **Client** | Layer connecting Spryker apps to internal/external systems and services; hosts the external HTTP adapters for CIAM/FMAT/SECM/PIM-SPISA. |
| **Facade** | Business-layer entry point exposing a module's functionality via Transfer Objects/native types. |
| **Transfer Object** | Pure DTO used to move data between layers/modules. |
| **OMS (Order Management System)** | State-machine engine (states, transitions, events, commands, timeouts) driving order lifecycle; hosts the post-order FMAT commands. [Research §12](https://docs.spryker.com/docs/pbc/all/order-management-system/latest/base-shop/state-machine-cookbook/state-machine-cookbook-state-machine-fundamentals) |
| **OMS Command** | A PHP class run on an OMS transition to perform a side effect (e.g. call FMAT). |
| **Federated Authentication** | Spryker OAuth2/OIDC SSO feature (modules `SecurityOauthKnpu`/`Oauth*`); storefront-GA, used for CIAM. Glue/API-level SSO and claims→role mapping are **not** GA. [Research §15](https://docs.spryker.com/docs/pbc/all/oauth/latest/federated-authentication) |
| **Company Account** | B2B structure of Companies → Business Units → Company Users, with roles & permissions; target for CIAM role mapping. [Research §7](https://docs.spryker.com/docs/pbc/all/customer-relationship-management/latest/base-shop/company-account-feature-overview/company-account-feature-overview) |
| **Company Role** | A role within a company that carries ACL permissions; CIAM roles map onto these. |
| **Configurable Product** | Spryker product type customized via a (possibly external) configurator; candidate for the Future service wizard. [Research §5](https://docs.spryker.com/docs/pbc/all/product-information-management/latest/base-shop/feature-overviews/configurable-product-feature-overview/configurable-product-feature-overview) |
| **SSP (Self-Service Portal)** | B2B after-sales portal; Asset Management (asset entry-point to a services catalog) and Service Management (book a service with date/time) are the relevant sub-features. [Research §6](https://docs.spryker.com/docs/pbc/all/self-service-portal/latest/ssp-asset-management-feature-overview) |
| **Dynamic Multistore (DMS)** | Manage multiple stores within a region from the Back Office; GA since release 202410.0. Used for GB/PL/DE. [Research §1](https://docs.spryker.com/docs/pbc/all/dynamic-multistore/latest/dynamic-multistore) |
| **Publish & Sync (P&S)** | Spryker mechanism propagating DB changes to Redis/OpenSearch via RabbitMQ; lightly used in this POC. |
| **Quote** | Persisted cart (`spy_quote`); carries the dynamic service items so the cart survives sessions. |

---

*Corresponds to [arc42 Section 12](https://docs.arc42.org/section-12/)*
