# System Scope and Context

Delimits the NORMA Spryker platform from its communication partners and specifies the external interfaces. NORMA is an integration-heavy system: 22 external systems, most storefront ones being JS widgets and a few being backend/ETL REST integrations.

## Business Context

The NORMA storefront (Spryker) serves B2C customers in Germany (Phase 1). Its ERP is Plentymarkets, integrated exclusively through the Talend ETL service bus; payments go through Payone; a data warehouse (DWH) receives data for BI; and a large set of storefront services (search, analytics, consent, A/B, recommendations, forms, tracking) are integrated mostly as JavaScript widgets.

**See diagram:** [C1 System Context](diagrams/c4/c1-system-context.mmd) (authored from the Components + Connectors tables; the TAD source diagram was drawio, not mermaid)

**See also:** [Integration overview](diagrams/integration/integration-overview.mmd) — all 22 systems grouped by integration style.

## Technical Context

### External Systems

| System | Description | Technology | Release Phase | Stakeholder |
|--------|-------------|------------|---------------|-------------|
| Plentymarkets ERP | Cloud e-commerce ERP; master for products, attributes, categories, stock, prices; receives orders & payments | REST via Talend | Phase 1 | Norma24 |
| Talend | ETL service bus between Spryker and Plentymarkets/DWH | REST API | Phase 1 | KPS |
| Payone | PSP for order payment (incl. COINs) | Redirect/iframe + MessageBroker events | Phase 1 | Norma24 |
| Feed Dynamix | SaaS feed management; consumes product feed | REST API | Phase 1 | Norma24 |
| SiteSearch360 | Custom full-text site search | JS widget + feed | Phase 1 | Norma24 |
| Emarsys | CRM: newsletters and email campaigns | REST API | Phase 1 | Norma24 |
| Melissa Lookup API | Address verification for payments/orders | REST API | Phase 1 | Norma24 |
| DHL / DPD / GLS | Return-label carriers | REST API | Phase 1 | Norma24 |
| DWH | Data warehouse; all data for BI | site2site VPN / Data Exchange API via Talend | Phase 1 | Norma24 |
| GTM / GA | Google Tag Manager + Analytics | JS widget | Phase 1 | Norma24 |
| Easy Marketing Tag manager | Tag manager + marketing tracking | JS widget | Phase 1 | Norma24 |
| Microsoft Clarity | User-behaviour analytics | JS widget | Phase 1 | Norma24 |
| Facebook Conversion API | Conversion tracking | JS widget | Phase 1 | Norma24 |
| Sovendus | Order-confirmation marketing | JS widget | Phase 1 | Norma24 |
| Klarna | PDP recommendations | JS widget | Phase 1 | Norma24 |
| Usercentrics | Consent management | JS widget + REST API | Phase 1 | Norma24 |
| Jotform | Contact-form builder | JS widget | Phase 1 | Norma24 |
| ParcelLab | Post-purchase experience tracking | JS widget | Phase 1 | Norma24 |
| OMQ | Dynamic FAQ/help page | JS widget | Phase 1 | Norma24 |
| reCAPTCHA v3 | Bot protection | JS widget | Phase 1 | Norma24 |
| Kameleoon | A/B testing + product recommendations | JS widget | Phase 1 | Norma24 |
| Social login / SSO | Not yet defined | Redirect + REST | Phase 2 | Norma24 |

### Integration Details

