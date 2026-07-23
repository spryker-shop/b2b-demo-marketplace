# Quality Requirements

Quality goals in concrete terms. **This is a POC**: most load/volume numbers are genuinely unknown and
are marked `TODO`/`TBD` rather than fabricated. Performance is explicitly *not* a POC quality driver
(see §1) — the numbers below matter for a later production sizing exercise, not for the POC gate.

## Volume Planning & Scalability Projections

| Entity                                 | Go-Live (POC) | Go-Live + 1 Year | Notes / Risks |
|----------------------------------------|---------------|------------------|---------------|
| ---**CATALOG (SERVICES, not Spryker products)**--- ||||
| Services per vehicle (from SECM)       | max ~100 | max ~100 | Not stored in Spryker; fetched live from SECM. |
| Regular products (Spryker catalog)     | TODO (minimal) | TODO | Regular products may coexist with services; count unknown. |
| Prices (Total)                         | 1 global price / service | TBD | One global price for POC; per-country pricing is an open question. |
| ---**CART**---                               ||||
| Service items per cart                 | TODO | TODO | Multiple assets + multiple services per cart supported. |
| Quantity per service item              | **Open question** | — | Always 1 per service/asset, or changeable? (client Q) |
| ---**EXPECTED USER LOAD / PEAK LOAD**---     ||||
| Seasonality Pattern                    | TODO | TODO | Not critical for POC. |
| Visitors (Total)                       | TODO | TODO | No load target for POC. |
| Concurrent Customers (Max)             | TODO | TODO | No SLA for POC. |
| Concurrent Back Office Users           | TODO | TODO | |
| Concurrent Merchants in MP             | 0 (no Merchant Portal) | TODO (Future) | Scania sole merchant; MP is Future. |
| ---**B2B CUSTOMERS**---                      ||||
| Companies                              | TODO | TODO | Provisioned via CIAM SSO (JIT). |
| Business Units                         | TODO | TODO | |
| Company Users                          | TODO | TODO | |
| CIAM roles → Company Roles             | 6–8 (subset for POC) | 6–8 | Mapping depends on CIAM spec; start 1:1 subset. |
| Assets (vehicles) per customer         | **Unknown** | Unknown | Fleet size unknown; FMAT list is paginated. (client Q) |
| ---**MARKETPLACE**---                        ||||
| Merchants                              | 1 (Scania) | TODO (Future multi-merchant) | Infra multi-merchant-ready. |
| Offers                                 | N/A (POC) | TODO | |
| ---**INTERNATIONALISATION (MULTI-STORE)**--- ||||
| Geographical Regions                   | 1 | 1 | One region, one codebase (DMS). |
| Countries                              | 3 (GB, PL, DE) | +2 (CH, BR — Future) | |
| Stores (Markets)                       | 3 | 5 (Future) | GB, PL, DE now; CH, BR later. |
| Locales                                | ≤2 per store | ≤2 per store | Native + English. |
| Currencies                             | GBP, PLN, EUR | +CHF, +BRL (Future) | |
| Shared Data                            | Global service catalog, one global price | TBD | |
| Separate Data                          | Orders, customers, sessions per store | TBD | |
| Shared Functionality                   | Same flow across all stores | Same | Stores can be identical for POC. |
| Separate Functionality                 | None for POC | Per-country price/catalog? (client Q) | |
| Data Residency Restrictions            | TODO | TODO | Not specified. |
| Visual Differences (Themes)            | None (standard theme) | TBD | |
| ---**ORDERS & TRANSACTIONS**---              ||||
| Orders per Day                         | TODO | TODO | No target for POC. |
| Order Lines per Day                    | TODO | TODO | |
| Post-order FMAT calls per order        | 1 (POC) | 2 (Future) | Async/OMS, fire-and-forget. |

## Testing & Environment Strategy

- **Mock-first integration:** until the four API specs/endpoints arrive, integration is developed and
  tested against **mock APIs** (the top project risk mitigation, §11). Real endpoints are swapped in
  behind the same Client modules.
- **POC test focus:** demonstrate the end-to-end flow (SSO → asset → services → detail → cart → invoice
  checkout → order → FMAT notify + email) in each of GB/PL/DE.
- > **TODO:** CI/CD pipeline, non-production environment topology (dev/staging/demo), test-data
  strategy, and automated test levels (unit/integration/E2E) are not specified in the TAD.

## Quality Scenarios

Consistent with the §1 goal ordering (integrability first; performance best-effort only).

| Scenario | Stimulus | Response | Measure |
|----------|----------|----------|---------|
| **SSO login (JIT)** | New fleet customer logs in via CIAM | Customer + company + roles created, session scoped | Login completes and lands on asset page; roles mapped |
| **End-to-end purchase** | Customer buys a service for a vehicle | Order placed on invoice; FMAT notified (async); email sent | Order visible in Order History; FMAT call dispatched; email received |
| **Multi-store** | Same flow run in GB, PL, DE | Flow works with each store's locale/currency | All three stores complete the flow |
| **External API latency (best-effort)** | SECM/FMAT slow to respond | Page renders once data returns | No hard SLA; acceptable for POC (documented trade-off) |
| **Integration isolation** | One external spec changes | Change contained to its Client module | No change needed outside the affected Client module |

> **TODO:** attach measurable performance thresholds only if/when the project moves beyond POC.

---

*Corresponds to [arc42 Section 10](https://docs.arc42.org/section-10/)*
