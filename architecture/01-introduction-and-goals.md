# Introduction and Goals

Describes the relevant requirements and driving forces for the **Scania Service Sales POC** — a Proof of Concept validating Spryker B2B Marketplace as the foundation for selling vehicle services (maintenance, repair, inspections) to Scania fleet customers across multiple markets.

## Document Status

| Field | Value |
|-------|-------|
| **Status** | IN PROGRESS |
| **Driver** | TBD |
| **Internal Approver** | TBD |
| **External Approver** | TBD |
| **Sales contact** | TBD |
| **Approved at** | Internally: TBD / Externally: TBD |
| **Handover session** | Date: TBD / Attendees: TBD / Recording: TBD |
| **Closure date** | TBD |
| **TAD Version** | V1 |
| **Source TAD** | [Confluence — TAD Scania Service Sales POC](https://spryker.atlassian.net/wiki/spaces/ES/pages/5670961154) (v6, author Dimitriy Kravchenko) |

> **TODO:** Fill Driver, Approvers, sales contact and handover details once assigned. Owner: Client / Spryker delivery lead.

## Requirements Overview

Scania, a global manufacturer of trucks and buses, needs a digital B2B Marketplace to sell vehicle services to fleet customers across multiple markets. The platform is built on the **latest Spryker B2B Marketplace** with the **Yves (traditional) storefront**.

This is a **Proof of Concept (POC), not production-ready**. The goal is to validate that Spryker can serve as the foundation for a service sales platform integrating **four external systems** (CIAM, FMAT, SECM, PIM-SPISA) to deliver the end-to-end flow:

> **log in → select vehicle → browse services → configure service → purchase.**

Services are delivered by Scania but may in future be provided by Scania-licensed service providers. For the POC the marketplace is set up with **Scania as the sole merchant**, with the infrastructure kept ready for future multi-merchant expansion.

**Market scope (both new-vehicle sales and rolling-fleet service sales):** GB, Poland, Germany for the POC; Switzerland and Brazil are Future.

### Functional Scope

| Capability | Scope summary | Release Phase |
|---|---|---|
| Authentication (CIAM SSO) | SSO via 3rd-party CIAM as the sole auth method; native Spryker login disabled; auto-create customer (JIT) if not existing; 6–8 CIAM roles mapped to Company Roles for ACL. Registration is out of scope (done in CIAM). POC: fail-fast with exception if customer creation fails. | POC |
| Asset Management | Vehicles (trucks, buses) fetched live from FMAT; paginated list; no caching in Spryker for POC; a customer may have many assets (fleet). | POC |
| Service Catalog | Services fetched from SECM per selected vehicle; only compatible services shown; ~100 services max per vehicle; price returned by SECM. | POC |
| Service Configuration | Service details (description, images) from PIM-SPISA; basic selection for POC; Configurable Product with PIM-SPISA as external configurator is a candidate requiring technical discovery. | POC (basic) / Future (wizard) |
| Service Date & Time | SSP date/time selection per service item on the PDP; optional; shown at checkout. | Future |
| Cart | Dynamic services added with API-sourced prices; multiple assets in one cart; regular products may coexist; cart persists between sessions; OOTB vouchers/discounts. Requires an architectural decision on dynamic product/price modelling. | POC |
| Checkout & Payment | OOTB checkout; **invoice only** (no PSP); "Place Order" button; no approval workflow; OOTB order-confirmation email. | POC |
| Post-Order Notifications | Two separate async FMAT calls via OMS: order creation (POC); payment + activation date (Future). Fire-and-forget for POC. | POC (call 1) / Future (call 2) |
| Invoice Generation | NOT in Spryker — handled externally (FMAT or manual). | POC (external) |
| Order History | OOTB Spryker order history; basic statuses; OMS customization out of scope. | POC |
| Multi-Store (DMS) | One store per country (GB, PL, DE minimum); global service catalog; one global price for POC; ≤2 languages per store; currencies GBP/PLN/EUR (+CHF, BRL). | POC |
| Marketplace | Scania as sole merchant; no Merchant Portal in use; infra ready for future multi-merchant. | POC |
| Asset Caching | Not in POC. | Future |
| Service History per Asset | Past orders per vehicle. | TBD |
| Order Fulfillment Integration | Not in POC. | Future |
| Order Cancel / Modify / Reorder | Not in POC. | Future |
| Graceful Error Handling | Not in POC — exceptions acceptable. | Future |
| Real PSP Integration | Not in POC. | Future |
| Customer Notification Strategy | Beyond OOTB order confirmation. | Future |

### Migration Requirements

No data migration from a legacy commerce platform is in scope — this is a **new project built from scratch**. There is, however, an existing (undocumented) Scania implementation for **CIAM and FMAT** integration that *may* be referenced as a pattern source (code reviewable, no docs). This is a reverse-engineering input, not a migration. See [Risks](11-risks-and-technical-debt.md).

## Quality Goals

Top quality goals for the POC, ordered by priority. Because this is a feasibility POC, **integration correctness** ranks above raw performance.

| Priority | Quality Goal | Scenario |
|----------|--------------|----------|
| 1 | **Integration correctness** | The end-to-end flow (CIAM login → FMAT assets → SECM services/prices → cart → invoice checkout → FMAT post-order notify) completes successfully against the four external systems (or their mocks). |
| 2 | **Feasibility / architectural fit** | Each external system maps onto a documented Spryker capability (Federated Auth, SSP, Configurable Product, Company Account, OMS) with a viable dynamic-service-in-cart model chosen (SD-001). |
| 3 | **Extensibility** | POC decisions (live-fetch, invoice-only, sole merchant) do not block the documented Future path (caching, PSP, multi-merchant, service wizard). |
| 4 | **Multi-store readiness** | The same codebase serves GB/PL/DE via Dynamic Multistore and can add CH/BR without re-architecture. |
| 5 | **Performance (acceptable)** | Standard Spryker performance is acceptable; external API latency directly affects UX and is understood, not optimised, for the POC. |

## Stakeholders

| Role/Name | Contact | Expectations |
|-----------|---------|--------------|
| Fleet Customer (company user) | — | Log in with My Scania, see their fleet, buy compatible services simply |
| Scania Back Office User | — | Manage orders and platform config; observe POC order flow |
| Scania integration owners (CIAM/FMAT/SECM/PIM-SPISA) | Client — spec required | Provide API specs, sandboxes/mocks, and role definitions |
| Spryker delivery team | — | Deliver a working, decision-grade POC on latest B2B Marketplace |
| Solution architect | TBD (Driver) | Own the dynamic-pricing and configurator decisions (SD-001/SD-002) |

> **TODO:** Populate stakeholder contacts for each of the four external systems. Owner: Client.

---

*Corresponds to [arc42 Section 1](https://docs.arc42.org/section-1/)*
