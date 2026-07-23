# Glossary

Domain and technical terms used across this architecture record.

## Business Terms

| Term | Definition |
|------|------------|
| **Headquarter (HQ)** | Daimler Germany; Tier 1 — provides the platform to markets and dealers, manages all markets. |
| **Market** | Daimler subsidiary / general distributor per country; Tier 2 — manages country dealers & settings; sees only own data (22 markets). |
| **Dealer** | Tier 3 seller; a Spryker **merchant** selling HQ products online, owning order & shipping. |
| **Customer** | Buyer organization with own workshop (transport co., garbage disposal, independent workshop); ≥9,000 orgs. |
| **Dealer pool** | The set of customers a dealer has approved to buy from them. |
| **Discount group** | Dealer-defined per-product/per-customer discount structure over the dealer list price. |
| **LLP** | Dealer list price (list-line price) — base from which customer prices are derived. |
| **FIN / VIN** | Vehicle identification numbers used to match parts to a vehicle. |
| **After Sales** | Spare-parts business for maintaining/repairing trucks. |
| **Branch** | Subordinate dealer instance under a main merchant instance, sharing the main instance's prices. |

## Technical Terms

| Term | Definition |
|------|------------|
| **REST** | Representational State Transfer API. |
| **SOAP** | XML-based RPC protocol (TruckLog DIMS). |
| **SFTP** | Secure File Transfer Protocol (IPS file delivery). |
| **OAuth** | Delegated authorization framework (DTAG IAM). |
| **Linkout** | Redirect to an external UI and back. |
| **S3** | Object storage; landing zone for import files. |
| **ETL Middleware** | Pipeline that splits/transforms the large XML before Spryker ingests it. |
| **CTE** | Common Table Expression; SQL construct used for bulk import writes. |
| **arc42 / C4** | Architecture documentation and diagram standards. |

## External Systems

| Term | Definition |
|------|------------|
| **IParts** | Part-vehicle matching + parts catalogue structure; source for catalog and PLP/search. |
| **VIS** | Vehicle Information Service; FIN/VIN validation, vehicle data card. |
| **TruckLog DIMS** | Spare-parts availability (SOAP), phased rollout. |
| **MB LogBus DIMS** | Availability (REST) fallback where TruckLog is unavailable. |
| **CRISP** | Customer organisation information. |
| **IPS** | Integrated Price Service; prices & parts master via XML/SFTP (being replaced). |
| **RetailNet** | Dealer (merchant) organisation information. |
| **Dealer Locator** | Dealer location service; supports customer registration. |
| **FUSO / BPC** | External parts catalogues for FUSO & Bus parts. |
| **DTAG IAM (Empower ID)** | Daimler identity & access management; OAuth customer authentication. |

## Spryker-Specific Terms

| Term | Definition |
|------|------------|
| **Yves** | Storefront application layer. |
| **Zed** | Backend application layer (Back Office, backend gateway). |
| **Merchant Portal** | Merchant-scoped subset of Zed for dealers to self-manage. |
| **Glue Backend API** | System-to-system API surface. |
| **Merchant Relationship** | Merchant B2B Contracts / Contract Requests linking merchants to buyers. |
| **Merchant Custom Prices** | Per-merchant-relation pricing dimension. |
| **Merchant Product Restrictions** | Product-list-based visibility control per merchant relationship. |
| **Persistence ACL** | Pattern for restricting BO users' access to store/market-specific data rows (mechanism to be confirmed). |
| **Publish & Sync (P&S)** | Event-driven propagation of DB changes to Redis/ES via RabbitMQ. |
| **DMS (Dynamic Multi-Store)** | Multiple stores in one region managed from one Back Office. |
| **OMS** | Order Management System; XML-driven state machine. |
| **Comments** | Generic threaded-comment feature attachable to entities (orders). |
| **DataImport** | Spryker module for importing data; supports batch/PDO/CTE optimization. |

---

*Corresponds to [arc42 Section 12](https://docs.arc42.org/section-12/)*
