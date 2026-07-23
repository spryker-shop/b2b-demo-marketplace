# Glossary

Terms used across the NORMA architecture documentation.

## Business Terms

| Term | Definition |
|------|------------|
| **NORMA** | Food discount retailer (1,300+ stores in DE/FR/CZ/AT); brands *Norma* and *Norma-Rodi*. This programme re-platforms its non-food B2C online shop. |
| **B2C** | Business-to-Consumer commerce (NORMA's primary model). |
| **Marketplace (limited)** | Merchant/merchant-order data model reused **only** for Drop Shipments; not a full marketplace launch. |
| **Drop Shipment** | Supplier (dropshipper) ships goods directly to the customer; realised via the Marketplace merchant-order model. ~300 dropshippers projected in +…Y. |
| **Re-platforming** | Moving the existing non-food shop from Shopware 5 to Spryker. |
| **Order splitting** | Creating multiple shipments at checkout based on warehouse allocation. |
| **Return portal** | Custom Yves storefront area where customers initiate returns. |
| **Main season** | March–August peak trading period. |

## Technical Terms

| Term | Definition |
|------|------------|
| **ACP** | App Composition Platform — Spryker mechanism to compose external apps (e.g. Payone payment) into the platform; event-driven. |
| **ACP events bus** | Message channel carrying payment events from the Payone ACP app (4,000–6,000 msg/day). |
| **Delta import** | Incremental import of only changed records (~50 products / 10 min); full import built on the same path. |
| **ETL / Service bus** | Talend middleware bridging Spryker with Plentymarkets and DWH. |
| **DWH** | Data Warehouse — receives all data for Business Intelligence. |
| **Site-to-site VPN** | Secure network tunnel giving DWH direct DB access (Cloud-network item). |
| **Data Exchange API** | Spryker REST API for real-time DB-entity exchange (Dynamic Entity API on Backend API), no-code. |
| **Glue Backend API** | System-to-system Spryker API used by Talend for catalog import / order export. |
| **JS Widget** | Client-side JavaScript integration embedded in the storefront (most of NORMA's ~24 integrations). |
| **Consent management** | Usercentrics-driven gating of non-essential storefront tags/widgets (GDPR). |
| **REST** | Representational State Transfer. |
| **AMQP** | Messaging protocol used by RabbitMQ. |

## Spryker-Specific Terms

| Term | Definition |
|------|------------|
| **Yves** | Storefront application (Twig/PHP); hosts the return portal and JS-widget integrations. |
| **Zed** | Backend application — business logic, Back Office, OMS, data import, P&S publishers. |
| **Glue** | API layer (Backend API for system-to-system, Storefront API for headless). |
| **OMS** | Order Management System — XML-driven state machine; external side-effects run as **commands** on transitions. |
| **Foreign/ACP payment state machine** | OMS state machine used by ACP payment apps (e.g. Payone) to model Preauthorization/Capture. |
| **Publish & Sync (P&S)** | Propagates DB changes to Redis (KV) and Elasticsearch via RabbitMQ; tuned with multiple publisher queues. |
| **Search PBC** | OOTB catalog search on Elasticsearch/OpenSearch (category pages); SiteSearch360 handles full-text externally. |
| **Return Management PBC** | Spryker returns feature; carrier label generation (DHL/DPD/GLS) added as a custom OMS command. |
| **Dynamic Multi-Store (DMS)** | Multiple stores within one region managed from a single Back Office; candidate for the +…Y 4-country expansion. |
| **Merchant / Merchant state machine** | Marketplace model splitting an order into merchant orders processed by a separate state machine — basis for drop shipments. |
| **Facade** | Business-layer interface hiding module complexity. |
| **Data Import** | Spryker module ingesting CSV/feed data; optimised via batch/PDO and pre-gathered lookups. |

---

*Corresponds to [arc42 Section 12](https://docs.arc42.org/section-12/)*
