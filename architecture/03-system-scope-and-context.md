# System Scope and Context

Delimits the NORMA Spryker system from its external communication partners and specifies the external interfaces. NORMA is **integration-heavy** (~24 external systems); most are JS-widget storefront integrations, with a smaller set of backend integrations doing the heavy lifting (ERP, PSP, DWH, CRM, carriers, feed).

## Business Context

Shows the system's business environment — customers and end-users on one side, and the ecosystem of ERP, PSP, CRM, search, feed, carrier, analytics and consent services on the other.

**See diagram:** [C1 System Context](diagrams/c4/c1-system-context.mmd)

For the full grouped view of all ~24 integrations (backend vs JS-widget storefront), see the **[Integration Overview](diagrams/integration/norma-integration-overview.mmd)**.

## Technical Context

### External Systems

| System | Responsibility | Technology | Stakeholder | Phase |
|--------|----------------|------------|-------------|-------|
| **Plentymarkets ERP** | Master of products, categories, stock, prices, order updates; accepts order info + payments/expenses | REST (via Talend) | KPS | 1 |
| **Talend** | ETL / service bus — backend-to-backend integration Spryker ↔ Plentymarkets and Spryker ↔ DWH | REST | KPS | 1 |
| **Payone** | PSP — order payment (ACP app). COIN topic TBD | ACP (iframe/redirect + ACP events bus) | KPS | 1 |
| **Feed Dynamix** | SaaS product-feed management / generation | REST (product export) | KPS | 1 |
| **SiteSearch360** | Custom full-text site search | JS Widget + REST | KPS | 1 |
| **Emarsys** | Newsletter / email campaigns (CRM) | REST | Norma24 | 1 |
| **Melissa Lookup API** | Address verification for payments/orders | REST | KPS | 1 |
| **DHL / DPD / GLS** | Create return labels per carrier | REST | KPS | 1 |
| **DWH** | Data warehouse — receives all data for BI | Site-to-site VPN DB connection (opt. Data Exchange API/REST) | Norma24 | 1 |
| **GTM / GA** | Google Tag Manager + Google Analytics | JS Widget | KPS | 1 |
| **Easy Marketing** | Tag manager + marketing tracking | JS Widget | KPS | 1 |
| **Microsoft Clarity** | User-behaviour analytics | JS Widget | KPS | 1 |
| **Sovendus** | Order-confirmation marketing tool | JS Widget | KPS | 1 |
| **Klarna** | PDP recommendations | JS Widget | KPS | 1 |
| **Usercentrics** | Consent management (gates all other JS) | JS Widget + REST | KPS | 1 |
| **Jotform** | Online form builder (Contact form page) | JS Widget | KPS | 1 |
| **ParcelLab** | Post-purchase tracking experience | JS Widget | KPS | 1 |
| **OMQ** | Dynamic FAQ / help page | JS Widget | Norma24 | 1 |
| **Facebook Conversion API** | Conversion tracking | JS Widget + REST (CAPI) | Norma24 | 1 |
| **reCAPTCHA v3** | Bot/abuse protection | JS Widget | KPS | 1 |
| **Kameleoon** | Product recommendations + A/B testing | JS Widget | KPS | 1 |
| **Social login / SSO** | Storefront SSO / social login | Redirect + REST | KPS | **2 (Red)** |

### Integration Details

| Integration | Direction | Protocol | Sync/Async | Frequency | Constraints | Phase |
|-------------|-----------|----------|-----------|-----------|-------------|-------|
| Plentymarkets → Talend → Spryker (catalog) | Inbound | REST via Talend → Glue **Backend API** | Async batch | **Delta every 10 min ≈ 50 products** | Delta-only; full import built on delta; ~60k total; no SLA | 1 |
| Spryker → Talend → Plentymarkets (orders) | Outbound | REST via Talend → Glue Backend API | Async batch | Delta export every 10 min | Orders + payments/expenses | 1 |
| Spryker ↔ Payone | Bidirectional | ACP (iframe/redirect) + ACP events bus | Async events | 4,000–6,000 msg/day | ACP/foreign payment state machine; COIN TBD | 1 |
| Spryker → Feed Dynamix | Outbound | REST | Batch/pull | TBD | Product data for feed | 1 |
| Spryker → Emarsys | Outbound | REST | Async | Event/campaign-driven | Customer info | 1 |
| Spryker → Melissa | Outbound | REST | Sync | At address entry / checkout | Address verification | 1 |
| Spryker → DHL / DPD / GLS | Outbound | REST | Sync/async | On return event | Custom OMS command, per carrier | 1 |
| Spryker → Talend → DWH | Outbound | Site-to-site VPN DB (opt. Data Exchange API) | Batch | TBD | External DB access — Cloud-network item | 1 |
| Storefront JS widgets (GTM/GA, Clarity, Sovendus, Klarna, Jotform, ParcelLab, OMQ, Kameleoon, reCAPTCHA, Easy Marketing, SiteSearch360, Usercentrics, Facebook CAPI) | Mostly inbound to browser | JS Widget (some + REST) | Client-side | Per page load | Performance impact; consent-gated via Usercentrics | 1 |
| Social login / SSO | Bidirectional | Redirect + REST | Sync | Per login | Spec TBD | **2** |

