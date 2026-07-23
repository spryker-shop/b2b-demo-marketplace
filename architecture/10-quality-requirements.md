# Quality Requirements

Quality goals in concrete, measurable terms for NORMA.

## Volume Planning & Scalability Projections

All figures are from the NORMA TAD (the richest of the intake set). Seasonality: **main season March–August**.

| Entity | Go-Live | +1 Year | +…Y | Notes / Risks |
|--------|---------|---------|-----|---------------|
| ---**CATALOG**--- ||||
| Active products | 60,000 | 70,000 | 80,000 | |
| Abstract products | 59,000 | 66,000 | 72,000 | |
| Concrete per abstract | Only ~1,000 abstracts have >1 variant; max 100 / avg 5 | same | same | |
| Bundles | Not for MVP | 5,000 | 6,000 | Phase 2+ |
| Categories (root + sub) | 2,000 | 2,300 | 2,600 | **Risk:** 2k+ categories may degrade category-structure build ([§11](11-risks-and-technical-debt.md)) |
| Prices | Single price dimension per abstract & concrete; types: standard, RRP, sale | same | same | |
| Offers | N/A | N/A | TBD | |
| ---**CART / ORDER**--- ||||
| Product item lines per cart | avg 1.21 / max 24 | avg 1.5 / max 30 | avg 1.8 / max 35 | |
| Merchants (in cart) | N/A | N/A | TBD | |
| ---**END-USER LOAD / PEAK**--- ||||
| Seasonality | Main season March–August | same | same | |
| Visitors | 60,000/day | 66,000/day | 72,000/day | |
| Orders | 2,000–3,000/day | 2,100–3,100/day | 2,200–3,200/day | |
| Conversion rate | 3% | 3.1% | 3.2% | |
| Concurrent customers (max) | TBD | TBD | TBD | |
| Concurrent BO users | TBD | TBD | TBD | |
| ---**CUSTOMERS / USERS / MERCHANTS**--- ||||
| Customers | 3,000,000 | 3,200,000 | 3,400,000 | Migrated from Shopware |
| Back Office users | 20 | 25 | 30 | |
| Merchants (dropshippers) | N/A | N/A | 300 | Drop-shipment marketplace model |
| ---**INTERNATIONALISATION (MULTI-STORE)**--- ||||
| Regions | 1 (EU) | 1 (EU) | 1 (EU) | |
| Countries | 1 (DE) | 1 (DE) | 4 (DE, AT, FR, CZ) | |
| Stores | 1 | 1 | 1 per country | Long-term; no timing/setup yet |
| Languages | 1 (de_DE) | 1 | 3 | |
| Currencies | 1 (EUR) | 1 | 2 (EUR, CZK) | |
| Per-store infra | Single DB/ES/KV | same | TBD | |
| Shared data among stores | N/A | N/A | TBD | |
| Data residency / firewall | N/A | N/A | N/A | |
| ---**DATA IMPORT / EXPORT**--- ||||
| Import | Products, prices, stocks, order-status updates | same | same | **Delta every 10 min ≈ 50 products**; delta-only; full built on delta; no SLA |
| Export | Orders info, payments | same | same | Delta export every 10 min |
| ---**PAYMENTS**--- ||||
| Payment providers | Payone | TBD | TBD | |
| Payone ACP messages | 4,000–6,000/day | TBD | TBD | Events bus |
| ---**MIGRATION (HISTORICAL)**--- ||||
| Customers | From Shopware | — | — | |
| Encrypted passwords | From Shopware — **under clarification** | — | — | |
| Orders | From DWH — **under clarification** | — | — | |
| Returns | From DWH — **under clarification** | — | — | |
| ---**ENVIRONMENTS**--- ||||
| Staging | 1 | 1 | TBD | |

## Testing & Environment Strategy

- **Environments:** 1 Production + 1 Staging in Phase 1 (both EU region). Pre-prod for multi-country expansion TBD.
- **Pre-go-live validation:** given the compressed timeline, prioritise (a) delta-import + P&S performance testing at 60k-product / 2k-category scale, (b) storefront performance testing with all ~24 JS widgets active, and (c) end-to-end Payone ACP payment + OMS flow.
- **Test data:** delta feeds simulated through Talend/Glue Backend API; Payone in sandbox; carrier label calls stubbed until credentials available.
- **Automated levels:** unit + functional (Codeception) for custom modules; E2E for checkout, returns, and import; production-like load test for the main-season peak.

> **TODO:** confirm concurrent-user and BO-user targets to size load tests (all TBD in the TAD).

## Quality Scenarios

### Performance

| Scenario | Stimulus | Response | Measure |
|----------|----------|----------|---------|
| Delta import | ~50 changed products arrive via Talend | Persisted + published to storefront | Completes well within the 10-min window |
| Category structure | 2,000+ categories built during import | Category nodes/storages generated | No unacceptable storefront load-time degradation (measured before go-live) |
| Storefront under 3rd-party load | Page load with ~24 JS widgets (consent-gated) | Page rendered | Within main-season performance budget; no cross-service conflicts |
| Payment throughput | 4,000–6,000 ACP messages/day | Payment state updated in OMS | No lost order-payment state |
| Peak season | 60k visitors/day, 2,000–3,000 orders/day | Storefront + checkout stable | No SLA breach during March–August |

### Deadline reliability

| Scenario | Stimulus | Response | Measure |
|----------|----------|----------|---------|
| Go-live readiness | Full Phase-1 scope | Validated on Staging | Ready by Technical Go-Live **2024-09-09**, live **2024-09-23** |

---

*Corresponds to [arc42 Section 10](https://docs.arc42.org/section-10/)*
