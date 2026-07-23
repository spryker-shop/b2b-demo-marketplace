# Quality Requirements

Quality goals in concrete, measurable terms, with per-phase volume planning derived from the NORMA TAD non-functional table.

## Volume Planning & Scalability Projections

Phases: **Go-Live** (Phase 1), **+1 Year**, **+…Y** (long-term horizon).

| Entity | Go-Live | +1 Year | +…Y | Notes / Risks |
|--------|---------|---------|-----|---------------|
| ---**CATALOG**--- ||||
| Active Products (SKUs) | 60,000 | 70,000 | 80,000 | Import performance watch item |
| Abstract Products | 59,000 | 66,000 | 72,000 | |
| Concrete per Abstract | Only ~1,000 abstracts have >1 variant; max 100 / avg 5 | max 100 / avg 5 | max 100 / avg 5 | Mostly single-variant catalog |
| Bundles | Not for MVP | 5,000 | 6,000 | Deferred from Phase 1 |
| Active/Inactive Categories | 2,000 | 2,300 | 2,600 | **2,000+ categories = navigation/PLP build-time risk** |
| Prices | Single price dimension per abstract + per concrete: standard, RRP, sale | same | same | 3 price types |
| ---**CART**--- ||||
| Product Item Lines per Cart | avg 1.21 / max 24 | avg 1.5 / max 30 | avg 1.8 / max 35 | Small baskets typical of food discount |
| ---**EXPECTED USER LOAD / PEAK LOAD**--- ||||
| Seasonality | Main season March–August | same | same | Size for seasonal peak |
| Visitors / day | 60,000 | 66,000 | 72,000 | |
| Concurrent Customers (max) | TBD | TBD | TBD | Interim assumption derived below |
| Concurrent Back Office Users | TBD | TBD | TBD | ~20 BO users total |
| Conversion Rate | 3% | 3.1% | 3.2% | |
| ---**ORDERS & TRANSACTIONS**--- ||||
| Orders / day | 2,000–3,000 | 2,100–3,100 | 2,200–3,200 | |
| Payment messages / day | 4,000–6,000 | ~same | ~same | Payone throughput |
| Data Import | ~50 products / 10 min (delta only); full import built on delta; **no SLA** | same | same | Steady state is light; full build is the heavy run |
| Data Export | Orders + payments delta every 10 min | same | same | |
| ---**CUSTOMERS**--- ||||
| Customers | 3,000,000 | 3,200,000 | 3,400,000 | Migrated from Shopware |
| ---**MARKETPLACE**--- ||||
| Merchants | N/A | N/A | 300 (dropshippers) | Long-term only |
| Offers | N/A | N/A | TBD | |
| ---**INTERNATIONALISATION (MULTI-STORE)**--- ||||
| Geographical Regions | 1 (EU) | 1 (EU) | 1 (EU) | |
| Countries | 1 (DE) | 1 (DE) | 4 (DE, AT, FR, CZ) | |
| Stores | 1 | 1 | 1 per country | Region strategy decision needed early |
| Locales | 1 (`de_DE`) | 1 | 3 | |
| Currencies | 1 (EUR) | 1 | 2 (EUR, CZK) | |
| Setup per store | Single DB / ES / KV | same | TBD | |
| Staging environments | 1 | 1 | TBD | |
| Shared / separate data & functionality per store | N/A | N/A | TBD | Resolve with DMS decision |

## Testing & Environment Strategy

- **Environments (Phase 1):** Production + 1 Staging (additional non-prod TBD). Staging must be able to simulate Talend delta batches and stub Payone/carrier/DWH endpoints.
- **Test data:** a representative catalog subset plus synthetic delta batches to exercise the import cursor and idempotency; migrated-customer sample to validate the password re-hash path.
- **Automated testing:** unit + functional (Codeception) for custom import/returns/payment/order-splitting modules; integration tests against stubbed external systems; smoke E2E on the critical storefront + returns + checkout paths.
- **Third-party JS:** measure storefront performance impact (Core Web Vitals) with all widgets enabled; verify no cross-service conflicts before Go-Live.

### Anchored Load Test

The TAD leaves concurrent-customer figures as TBD, so an **interim assumption** is derived to anchor the load test:

> **Assumption (interim, to be validated):** 60,000 visitors/day, concentrated into an ~8-hour active window during the March–August peak, with a 3–4× peak-hour factor, implies on the order of **~25,000–30,000 visitors/hour at peak**. At a conservative 3–5 minute average session and typical page cadence, plan the initial load test for roughly **1,500–2,500 concurrent sessions** with headroom to 2× for seasonal spikes. **Owner: Norma24 + Spryker — replace with measured concurrency once available.**

Load test must cover: PLP rendering with 2,000+ categories, checkout under peak concurrency, and the full-catalog delta build running concurrently with live traffic.

## Quality Scenarios

### Performance

| Scenario | Stimulus | Response | Measure |
|----------|----------|----------|---------|
| Category PLP | Customer opens a category with 2,000+ categories present | Page rendered | < 2s (p95) |
| Full-text search | Customer searches a term | SiteSearch360 results shown | < 500ms (p95) at the widget |
| Delta import | ~50-product delta batch received | Storefront read models updated | Within one P&S cycle |
| Payment capture | Payone capture event received | Order marked paid | Processed async without blocking checkout |

### Reliability / Correctness

| Scenario | Stimulus | Response | Measure |
|----------|----------|----------|---------|
| Idempotent import | Same delta batch delivered twice | No duplicate/drift | 0 duplicate records |
| Return label | Customer requests a return | Label generated or safe retry | Carrier command retries on failure; item never lost |

---

*Corresponds to [arc42 Section 10](https://docs.arc42.org/section-10/)*
