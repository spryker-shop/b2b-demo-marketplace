# Glossary

Important domain and technical terms used in the architecture documentation.

## Business Terms

| Term | Definition |
|------|------------|
| **B2B** | Business-to-Business commerce |
| **B2C** | Business-to-Consumer commerce |
| **Backoffice User** | Internal user managing the platform |
| **Merchant User** | Seller managing products and orders on the marketplace |
| **Cart** | Shopping basket for checkout |
| **Checkout** | Process of finalizing a purchase |
| **ERP** | Enterprise Resource Planning system (SAP ECC 6.0) |
| **Order** | Finalized purchase transaction sent to ERP |
| **PIM** | Product Information Management system (Akeneo) |
| **Product** | Sellable item with SKU and price from PIM |
| **SKU** | Stock Keeping Unit (unique product identifier) |

## Technical Terms

| Term | Definition |
|------|------------|
| **API** | Application Programming Interface |
| **APM** | Application Performance Monitoring (e.g., Datadog, New Relic) |
| **arc42** | Template for architecture documentation |
| **BI** | Business Intelligence Platform |
| **C4 Model** | Context, Container, Component, Code diagrams |
| **CDN** | Content Delivery Network |
| **CI/CD** | Continuous Integration/Deployment |
| **CIAM** | Customer Identity and Access Management (e.g., Auth0) |
| **OAuth 2.0** | Authorization framework used by CIAM |
| **REST** | Representational State Transfer |
| **Redis** | In-memory data store |

## Spryker-Specific Terms

| Term | Definition |
|------|------------|
| **Yves** | Storefront application layer |
| **Zed** | Backend application layer (BackOffice, MerchantPortal, BackendGateway) |
| **Glue** | API layer (BackendAPI, StoreFrontAPI) |
| **Client** | Communication layer between Yves and Zed |
| **Facade** | Module API interface in Communication layer |
| **Gateway** | RPC handler for Client-to-Zed communication |

---

*Corresponds to [arc42 Section 12](https://docs.arc42.org/section-12/)*
