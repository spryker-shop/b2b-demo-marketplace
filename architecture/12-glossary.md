# Glossary

Terms used across this architecture documentation for the Daimler Truck B2B Parts platform.

## Business Terms

| Term | Definition |
|------|------------|
| **After Sales** | The parts/service business supporting vehicles after purchase (maintenance, repair) — Daimler's use case here. |
| **Headquarter (HQ)** | Daimler Germany; provides the platform, owns the global catalog, monitors all markets. |
| **Market** | A Daimler subsidiary or general distributor for a country; manages country-specific dealers and settings. 22 markets = 22 stores. |
| **Dealer** | A seller in the 3-tier model, modeled as a Spryker **merchant**; sells HQ-provided parts, handles order/shipping, manages its customer pool and prices. |
| **Branch** | A subordinate dealer instance under a main dealer instance; branches share the main instance's prices and cannot change them. |
| **Customer** | An organisation (workshop, transport company, independent service provider) that buys parts; min 9,000 orgs. |
| **Customer pool** | The set of customers a dealer has approved to order from it. |
| **Registration request** | A customer's request to join a dealer's customer pool (approved/rejected by the dealer). |
| **Discount group** | A per-customer pricing group that adjusts the dealer list price. |
| **LLP** | List price for a part, delivered via the IPS import. |
| **Legal template** | Per-market T&C / warranty disclaimer / sales-regulation / product-liability text; dealers use it for customer communication. |
| **FIN/VIN** | Vehicle identification number; validated via VIS to find the right parts. |

## Technical Terms

| Term | Definition |
|------|------------|
| **CTE** | Common Table Expression — used for bulk INSERT/UPDATE of millions of rows instead of many per-row statements. |
| **OAuth2 / OIDC** | Authorization framework / identity layer used for DTAG IAM login (Authorization Code flow). |
| **JIT provisioning** | Just-in-time creation of a Spryker account on first SSO login when no matching account exists. |
| **SOAP / REST** | Integration protocols; TruckLog DIMS uses SOAP, most Daimler systems use REST. |
| **SFTP** | Secure file transfer; IPS delivers XML that lands on S3. |
| **S3** | AWS object storage; the IPS XML import bucket. |
| **Read-through** | Data fetched live from an external system per request (availability), not stored in Spryker. |
| **Linkout** | A redirect to an external system (Dealer Locator, FUSO/BPC) that returns to the shop. |
| **GDPR** | EU data-protection regulation; drives EU hosting and PII handling. |

## Spryker-Specific Terms

| Term | Definition |
|------|------------|
| **Yves** | Storefront application (customer-facing). |
| **Zed** | Backend application (Back Office, Merchant Portal, Backend Gateway). |
| **Glue** | API layer (Storefront API, Backend API). |
| **Dynamic Multistore (DMS)** | Managing multiple stores within one region from the Back Office; **GA since release 202410.0**. Daimler runs 22 stores in one EU region. |
| **Merchant** | A seller entity; Daimler dealers are merchants. |
| **Merchant relationship (Merchant B2B Contract)** | A contract between a merchant (dealer) and a buyer (business unit); basis for buyer-specific prices/products; established via a relation request → approval. |
| **Merchant Product Restrictions** | Per-merchant/per-customer product visibility via allow/exclude product lists bound to a merchant relation. |
| **Persistence ACL** | DB-entity-level authorization enforced in the Persistence layer via Propel behaviors; used for per-market data isolation. **Propel-ORM only** — non-Propel queries are not filtered. |
| **User Management + ACL** | Back Office Users → Groups → Roles → Resources (`/module/controller/action`); controls which screens/actions a BO user can reach (distinct from Persistence ACL row-level filtering). |
| **Company / Business Unit / Company User** | B2B customer structure: a company has business units (sub-divisions), each with company users. |
| **Federated Authentication** | Spryker's OAuth2/OIDC delegated login (Storefront/BO GA; Glue-API SSO not GA). |
| **Publish & Sync (P&S)** | Spryker's mechanism to propagate DB changes to Redis/Search via RabbitMQ events; tuned here with CTE + chunking for scale. |
| **Comments (+ OMS)** | Generic comments feature; with the Order Management integration, comments bind to sales orders (customer↔dealer dialog). |
| **OMS / State Machine** | Order Management System modeling the order lifecycle as XML states/transitions with commands/events. |
| **search-migration pattern** | Documented Spryker pattern to replace Elasticsearch with an external search engine by swapping `QueryInterface` implementations (not a packaged connector). |
| **Storage / Search tables (`*_storage`, `*_search`)** | Denormalized Propel tables populated by P&S and read into Redis/Search. |

---

*Corresponds to [arc42 Section 12](https://docs.arc42.org/section-12/)*
