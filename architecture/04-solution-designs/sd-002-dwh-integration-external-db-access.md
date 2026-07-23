# SD-002: DWH Integration — External DB Access

## Metadata

| Field | Value |
|-------|-------|
| **Status** | Draft |
| **Date** | 2024-07-25 |
| **Author(s)** | Spryker architecture (Yevhen Romanov, Vitalii Ivanov) |
| **Stakeholders** | Norma24 (DWH owner), Spryker Cloud team, KPS |

## Problem Statement

NORMA's Data Warehouse (DWH) must receive **all** data for Business Intelligence. Spryker does **not** support external database connections by default, so a supported mechanism to give the DWH access to Spryker data is required. This is a flagged risk in the TAD.

## Goals & Requirements

### Functional Requirements
- Provide the DWH with the data it needs for BI (catalog, orders, payments, customers, etc.).
- Support historical backfill (customers/orders/returns — see [SD-001](sd-001-shopware5-data-migration.md)).

### Non-Functional Requirements
- Security: encrypted, access-controlled transport (EU/GDPR).
- Performance: batch-capable for BI volumes without impacting the storefront DB.

### Constraints
- Spryker Cloud (PaaS) network model governs what external DB access is permitted.
- No native Spryker external-DB connector.

## Proposed Solution

Two candidate paths; the architecture keeps both open pending Cloud confirmation.

### Option A — Site-to-site VPN DB connection (confirmed with Cloud team)
Direct, read-oriented DB access to Spryker over a secure **site-to-site VPN** tunnel, brokered by Talend for the DWH.

- **Pros:** direct DB read for BI; batch-friendly; confirmed feasible by the Cloud team.
- **Cons:** couples DWH to the Spryker schema; VPN requirement is **Cloud-network guidance, not a documented Data Exchange constraint** — must be confirmed with Cloud networking/account team.

### Option B — Data Exchange API (REST over DB entities)
Use the Spryker **Data Exchange API** (Dynamic Entity API on the Backend API) to expose DB entities over REST with no custom code ([docs](https://docs.spryker.com/docs/integrations/spryker-glue-api/backend-api/data-exchange-api/data-exchange-api)).

- **Pros:** no custom code; auth/scopes via Backend API; decoupled from raw schema; batch endpoints for volume ([batch docs](https://docs.spryker.com/docs/integrations/spryker-glue-api/backend-api/data-exchange-api/batch-processing-and-performance-optimization)).
- **Cons:** REST pull vs direct DB read; endpoint/mapping configuration effort; BI-scale extraction patterns to validate.

### Key Components

| Component | Responsibility | Technology |
|-----------|----------------|------------|
| Talend | Broker DWH ↔ Spryker | ETL service bus |
| VPN tunnel *(Option A)* | Secure DB access | Spryker Cloud networking |
| Data Exchange endpoints *(Option B)* | REST DB-entity access | Backend API / Dynamic Entity API |

## Trade-offs & Considerations

### Risks
| Risk | Impact | Mitigation |
|------|--------|------------|
| VPN not actually permitted / mis-scoped | DWH BI blocked | Confirm with Cloud networking team; fall back to Option B |
| Data Exchange throughput insufficient for BI volumes | Slow/failed extracts | Use batch/complex endpoints; benchmark |

## Open Questions
- What exact DWH export scope and frequency are required?
- Is the site-to-site VPN DB connection contractually/technically confirmed for this Cloud environment?
- Does BI require raw DB access or is a curated REST feed acceptable?

## Related Documentation
- SDs: [SD-001](sd-001-shopware5-data-migration.md)
- Research: Data Exchange API (research-docs #10) — VPN requirement flagged UNCERTAIN.

> **TODO (Cloud team + Norma24):** confirm VPN feasibility vs Data Exchange API and finalise DWH export scope/frequency.

---

*Corresponds to [arc42 Section 4](https://docs.arc42.org/section-4/)*