> **TODO:** Feed Dynamix export frequency, DWH export scope/frequency, and Payone COIN topic are TBD in the TAD.

### Components — rationale

Which Spryker/architectural components realise each business need, and why:

| Business need | Spryker component / feature | Rationale (why) |
|---------------|-----------------------------|-----------------|
| ERP catalog & order data exchange | **Glue Backend API** ([docs](https://docs.spryker.com/docs/integrations/spryker-glue-api/backend-api/integrate-backend-api/integrate-backend-api)) | Dedicated backend/system-to-system API surface for ETL tools like Talend; token auth + scopes. |
| High-volume delta import at 60k products | **Data Import + Publish & Sync optimization** ([docs](https://docs.spryker.com/docs/dg/dev/data-import/latest/data-import-optimization-guidelines)) | Batch/PDO over ORM, pre-gather steps, multi-queue P&S and chunk tuning keep the 10-min window and 2k-category build tractable. |
| DWH BI feed | **Data Exchange API** ([docs](https://docs.spryker.com/docs/integrations/spryker-glue-api/backend-api/data-exchange-api/data-exchange-api)) *or* Cloud site-to-site VPN DB | Data Exchange API gives no-code REST access to DB entities; VPN gives direct DB read for BI. Choice open — see SD-002. |
| Payment | **Payment Service Provider PBC via ACP** ([docs](https://docs.spryker.com/docs/pbc/all/payment-service-provider/latest/base-shop/third-party-integrations/payone/app-composition-platform-integration/payone-acp-app)) | Payone ACP app composes payment into the platform; OMS integrates via the foreign/ACP payment state machine. |
| Returns + carrier labels | **Return Management PBC** ([docs](https://docs.spryker.com/docs/pbc/all/return-management/latest/base-shop/return-management-feature-overview)) + custom OMS command | Return states live in the OMS; DHL/DPD/GLS label generation attaches as a custom command on a return transition (not OOTB). |
| Drop shipments | **Marketplace merchant / merchant-order model** ([docs](https://docs.spryker.com/docs/pbc/all/order-management-system/latest/marketplace/marketplace-order-management-feature-overview/marketplace-order-management-feature-overview)) | Merchant-order split + Merchant state machine is the structural basis for a supplier shipping directly. "Drop Shipment" is not a named Spryker feature. |
| Full-text + category search | **Search PBC (Elasticsearch)** ([docs](https://docs.spryker.com/docs/pbc/all/search/latest/search)) + custom SiteSearch360 client | OOTB ES for category pages; SiteSearch360 as a custom search-client integration for full-text. No OOTB external-search-delegation feature. |
| Order splitting by warehouse | OMS + shipment split at checkout | Shipments created at checkout based on warehouse allocation. |

### Connectors — rationale

Why each connector uses the chosen transport:

| Connector | Transport | Rationale (why) |
|-----------|-----------|-----------------|
| Spryker ↔ Talend ↔ Plentymarkets | REST (Glue Backend API) | Talend is the mandated service bus; Backend API is the supported system-to-system surface. |
| Spryker ↔ Payone | ACP (iframe/redirect) + events bus | ACP payment apps are event-driven; iframe/redirect keeps card data out of Spryker (PCI SAQ-A). |
| Spryker → Feed Dynamix / Emarsys / Melissa / carriers | REST | Simple request/response to SaaS partners; no bus needed. |
| Spryker → Talend → DWH | Site-to-site VPN DB (opt. Data Exchange API) | Spryker has no native external-DB export; VPN or Data Exchange API bridges the gap for BI. |
| Storefront services | JS Widget (+ REST where needed) | Vendor-standard delivery; keeps integration client-side and consent-gated. |
| Social login / SSO | Redirect + REST | Standard OAuth-style redirect flow. |

---

*Corresponds to [arc42 Section 3](https://docs.arc42.org/section-3/)*
