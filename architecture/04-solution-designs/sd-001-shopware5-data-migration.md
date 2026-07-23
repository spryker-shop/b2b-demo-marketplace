# SD-001: Shopware 5 → Spryker Data Migration

## Metadata

| Field | Value |
|-------|-------|
| **Status** | Draft |
| **Date** | 2024-07-25 |
| **Author(s)** | Spryker architecture (Yevhen Romanov, Vitalii Ivanov) |
| **Stakeholders** | Norma / KPS (Vitali Adam, Sonja Bouwers), Norma24 (DWH owner) |

## Problem Statement

NORMA re-platforms its non-food B2C shop from **Shopware 5** (end of support) to Spryker. Existing customers must be able to log in and see relevant history from day one. Multiple migration inputs are still **under clarification**, which is a go-live risk on the compressed timeline (Technical Go-Live 2024-09-09).

## Goals & Requirements

### Functional Requirements
- Migrate **customers** from Shopware 5 into Spryker.
- Migrate **encrypted passwords** from Shopware so customers can log in without a forced reset — *approach under clarification*.
- Migrate **historical orders** from the DWH — *under clarification*.
- Migrate **historical returns** from the DWH — *under clarification*.

### Non-Functional Requirements
- Performance: bulk-load ~3,000,000 customer records within the migration window.
- Security: preserve password confidentiality; rehash to Spryker's scheme where possible.
- Correctness: idempotent, re-runnable migration; verifiable record counts.

### Constraints
- Catalog is **not** part of this migration — products/prices/stock/categories flow live from Plentymarkets via Talend (see [ADR-004](../09-architecture-decisions/adr-004-delta-only-import-strategy.md)).
- DWH is the source for historical orders/returns and is itself an access-constrained system ([SD-002](sd-002-dwh-integration-external-db-access.md)).

## Proposed Solution

### Overview
A one-off migration toolchain that loads customers (with encrypted passwords) from Shopware and historical orders/returns from the DWH, staged and validated on Staging before cut-over.

### Key Components

| Component | Responsibility | Technology |
|-----------|----------------|------------|
| Customer migration importer | Load Shopware customers into `spy_customer` | Spryker Data Import (batch/PDO) |
| Password strategy | Verify Shopware hash on first login, rehash to Spryker scheme | Custom auth plugin |
| Order/return backfill | Load historical orders/returns from DWH | Data Import / API, via VPN or Data Exchange |

### Integration Points
- **Shopware 5:** export of customers + password hashes (format TBD).
- **DWH:** historical orders/returns via the [SD-002](sd-002-dwh-integration-external-db-access.md) access path.

## Trade-offs & Considerations

### Advantages
- Customers keep their logins (no mass reset) if the password scheme is compatible.

### Disadvantages / Risks
| Risk | Impact | Mitigation |
|------|--------|------------|
| Shopware password hash incompatible with Spryker | Forced password resets | Rehash-on-login; fallback to reset email campaign |
| DWH order/return scope unclear | Missing history at go-live | Clarify scope; defer non-critical history to post-go-live |
| Timeline | Migration slips past go-live | Freeze scope early; validate on Staging first |

## Open Questions
- Which hashing algorithm does Shopware 5 use, and can Spryker verify/rehash it?
- What order/return history depth is required at go-live vs deferrable?
- Are historical orders needed as full Spryker orders or read-only records?

## Related Documentation
- ADRs: [ADR-004](../09-architecture-decisions/adr-004-delta-only-import-strategy.md)
- SDs: [SD-002](sd-002-dwh-integration-external-db-access.md)

> **TODO (Norma/KPS + Norma24):** resolve all "under clarification" items — encrypted passwords, historical orders, historical returns.

---

*Corresponds to [arc42 Section 4](https://docs.arc42.org/section-4/)*