| Integration | Direction | Protocol | Sync/Async | Frequency / Constraints | Phase |
|-------------|-----------|----------|------------|--------------------------|-------|
| Plentymarkets ↔ Talend ↔ Spryker (catalog) | Inbound | REST → Glue Backend API | Async batch | Delta every 10 min, ~50 products; full import built on delta; no SLA | 1 |
| Spryker → Talend → Plentymarkets (orders/payments) | Outbound | Glue Backend API → REST | Async batch | Delta export every 10 min | 1 |
| Spryker ↔ Payone | Bidirectional | Redirect/iframe + MessageBroker | Async (capture/status) | 4,000–6,000 payment messages/day | 1 |
| Spryker → Feed Dynamix | Outbound | REST | Async | Product feed export; volume TBD | 1 |
| Spryker ↔ SiteSearch360 | Bidirectional | JS widget (search) + feed content | Sync (query) | Full-text queries; category PLPs remain on OOTB ES | 1 |
| Spryker → Emarsys | Outbound | REST | Async | Customer info for campaigns; volume TBD | 1 |
| Spryker → Melissa | Outbound | REST | Sync | Address verification at checkout | 1 |
| Spryker → DHL / DPD / GLS | Outbound | REST | Sync/Async (OMS command) | Return-label generation on demand | 1 |
| Spryker → DWH (via Talend) | Outbound | site2site VPN DB / Data Exchange API | Batch | BI extract; mechanism TBD ([ADR-004](09-architecture-decisions/adr-004-dwh-access-mechanism.md)) | 1 |
| Storefront JS widgets (GTM/GA, Clarity, FB, Sovendus, Klarna, Jotform, ParcelLab, OMQ, reCAPTCHA, Kameleoon, Easy Marketing) | Client-side | JS (browser) | Sync | Consent-gated via Usercentrics; performance budget applies | 1 |
| Social login / SSO | Bidirectional | OAuth2/OIDC (Federated Auth) | Sync | Undefined; storefront Federated Auth is supported (research §15) | 2 |

### Components rationale

Why each in-scope component/system participates in the architecture.

| Component | Rationale |
|-----------|-----------|
| NORMA Spryker platform (Yves/Zed/Glue) | Replaces Shopware 5 as the commerce core; owns catalog read models, checkout, OMS, returns portal. |
| Glue Backend API | Single, authenticated integration surface for Talend-driven import/export; hosts Data Exchange / Dynamic Entity endpoints. Research §10. |
| Talend | Mandated ETL service bus; isolates Spryker from ERP/DWH protocol details and does the heavy transformation off-platform. |
| Payone (direct module) | PSP for the DE market; direct module chosen over ACP because ACP is sunset. [ADR-001](09-architecture-decisions/adr-001-payone-direct-module-over-acp.md). |
| SiteSearch360 | Delivers NORMA's required full-text search behaviour that OOTB ES is not tuned for; category PLPs stay on ES. [ADR-002](09-architecture-decisions/adr-002-external-full-text-search-sitesearch360.md). |
| Returns portal + carrier labels | Custom Yves portal on Return Management; carrier labels via a custom OMS command. [SD-002](04-solution-designs/sd-002-returns-portal-and-carrier-labels.md). |
| Feed generation | API-based product export decouples marketing feeds from the storefront read models. |
| Marketplace primitives | Provide the drop-shipment (per-fulfiller order split) mechanism without a dedicated feature. Research §17. |

### Connectors rationale

Why each connector uses its chosen protocol/pattern, with volume/constraints.

| Connector | Type / Pattern | Rationale | Frequency / Volume / Constraints |
|-----------|----------------|-----------|----------------------------------|
| Spryker ↔ Talend ↔ Plentymarkets | REST over Glue Backend API, async batch | ERP only exposes delta; batch import via Data Exchange keeps writes off the request path | ~60k products total; ~50 products / 10 min; orders exported / 10 min; **no SLA** |
| Spryker ↔ Payone | Redirect + MessageBroker events | Direct module uses the event bus for async capture/status; OMS commands react to events | 4,000–6,000 messages/day |
| Spryker → Feed Dynamix | REST | Simple pull/push of product data for feed generation | TBD |
| Spryker → DWH (via Talend) | site2site VPN DB or Data Exchange API | Spryker has no native external-DB connector; needs Spryker Cloud confirmation | TBD; **mechanism unresolved** ([ADR-004](09-architecture-decisions/adr-004-dwh-access-mechanism.md)) |
| Spryker → DHL/DPD/GLS | REST via OMS command | Label generation triggered from the return OMS sub-process | On demand per return |
| Spryker → Emarsys / Melissa | REST | Straightforward outbound calls (campaign sync, address verify) | TBD / per-checkout |
| Storefront JS widgets | Client-side JS | Vendor-provided widgets integrate in the browser, not server-side | Consent-gated; performance budget; watch cross-service conflicts |

---

*Corresponds to [arc42 Section 3](https://docs.arc42.org/section-3/)*
