# Deployment View

Infrastructure for the Scania Service Sales POC. Standard Spryker Cloud Commerce / PaaS-style topology is sufficient; the notable dimension is **Dynamic Multistore** across GB/PL/DE and the four external-system integrations.

## Infrastructure Overview

### POC Environment

| Component | Technology | Purpose |
|-----------|-----------|---------|
| **Application (Yves/Zed/Glue)** | Spryker on containerized PaaS (Spryker Cloud) | Application runtime |
| **Database** | MySQL/MariaDB (managed) | Orders, customers, companies, quotes, dynamic service items |
| **Cache / KV** | Redis/Valkey (managed) | Sessions, KV storage (per store) |
| **Search** | OpenSearch (managed) | Search index (minimal POC use) |
| **Message Queue** | RabbitMQ | Async OMS transitions, Publish & Sync; **per-region vhost** under DMS |
| **Scheduler** | Jenkins + PHP | OMS process cron, queue workers |
| **Mailer** | SMTP relay | Order-confirmation email |
| **Outbound integration** | HTTPS to CIAM / FMAT / SECM / PIM-SPISA | External system calls (all client-owned) |

> **TODO:** Confirm the concrete hosting model (Spryker Cloud Commerce vs self-managed) and the sandbox/mock endpoints for the four external systems. Owner: Client + Spryker delivery.

### Deployment Pattern

- Standard Spryker deploy pipeline; containerized application; managed data stores.
- **DMS-on deploy specifics** ([research §1](https://docs.spryker.com/docs/pbc/all/dynamic-multistore/latest/dynamic-multistore)): `SPRYKER_DYNAMIC_STORE_MODE: true`, region-scoped `regions:` block, `-r dynamic-store` install recipe; URLs carry the **region** (e.g. `…eu…`) not the store; store switching happens within the region via the `_store` param.
- One codebase, one region for the POC (all POC stores — GB/PL/DE — are in the same region and can be near-identical).

## Multi-Store Strategy (Dynamic Multistore)

One store per country. DMS is **GA since 202410.0** so this is a supported, no-redeploy store model managed from the Back Office (Administration → Stores).

| Store | Country | Languages | Currency | Phase |
|-------|---------|-----------|----------|-------|
| GB | United Kingdom | EN | GBP | POC |
| PL | Poland | PL, EN | PLN | POC |
| DE | Germany | DE, EN | EUR | POC |
| CH | Switzerland | DE/FR, EN | CHF | Future |
| BR | Brazil | PT, EN | BRL | Future |

**Setup rules for POC:**
- All stores share the **global service catalog** and **one global price** (SECM returns price; POC does not vary price per country).
- ≤ 2 languages per store; currencies GBP/PLN/EUR (+CHF, BRL Future).
- Back Office / Merchant Portal operate across **all stores in a region** (no store context); Storefront requires a store context (default if none). DMS-on console commands use `--store=DE`; store-aware commands implement `StoreAwareConsole`.
- CH and BR add currency/locale only — **no re-architecture** required to add them (a Future stores workstream).

> **Open question (carried):** will services/prices differ per country in future? (Open Q #20). If yes, SECM price handling and the dynamic-item price source (SD-001) must become store-aware. Owner: Client.

## Integration-Readiness Pre-Integration Checklist

Because every integration is blocked on client input, this checklist gates the integration workstreams.

| Item | Owner | Status |
|------|-------|--------|
| CIAM provider identified + OAuth2/OIDC endpoints | Client | > **TODO** (Open Q #1) |
| CIAM full response payload + 6–8 role definitions | Client | > **TODO** (Open Q #2, #3) |
| FMAT asset API spec + attributes + max assets/customer | Client | > **TODO** (Open Q #5, #6, #7) |
| FMAT post-order specs (#1 order creation, #2 payment+activation) | Client | > **TODO** (Open Q #8) |
| SECM service API spec + categorization + price model | Client | > **TODO** (Open Q #9, #10, #11, #12) |
| PIM-SPISA detail API + configurator capability | Client | > **TODO** (Open Q #13, #14, #15) |
| API protocol confirmed for all four (REST/SOAP/GraphQL) | Client | > **TODO** (Open Q #16) |
| Sandboxes or agreed **mock APIs** for each system | Client + Spryker | > **TODO** (Risk mitigation) |
| Access to the existing (undocumented) Scania CIAM/FMAT code | Client | > **TODO** |

## First-Pass Infra Sizing

POC volumes are small and performance is explicitly non-critical (see [section 10](10-quality-requirements.md)); a **single standard Spryker environment per region** is sufficient. The load-shaping factor is **external API latency**, not Spryker throughput — every asset/service page makes synchronous outbound calls, so response time tracks CIAM/FMAT/SECM/PIM-SPISA. No horizontal scaling or read-replica strategy is warranted for the POC; the Future caching workstream (ADR-002 revisit) is where sizing becomes relevant.

---

*Corresponds to [arc42 Section 7](https://docs.arc42.org/section-7/)*
