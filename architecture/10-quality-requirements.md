# Quality Requirements

Quality goals in concrete, measurable terms. Because this is a feasibility POC, most volumes are small and several figures are honest unknowns awaiting client input — those are marked TODO rather than fabricated.

## Volume Planning & Scalability Projections

| Entity | Go-Live (POC) | +1 Year (Future) | Notes / Risks |
|---|---|---|---|
| ---**CATALOG**--- ||||
| Active Products (SKUs) | ~0 real catalog | grows w/ multi-merchant | Services are **not** catalog products for POC; they are dynamic items from SECM. |
| Services per vehicle (SECM) | ≤ ~100 | ≤ ~100 | Returned live per vehicle; not stored. |
| Abstract Products | ~0 | TBD | N/A for POC (dynamic services). |
| Active Categories | minimal | TBD | Service categorization TBD (Open Q #9). |
| Prices (Total) | 1 global price per service (from SECM) | per-country? | One global price for POC; per-country pricing is Open Q #20. |
| ---**CART**--- ||||
| Product Item Lines per Cart | small (multi-asset, multi-service) | small | Multiple assets + services + optional regular products in one cart. |
| Max Quantity per Cart Item | TBD | TBD | Open Q #17 — 1 per service per asset, or variable qty? |
| ---**EXPECTED USER LOAD / PEAK LOAD**--- ||||
| Seasonality Pattern | none (POC) | TBD | Not critical for POC. |
| Visitors (Total) | low (demo/pilot users) | TBD | POC audience is limited pilot fleet users. |
| Concurrent Customers (Max) | low (interim assumption: ~20 — see below) | TBD | No SLA defined; assumption stated for load test. |
| Concurrent Back Office Users | few (Scania staff) | TBD | |
| Concurrent Merchants in MP | 0 | grows w/ multi-merchant | Merchant Portal not used in POC. |
| ---**B2B CUSTOMERS**--- ||||
| Companies | pilot set | TBD | Created via CIAM JIT provisioning. |
| Branches (Business Units) | pilot set | TBD | |
| Company Users | pilot set | TBD | |
| ---**MARKETPLACE**--- ||||
| Merchants | 1 (Scania) | many (Future) | Sole merchant for POC; infra ready for multi-merchant. |
| Offers | N/A | TBD | No product offers in POC. |
| ---**INTERNATIONALISATION (MULTI-STORE)**--- ||||
| Geographical Regions | 1 | 1–2 | GB/PL/DE in one region for POC. |
| Countries | 3 (GB, PL, DE) | +2 (CH, BR) | |
| Stores (Markets) | 3 | 5 | DMS, one store per country. |
| Locales | up to 6 (EN, PL, DE + EN pairs) | +CH (DE/FR), BR (PT) | ≤2 languages per store. |
| Currencies | 3 (GBP, PLN, EUR) | +CHF, BRL | |
| Shared Data | service catalog, one global price | TBD | SECM is global for POC. |
| Separate Data | orders, customers, sessions per store | per store | |
| Shared Functionality | all features identical across POC stores | TBD | Stores can be near-identical for POC. |
| Separate Functionality | none for POC | per-country pricing? | Open Q #20. |
| Data Residency Restrictions | > **TODO** (Owner: Client) | > **TODO** | Not stated in TAD; confirm for EU/UK/BR. |
| Visual Differences (Themes) | none (standard theme + minimal customization) | TBD | No design system/wireframes yet. |
| ---**ORDERS & TRANSACTIONS**--- ||||
| Orders per Day | low (POC test volume) | TBD | Each order triggers async FMAT notify. |
| Order Lines per Day | low | TBD | |

> **TODO:** Replace low/TBD volumes with real pilot figures once the client defines the POC user population and expected order volume. Owner: Client. Several rows depend directly on Open Questions #6, #9, #17, #20.

## Testing & Environment Strategy

- **Environments:** at least one shared dev/integration environment plus a demo/pilot environment; DMS configured for GB/PL/DE.
- **External systems:** since all four specs are missing, the primary test strategy is **mock APIs** for CIAM/FMAT/SECM/PIM-SPISA until sandboxes exist. Mocks let the end-to-end flow be validated before real integration and de-risk the timeline (a named risk mitigation).
- **Automated testing:** functional tests for the custom modules (Client mapping, cart/price locking, OMS commands); the flagship dynamic-service-in-cart path (Scenario 4) and CIAM JIT provisioning (Scenario 1) are the priority test targets.
- **Test data:** pilot companies/users provisioned via CIAM claims; a small set of vehicles and services from FMAT/SECM mocks.

### Anchored (interim) load test

No SLA is defined and performance is non-critical for the POC, so a light load test is sufficient to confirm the platform does not fall over. **Interim assumption (stated explicitly, to be confirmed):** design the load test for **~20 concurrent customers** driving the asset → service → cart → checkout flow, with **external APIs mocked at realistic latency (e.g. 200–500 ms/call)**. The pass criterion is functional completion without errors, not a latency percentile — real latency is dominated by the external systems and out of Spryker's control for the POC.

> **TODO:** Confirm the real concurrent-user target and any latency expectation once the pilot population is known. Owner: Client.

## Quality Scenarios

| Scenario | Stimulus | Response | Measure |
|----------|----------|----------|---------|
| **Integration correctness** | A pilot user completes login → assets → services → cart → invoice checkout | Order is placed and FMAT notified | End-to-end flow succeeds against mocks and (later) real systems |
| **JIT provisioning** | First-time CIAM user logs in | Customer + company user created, roles mapped | Account exists with correct Company Role; failure raises exception (POC) |
| **Dynamic price integrity** | Service added to cart | SECM price locked into the line item | Order total equals the SECM price captured at add-to-cart |
| **Async resilience** | FMAT unavailable when post-order command fires | Order item stays in source state and retries | Notification eventually delivered without blocking checkout |
| **Multi-store** | Same flow on GB, PL, DE | Correct locale/currency per store | Order in correct currency; one codebase, no re-deploy to switch store |

---

*Corresponds to [arc42 Section 10](https://docs.arc42.org/section-10/)*
