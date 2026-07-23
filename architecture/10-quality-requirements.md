# Quality Requirements

Quality goals in concrete, measurable terms for the Daimler Truck B2B Parts platform.

## Volume Planning & Scalability Projections

Figures from the TAD "Project functional requirements (Go-Live / GL+1Y)" table. The catalog jump (300k → 26M) is the central scaling decision — see [ADR-001](09-architecture-decisions/adr-001-single-shared-database-at-scale.md).

| Entity | Go-Live | Go-Live + 1 Year | Notes / Risks |
|--------|---------|------------------|---------------|
| ---**CATALOG**--- ||||
| Active Products (SKUs) | 300,000 | **26,000,000** | Legacy status-quo; central scaling driver |
| Abstract Products | 300,000 (minimal: name + price) | 26,000,000 | Catalog search delegated to external API |
| Concrete Products per Abstract | 1:1 (grouped by product groups) | 1:1 | — |
| Bundles | N/A | N/A | Not used |
| Active Categories | Up to 10 (manual) | N/A | Category tree + filtering via external API |
| Prices (Total) | 300,000 LLP + customer-specific discounts (dynamic) | grows with product × store × currency | `spy_price_product_store` is a hot table |
| ---**CART**--- ||||
| Product Item Lines per Cart | 200 | N/A | Large B2B carts — cart recalculation cost |
| ---**EXPECTED USER LOAD / PEAK LOAD**--- ||||
| Visitors | ~120,000 sessions/month (no visitor data) | N/A | No anonymous visitor persistence |
| Concurrent Back Office Users | ~10 HQ + ~50 market = ~60 | N/A | — |
| Concurrent Merchants in MP | 1000 active dealer orgs, ~5000 users | N/A | Merchant Portal load |
| ---**B2B CUSTOMERS**--- ||||
| Companies (customer orgs) | ≥ 9,000 | N/A | Not users — organisations |
| Dealer Users | ≥ 2,500 | N/A | Across branches |
| ---**MARKETPLACE**--- ||||
| Merchants (dealers) | Many (per market) | N/A | 3-tier: markets → dealers |
| Offers | N/A | N/A | Products global, not per-offer |
| ---**INTERNATIONALISATION (MULTI-STORE)**--- ||||
| Geographical Regions | 1 (EU) | 1 (EU) | Single region |
| Countries | 1 | 22 | One store per country |
| Stores (Markets) | 1 | 22 | DMS (GA) |
| Locales | 19 | 19 | Languages |
| Currencies | 12 | 12 | — |
| Shared Data | All products global | All products global | Single shared DB/Redis/ES |
| Separate Data | Per-market BO settings, legal templates | same | Persistence ACL isolation |
| Separate Functionality | Discount-class upload, price upload (auto/manual), UK licence-plate search | same | Per-store functions |
| Data Residency | EU | EU | GDPR |
| ---**DATA IMPORT**--- ||||
| Full import volume | ~1,500,000 `<SET>` product-data tags | Must hold as catalog grows | Per store; scales with store count |
| Import target time | ~30 min (not strict) | same | CTE + chunked P&S; **load test required** |
| ---**ORDERS & TRANSACTIONS**--- ||||
| Payment providers | Invoice only (no PSP) | N/A | — |
| Data export | None (merchants fulfill via MP) | N/A | — |

**This table drives:** DB sizing/partitioning for `spy_product_abstract` / `spy_price_product_store` at 26M; Redis memory for product/price storage keys; import worker sizing; and the load-test targets below.

## Testing & Environment Strategy

- **Environments:** Dev → Stage → Prod (per TAD). Stage must be production-like for the import/P&S load test.
- **Test data:** A representative **full IPS XML file (~1.5M `<SET>` tags)** is a prerequisite for the load test (§7 checklist, owner: Daimler IPS team). A synthetic 26M-scale catalog is needed to validate the +1Y projection.
- **Automated testing:** Unit + functional (Codeception) for custom modules; integration tests for each external adapter (IParts, VIS, DIMS, CRISP, RetailNet, external search) with contract stubs.
- **Anchored load test (interim assumption):** The TAD gives no direct concurrency figure. Derive an interim target from the given figures — ~120,000 sessions/month, and B2B usage concentrated in working hours across 22 markets. **Assumption:** ~120,000 sessions/month ≈ 4,000 sessions/business day; assuming a busy-hour factor of ~15% and ~8-minute sessions, this yields on the order of **~80–120 concurrent storefront sessions** as an interim target, plus ~60 concurrent BO users and up to ~5,000 MP users (peaks lower). **Load test to (a) full import throughput at 300k and a 26M-scale catalog, (b) ~150 concurrent storefront sessions with external-search calls, (c) cart recalculation at 200 line items.** Revisit once Daimler provides real peak concurrency.

> **TODO:** Confirm real peak concurrency (storefront + MP) with Daimler to replace the interim ~150-session assumption. Owner: solution architect + Daimler. Feeds the ADR-001 load-test gate.

## Quality Scenarios

### Scalability & Import

| Scenario | Stimulus | Response | Measure |
|----------|----------|----------|---------|
| Full parts import | ~1.5M `<SET>` XML on S3 | Imported + published | ~30 min at 300k (target); throughput validated toward 26M |
| Catalog at scale | Catalog grows to 26M products | Persistence + P&S remain viable on single shared DB | No hot-table degradation beyond agreed thresholds (**gate: load test**) |
| Storefront during import | Large import running | Storefront stays responsive | No storefront outage; import does not block Yves |

### Data Isolation & Correctness

| Scenario | Stimulus | Response | Measure |
|----------|----------|----------|---------|
| Market isolation | Market BO user opens data lists | Only own market's data returned | 0 cross-market records via Propel-backed screens |
| Customer scoping | Customer selects active dealer | Only that dealer's approved products/prices shown | 100% restriction correctness |

### Performance (storefront)

| Scenario | Stimulus | Response | Measure |
|----------|----------|----------|---------|
| Product search | Customer searches parts | External API results rendered | < 2s page load (95th pct) incl. external call |
| Add to cart | Customer adds part | Merchant price + availability shown | < 2s incl. availability lookup (non-blocking if slow) |

---

*Corresponds to [arc42 Section 10](https://docs.arc42.org/section-10/)*
