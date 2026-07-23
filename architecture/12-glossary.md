# Glossary

Terms used across this NORMA architecture documentation. Spryker definitions are corrected against the shared docs research.

## Business Terms

| Term | Definition |
|------|------------|
| **B2C** | Business-to-Consumer commerce; NORMA's model (with limited Marketplace capabilities). |
| **Back Office User** | Internal Norma24 staff managing the platform (~20 users). |
| **COIN** | A Payone-specific payment topic handled as OMS transitions. |
| **Delta import** | Importing only changed records (~50 products / 10 min) rather than the full catalog; the full/initial load is built on this mechanism. |
| **Drop Shipment** | Fulfilment where a supplier ships directly to the customer. **Not a Spryker feature** — implemented with Marketplace primitives. |
| **DWH** | Data Warehouse; receives NORMA data for Business Intelligence. |
| **ERP** | Enterprise Resource Planning system; NORMA's is Plentymarkets. |
| **Return Policy** | Configurable time window during which an order item may be returned. |
| **Returns Portal** | Custom Yves storefront flow for customers to create returns and obtain carrier labels. |
| **Seasonality** | NORMA's main season, March–August, driving peak load. |

## Technical Terms

| Term | Definition |
|------|------------|
| **API key / OAuth 2.0** | Authentication mechanisms for the Glue Backend API (used by Talend). |
| **arc42** | Architecture documentation template used here. |
| **C4 Model** | Context / Container / Component / Code diagram model. |
| **CTE** | Common Table Expression; used for bulk insert/update during large imports. |
| **ETL** | Extract-Transform-Load; Talend's role between Spryker and ERP/DWH. |
| **GDPR** | EU data-protection regulation; relevant to consent and address handling. |
| **Idempotency (delta cursor)** | Property ensuring a re-delivered delta batch produces no duplicates, keyed on a stored cursor. |
| **JS widget** | A vendor-provided client-side script integrated in the storefront browser. |
| **OIDC** | OpenID Connect; identity layer over OAuth2 used by Federated Authentication. |
| **PSP** | Payment Service Provider; NORMA's is Payone. |
| **REST** | Representational State Transfer HTTP API style. |
| **site2site VPN** | A secure network tunnel option for the DWH DB connection. |

## Spryker-Specific Terms

| Term | Definition |
|------|------------|
| **Yves** | Spryker storefront application layer (customer-facing). |
| **Zed** | Spryker backend application layer (Back Office, business logic, persistence). |
| **Glue Backend API** | Spryker's system-to-system API application; hosts Data Exchange / Dynamic Entity endpoints and custom resources. |
| **Data Exchange API** | Configurable REST endpoints over database tables (Dynamic Entity), enabling middleware-less integration; runs on Glue Backend API. |
| **Publish & Synchronize (P&S)** | Spryker mechanism that propagates DB changes into Redis (KV) and Elasticsearch/OpenSearch read models via event + sync messages. |
| **OMS / State Machine** | Order Management System; an XML state machine of states/transitions bound to events, with **commands** running external side-effects. |
| **OMS Command** | A PHP class executed on an OMS transition (e.g. Payone capture, carrier-label generation). |
| **MessageBroker** | Spryker's event/message bus; carries Payone capture/status events in the direct-module integration. |
| **ACP (App Composition Platform)** | Spryker's low-code third-party integration platform; **being sunset** — new builds use direct modules. |
| **Marketplace Product Offer** | Marketplace primitive representing a merchant/fulfiller's offer of a product; used for drop shipment. |
| **Marketplace Shipment** | Marketplace primitive splitting an order into per-merchant/per-fulfiller shipments. |
| **Return Management** | Core Spryker feature for creating and processing returns as an OMS sub-process. |
| **Dynamic Multistore (DMS)** | Managing multiple stores within one region from the Back Office without redeploy; GA since release 202410.0. |
| **Federated Authentication** | Spryker OAuth2/OIDC login delegation to an external IdP (storefront supported; Glue-API SSO not yet GA). |
| **Data Import optimization** | Spryker guidelines for high-volume import (batch, CTE bulk, bulk event triggering). |
| **QueryInterface (search)** | The search plugin/query model replaced when delegating full-text search to an external engine. |

---

*Corresponds to [arc42 Section 12](https://docs.arc42.org/section-12/)*
