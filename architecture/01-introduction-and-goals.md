# Introduction and Goals

> **This is a Proof of Concept (POC), not a production system.** The goal is to validate that Spryker
> B2B Marketplace can be the foundation for a Scania service-sales platform that integrates four
> external systems (CIAM, FMAT, SECM, PIM-SPISA) to deliver an end-to-end flow: **login → select
> vehicle → browse services → configure service → purchase**. Every item below is tagged **POC**,
> **Future**, or **TBD**; production hardening (error handling, caching, PSP, OMS customization,
> multi-merchant) is explicitly out of POC scope and carried as Future.

## Status & Approval

| Field | Value |
|-------|-------|
| **Status** | IN PROGRESS |
| **Confluence Space** | ES (Professional Services) |
| **TAD Version** | V1 |
| **Source TAD** | https://spryker.atlassian.net/wiki/spaces/ES/pages/5670961154 |
| **Driver** | > **TODO:** name the solution architect / TAD driver |
| **Approver** | > **TODO:** name the approver |
| **Sales** | > **TODO:** name the sales owner |
| **Go-Live (POC)** | > **TODO:** target POC demo date |
| **Doc date** | 2026-07-23 |

## Requirements Overview

Scania (global manufacturer of trucks and buses) wants a digital B2B Marketplace to sell **vehicle
services** (maintenance, repair, inspections) to **fleet customers** across multiple markets. Services
are delivered by Scania but may in future be provided by Scania-licensed service providers. For the POC
the Marketplace is set up with **Scania as the sole merchant**, with the infrastructure ready for
future multi-merchant.

- **Platform:** Spryker B2B Marketplace, latest version, Yves (traditional) frontend.
- **Greenfield:** brand-new project from scratch. No legacy system is being re-platformed and **no data
  migration** is in scope (registration and identity live in CIAM; assets/services/prices are fetched
  live from external systems). See *Migration Requirements* below.
- **POC vs 100%:** the POC proves the integration and purchase flow with OOTB Cart/Checkout/Vouchers/
  Order History, invoice-only payment, and two fire-and-forget post-order notifications. The full
  vision (service wizard, additional countries, multi-merchant, caching, PSP, graceful error handling)
  is Future.

### Functional Scope

| # | Capability | Summary | Release Phase |
|---|------------|---------|---------------|
| 1 | Authentication (SSO) | SSO via CIAM is the sole auth method; no Spryker-native login. JIT auto-create customer; map 6–8 CIAM roles to Spryker Company Roles for ACL. Registration done in CIAM. | **POC** |
| 2 | Asset Management | Fetch customer vehicles (trucks, buses) live from FMAT, paginated. A customer can have many assets (fleet). No caching in Spryker. | **POC** |
| 3 | Service Catalog | Fetch compatible services per selected vehicle from SECM (max ~100). Price returned by SECM per service. | **POC** |
| 4 | Service Configuration | Service details (description, images) from PIM-SPISA. Basic selection only. | **POC** |
| 4a | Service Wizard | 1–2 step wizard using Configurable Product with PIM-SPISA as external configurator. | **Future** |
| 5 | Service Date & Time | SSP service date/time selection per service item on PDP; displayed at checkout. Optional. | **Future** |
| 6 | Cart | Dynamic services added with API-sourced prices; multiple assets per cart; regular products may coexist; cart persists between sessions; OOTB vouchers/discounts. | **POC** |
| 7 | Checkout & Payment | OOTB checkout, **Invoice only (no PSP)**, "Place Order" button, no approval workflow, OOTB confirmation email. | **POC** |
| 8 | Post-Order Notification 1 | Async FMAT call on order creation via OMS (fire-and-forget). | **POC** |
| 8a | Post-Order Notification 2 | Async FMAT call for payment + activation date via OMS. | **Future** |
| 9 | Invoice Generation | Handled externally (FMAT or manual) — **not** in Spryker. | **POC (external)** |
| 10 | Order History | OOTB Spryker order history, basic statuses. No OMS customization. | **POC** |
| 11 | Multi-Store (DMS) | One store per country (GB, PL, DE min). Global service catalog, one global price, ≤2 languages/store. | **POC** |
| 12 | Marketplace | Scania sole merchant, no Merchant Portal, infra ready for multi-merchant. | **POC** |
| 13 | Asset Caching | Cache/sync assets instead of live fetch. | **Future** |
| 14 | Service History per Asset | Per-asset service history view. | **TBD** |
| 15 | Order Fulfillment / Cancel / Modify / Reorder | Fulfillment integration and order amendments. | **Future** |
| 16 | Graceful Error Handling | Robust handling of external API failures. | **Future** |
| 17 | Real PSP Integration | Replace invoice-only with a real payment provider. | **Future** |
| 18 | Customer Notification Strategy | Broader notification strategy beyond confirmation email. | **Future** |
| 19 | Additional Stores | CH (CHF), BR (BRL). | **Future** |

### Migration Requirements

**Greenfield — no historical data migration.** Registration and identity are owned by CIAM; vehicles,
services, and prices are fetched live from FMAT / SECM / PIM-SPISA at runtime (no import into Spryker
for the POC). There is therefore no source system to migrate, no entity counts, and no migration
timeline for the POC.

> **Note:** an existing Scania project may hold reusable CIAM/FMAT integration code (no docs, but
> code-reviewable) — this is a *reference for implementation patterns*, not a data-migration source.

## Quality Goals

Priorities reflect a **POC**: proving integration and the end-to-end flow outranks performance and
robustness, which are explicitly deferred.

| Priority | Quality Goal | Scenario |
|----------|--------------|----------|
| 1 | **Integrability** | With CIAM/FMAT/SECM/PIM-SPISA specs available, a fleet customer completes login → asset → services → detail → cart → invoice checkout → order end-to-end against the four systems. |
| 2 | **Functional completeness (flow)** | Every POC-tagged capability in the scope table is demonstrable in at least one store (GB, PL, or DE). |
| 3 | **Multi-store capability** | The same flow works in GB, PL and DE stores (Dynamic Multi-Store), each with its locale/currency, proving DMS as the go-forward model. |
| 4 | **Maintainability / clean boundaries** | Each external system is reached through its own Client module so a spec change or system swap is isolated to one module. |
| 5 | **Performance (best-effort only)** | Standard Spryker performance is acceptable; external API latency directly affects UX and has **no SLA** for the POC. |

## Stakeholders

| Role | Contact | Expectations |
|------|---------|--------------|
| Fleet Customer (B2B Company User) | *(end user)* | Log in via My Scania, see own fleet, find and buy compatible services quickly. |
| Back Office User (Scania internal) | > **TODO** | Manage orders, companies/roles and configuration; view order history. |
| Merchant (Scania-licensed provider) | > **TODO** | *(Future)* Fulfil services as an additional merchant. |
| Solution Architect / TAD Driver | > **TODO** | POC validates Spryker fit; discovery items resolved. |
| CIAM team (My Scania) | > **TODO** | Provide SSO spec, role/permission model, product payload. |
| FMAT team | > **TODO** | Provide asset API + two post-order notification specs. |
| SECM team | > **TODO** | Provide service catalog + pricing API. |
| PIM-SPISA team | > **TODO** | Provide service-detail API and configurator capabilities. |

---

*Corresponds to [arc42 Section 1](https://docs.arc42.org/section-1/)*
