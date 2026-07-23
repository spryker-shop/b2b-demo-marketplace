# Deployment View

Infrastructure and deployment topology for NORMA. Phase 1 is a single store hosted in one EU AWS region on Spryker Cloud Commerce OS (PaaS).

## Infrastructure Overview

### Production Environment (Phase 1)

| Component | Technology | Purpose |
|-----------|-----------|---------|
| Region | AWS EU | Single region; EU data residency |
| Application | Spryker Cloud (Yves / Zed / Glue containers) | Storefront, Back Office, Glue Backend API |
| Database | MariaDB (managed) | Single relational database |
| Cache / KV | Redis (managed) | Sessions + storefront read models |
| Search | OpenSearch/Elasticsearch (managed) | Category PLP index (full-text is SiteSearch360) |
| Message broker | RabbitMQ | Events, P&S sync, Payone MessageBroker bus |
| Scheduler | Jenkins | P&S workers, import cron, OMS cron, feed/label jobs |
| Object storage | S3 | Feed files, label blobs, import/export artifacts |

### Deployment Pattern

- **Hosting:** Spryker Cloud Commerce OS (managed PaaS), single EU region.
- **Environments (Phase 1):** Production + 1 Staging. Additional non-prod environments are **TBD**.
- **CI/CD:** Automated pipeline with static analysis, functional tests, and a controlled deploy to staging then production ahead of the 2024-09-09 technical Go-Live.
- **Multi-store mode:** **Off in Phase 1** (single store). See the multi-store strategy below before enabling.

## Multi-Store Strategy

Phase 1 is deliberately single-store; the 4-country expansion is a 2+ year vision with no committed timeline. The region strategy must, however, be decided **before** the first additional country, because it changes URLs, RabbitMQ vhosts, Jenkins jobs, and console-command semantics.

| Aspect | Phase 1 (Go-Live) | Long-term (4 countries, 2+ years) |
|--------|-------------------|-----------------------------------|
| Regions | 1 (EU) | 1 (EU) or per-country — **decision needed early** |
| Countries | 1 (DE) | 4 (DE, AT, FR, CZ) |
| Stores | 1 | 1 per country |
| Languages | 1 (`de_DE`) | 3 |
| Currencies | 1 (EUR) | 2 (EUR, CZK) |
| DB / ES / Redis | Single shared | TBD (shared within a region; separate across regions) |

**Recommended approach:** if all four countries stay in the **same EU region**, use **Dynamic Multistore (DMS)** — GA since release 202410.0 (research §1) — to create/manage the per-country stores from the Back Office without redeploy, sharing one database/ES/Redis within the region. Key implications when DMS is on: region-based URLs (`…eu…`), per-region RabbitMQ vhosts and Jenkins jobs, store passed via the Store HTTP header on APIs, and store-aware console commands (`--store=DE`). Data-import time scales with store count, which matters given the delta-import volume.

> **TODO (Norma24 + Spryker Cloud):** decide same-region-DMS vs per-country-regions before the first non-DE store; this determines whether DMS applies or separate deployments are needed. Docs: [Dynamic Multistore](https://docs.spryker.com/docs/pbc/all/dynamic-multistore/latest/dynamic-multistore).

## First-Pass Infrastructure Sizing

Volumes grow modestly (60k→80k products, 60k→72k visitors/day, 2–3k→2.2–3.2k orders/day over "+…Y"), so Phase 1 does not require aggressive scaling, but a few sizing points follow from the figures:

- **Web/app tier:** size for **60,000 visitors/day at 3% conversion** with a March–August seasonal peak. An interim peak-concurrency assumption is derived in [Section 10](10-quality-requirements.md); auto-scale the Yves/Glue tier to the seasonal peak.
- **Search:** OpenSearch indexes only category PLPs (60–80k products, 2,000+ categories) — modest; the 2,000+ active categories are the watch item for navigation/PLP build time, not raw index size.
- **Redis:** must hold storefront read models for 60–80k products plus sessions for the seasonal peak.
- **P&S / import:** the 10-minute delta cadence is light in steady state; size the workers for the **full-catalog build on delta** (the heaviest run) and the seasonal peak, not the steady-state 50-products/10-min.

## Integration Readiness Checklist (pre-integration)

NORMA is integration-heavy and third-party-dependent; readiness must be tracked centrally, not scattered across sections.

| Item | Owner | Ready by | Status |
|------|-------|----------|--------|
| Glue Backend API auth model agreed with Talend (token / API key / OAuth 2.0) | KPS + Spryker | Before import build | Open |
| Talend delta contract (fields, cursor semantics, idempotency key) | KPS | Before import build | Open |
| **Payone direct-module availability confirmed** (ACP sunset) | Spryker + Norma24 | **Before payment build — launch gate** | Open ([ADR-001](09-architecture-decisions/adr-001-payone-direct-module-over-acp.md)) |
| Payone COINs delivery | Norma24 | Post Go-Live acceptable | On track |
| **DWH access mechanism** (site2site VPN vs Data Exchange API) | Spryker Cloud + Norma24 | Before DWH extract | Open ([ADR-004](09-architecture-decisions/adr-004-dwh-access-mechanism.md)) |
| SiteSearch360 feed + widget contract | Norma24 | Before search cutover | Open |
| DHL/DPD/GLS carrier API credentials + label formats | Norma24 | Before returns Go-Live | Open |
| Consent categories mapped in Usercentrics for all JS widgets | Norma24 | Before Go-Live | Open |
| Historical migration source access (Shopware customers/passwords; DWH orders/returns) | Norma24 | Before migration | **Under clarification** |

---

*Corresponds to [arc42 Section 7](https://docs.arc42.org/section-7/)*
