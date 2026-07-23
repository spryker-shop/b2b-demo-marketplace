# Quality Requirements

Quality goals for the Daimler Truck B2B Parts platform in concrete, measurable terms.

## Volume Planning & Scalability Projections

Numbers from the TAD across Go-Live and Go-Live + 1 Year (GL+1Y).

| Entity | Go-Live | Go-Live + 1 Year | Notes / Risks |
|--------|---------|------------------|---------------|
| ---**CATALOG**--- ||||
| Active Products (part numbers) | 300,000 | **26 mio** | Legacy status quo; huge growth drives import & storage sizing. |
| Abstract Products | 300,000 | 26 mio | Minimal data (name + price) to be listable; catalog search via external API. |
| Concrete per Abstract | 1-to-1 (grouped by product groups) | same | — |
| Categories (root + sub) | Up to 10, manually configured | N/A | External API (IParts) builds category tree + catalog filtering. |
| Prices (Total) | 300,000 LLP + customer-specific discounts | N/A | Customer prices calculated dynamically. |
| Bundles | N/A | N/A | Not used. |
| ---**CART / ORDER**--- ||||
| Product Item Lines per Cart | **200** | 200 | Large carts — validate cart/checkout performance. |
| ---**EXPECTED USER LOAD**--- ||||
| Visitors | ~120,000 sessions/month | — | No visitor (anonymous) data. |
| Concurrent BO Users | ~10 HQ, ~50 market | — | — |
| Concurrent Merchants in MP | ~1,000 active dealer orgs, ~5,000 users | — | Drives Merchant Portal scaling. |
| ---**B2B CUSTOMERS**--- ||||
| Customer Organizations | min. 9,000 orgs | — | Organizations, **not** users. |
| Dealer Users | min. 2,500 | — | Tier-3 merchant users. |
| ---**MARKETPLACE**--- ||||
| Merchants (Dealers) | ≥ per-market dealer counts | — | Dealers are Spryker merchants. |
| Markets (Tier 2) | 1 country live | 22 markets | Subsidiaries / general distributors. |
| ---**INTERNATIONALISATION (MULTI-STORE)**--- ||||
| Regions | 1 (EU) | 1 (EU) | One region. |
| Countries | 1 | 22 | — |
| Stores | 1 | 22 (1 per country) | Dynamic Multi-Store. |
| Languages | 19 | 19 | — |
| Currencies | 12 | 12 | — |
| Per-store Infra | **Single DB / Redis / ES** | same | ES not used for product search/filtering. |
| Shared Data | All products global (shared between markets) | same | — |
| Separate Data | Market settings, legal templates, dealers, BO data | same | Isolated per store. |
| Separate Functionality | Discount-class product upload, price upload (auto/manual), UK license-plate search | same | Per-store functional differences. |
| ---**DATA IMPORT / EXPORT**--- ||||
| Import mode | **FULL import per store** via XML on S3 | same | ~1,500,000 `<SET>#productdata` tags; ≤30 min (not strict). |
| Import source | IPS (SFTP/XML → S3) | 2 new services (replacing IPS this year) | Source system changing within the year. |
| Export | **None** | None | Merchants fulfil via Merchant Portal. |
| ---**HISTORICAL MIGRATION**--- ||||
| Entities | Customer/dealer relations, customer rebate classes, open orders | — | Volumes not quantified — see TODO. |
| ---**ENVIRONMENTS**--- ||||
| Staging | Dev, Stage, Prod | same | — |

> **TODO:** Orders/day, order-lines/day, seasonality, historical-migration volumes, and per-market dealer/merchant counts are not in the TAD. Owner: Daimler business + migration team.

## Testing & Environment Strategy

Dev / Stage / Prod promotion path. Given the import scale and 10 external integrations, production-like testing must cover: (1) full-volume XML import timing (~1.5M tags ≤30 min) with realistic files; (2) contract/mocking for all 10 external systems, especially phased DIMS availability; (3) DMS behaviour across multiple stores; (4) dealer-scoped pricing/visibility correctness.

> **TODO:** CI/CD topology, automated test levels, and external-system sandbox availability are not specified. Owner: Spryker delivery + Daimler IT.

## Quality Scenarios

### Performance

| Scenario | Stimulus | Response | Measure |
|----------|----------|----------|---------|
| Full XML import | ~1.5M-tag full per-store XML lands on S3 | Split, imported, published to Redis | ~30 min (not strict), no OOM |
| Dealer-scoped PLP | Customer browses catalog for active dealer | Only that dealer's products/prices shown | Correct scoping, 100% of requests |
| Availability lookup | PDP requests live stock | DIMS availability returned or graceful fallback | Sub-second UX with adapter timeout |
| MP concurrency | ~5,000 dealer users active | Merchant Portal responsive | No degradation at stated concurrency |

### Data Isolation

| Scenario | Stimulus | Response | Measure |
|----------|----------|----------|---------|
| Market BO access | Market user opens BO data grids | Sees only own market's data | 0 cross-market leakage |
| HQ oversight | HQ user opens monitoring | Sees all 22 markets' import/dump/revenue status | All markets visible |

---

*Corresponds to [arc42 Section 10](https://docs.arc42.org/section-10/)*
