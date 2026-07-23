# Glossary

Terms used across the Scania Service Sales POC architecture.

## Business Terms

| Term | Definition |
|------|------------|
| **POC** | Proof of Concept — a throwaway/validation build to prove Spryker fit, not production. |
| **Fleet Customer** | A B2B company user who owns/operates a fleet of Scania vehicles and buys services. |
| **Fleet / Asset** | The set of vehicles (trucks, buses) a customer operates; each vehicle is an asset. |
| **Service** | A vehicle service (maintenance, repair, inspection) sold on the platform. |
| **Service Provider** | An entity delivering a service — Scania, or (Future) a Scania-licensed provider. |
| **Back Office User** | Scania-internal user managing orders, companies/roles and configuration. |
| **Merchant** | A seller on the Marketplace; POC has Scania as the **sole** merchant. |
| **Rolling Fleet** | Vehicles already in service (the service-sales target), vs. new-vehicle sales. |
| **Activation Date** | The date a purchased service becomes active (Future: sent to FMAT post-order). |

## Technical Terms

| Term | Definition |
|------|------------|
| **SSO** | Single Sign-On — login delegated to an external identity provider (CIAM). |
| **OAuth 2.0** | Authorization framework used for the SSO exchange with CIAM. |
| **JIT provisioning** | Just-In-Time customer creation on first SSO login. |
| **REST / SOAP / GraphQL** | Candidate API protocols for the four integrations — **TBD**. |
| **Fire-and-forget** | Async call with no waiting for / handling of the response (POC post-order pattern). |
| **Mock API** | Stand-in implementation of an external system used until real specs/endpoints exist. |
| **ACL** | Access Control List — role/permission model. |

## External Systems

| Term | Definition |
|------|------------|
| **CIAM (My Scania)** | Scania's Customer Identity & Access Management / SSO IdP; provides customer profile, roles, products. |
| **FMAT** | Fleet asset-management system; source of vehicles; recipient of post-order notifications. |
| **SECM** | Service catalog & pricing system; returns compatible services + prices per vehicle. |
| **PIM-SPISA** | Service details system (description, images); potential external configurator. |
| **SMTP** | Mail transport for the order-confirmation email. |

## Spryker-Specific Terms

| Term | Definition |
|------|------------|
| **Yves** | Spryker storefront application layer (traditional frontend used here). |
| **Zed** | Spryker backend application layer (Back Office, backend gateway, business logic, OMS). |
| **Client (module)** | Spryker layer connecting an application to internal/external services — one per external system here. |
| **Facade** | Business-layer entry point that hides a module's internals. |
| **OMS** | Order Management System — XML-driven state machine; **commands** on transitions implement side-effects. |
| **OMS Command** | Extension point invoked by an OMS event (used for FMAT notifications + confirmation email). |
| **Company Account / Company Role** | B2B org model; CIAM roles map to Company Roles for ACL. |
| **OAuth Customer Authentication Strategy** | Plugin resolving/creating the customer on SSO login (custom strategy required for B2B). |
| **DMS (Dynamic Multi-Store)** | Multiple stores within one region managed from one Back Office; store chosen via `Store` header. |
| **SSP (Self-Service Portal)** | After-sales feature set (assets, services, service date/time) — relevant to SD-001 option B and Future date/time. |
| **Configurable Product** | Spryker product type customized via a (possibly external) configurator — Future service wizard. |
| **Publish & Sync (P&S)** | Spryker mechanism propagating data to Redis/OpenSearch; required for stores to appear on Storefront. |
| **Virtual Product** | A product represented at runtime (not persisted in the catalog) — SD-001 option A. |

---

*Corresponds to [arc42 Section 12](https://docs.arc42.org/section-12/)*
