# Deployment View

The POC targets a standard **Spryker Cloud (PaaS)** deployment. Nothing about hosting is unusual for a
POC; the notable part is the **Dynamic Multi-Store** setup that serves three country stores from one
codebase/region.

## Infrastructure Overview

Standard Spryker Cloud managed services:

| Component | Technology | Purpose |
|-----------|-----------|---------|
| **Application (Yves / Zed)** | Spryker Cloud PaaS | Storefront + backend runtime |
| **Database** | Managed MariaDB/MySQL | Orders, customers, companies, cart |
| **Cache / KV** | Managed Redis/Valkey | Sessions, key-value storage (per store) |
| **Search** | Managed OpenSearch | Catalog search (minimal — services are not a Spryker catalog) |
| **Message Queue** | RabbitMQ | OMS async commands + Publish & Sync |
| **Scheduler** | Jenkins | `oms:check-timeout` / `oms:check-condition`, P&S workers |
| **Object Storage** | S3 | Import/export & media (limited use in POC) |
| **SMTP** | External mail service | Order-confirmation email |

> **TODO:** confirm the exact Spryker Cloud plan/region and outbound network path (allow-listing) to
> reach CIAM/FMAT/SECM/PIM-SPISA — the four integrations require egress connectivity that must be
> agreed with Scania and Spryker Cloud networking.

## Environments

| Environment | Purpose | POC notes |
|-------------|---------|-----------|
| Development | Build + integrate | Uses **mock APIs** for the four systems until real specs/endpoints arrive (see §11 risk). |
| > **TODO:** Staging / Demo | Stakeholder demo of the end-to-end flow | Topology and count TBD. |

> **TODO:** full non-production environment topology, CI/CD pipeline, and test-data strategy are not
> specified in the TAD (see §10 Testing & Environment Strategy).

## Multi-Store Setup (Dynamic Multi-Store)

The POC uses **Dynamic Multi-Store (DMS)** — multiple stores within one region, managed from a single
Back Office and served through the standard Storefront (store selected via the `Store` header /
switcher), rather than separate deployments per country.
[docs](https://docs.spryker.com/docs/pbc/all/dynamic-multistore/latest/base-shop/dynamic-multistore-feature-overview)

| Store | Country | Languages | Currency | Phase |
|-------|---------|-----------|----------|-------|
| **GB** | United Kingdom | EN | GBP | POC |
| **PL** | Poland | PL, EN | PLN | POC |
| **DE** | Germany | DE, EN | EUR | POC |
| CH | Switzerland | DE/FR, EN | CHF | Future |
| BR | Brazil | PT, EN | BRL | Future |

**Setup characteristics (POC):**
- **One region, one codebase**; three stores (GB, PL, DE) created in the Back Office.
- **Global service catalog** and **one global price** across stores — stores may be identical; the goal
  is to prove the capability, not to differentiate markets yet.
- Each store has its own locale set (≤2: native + English) and currency.
- Shared infrastructure (single DB/Redis/OpenSearch/RabbitMQ), store-scoped data via DMS store
  relations. Publish & Sync must run for a store to appear on the Storefront.

> **Maturity note:** the DMS overview carries **no explicit EA/GA label**; treat GA status as
> **uncertain from docs alone** and confirm against the target Spryker release's release notes before
> committing. This is itself part of what the POC validates.
> **Open question (client):** will services/prices differ per country in future? If yes, the "one
> global price / global catalog" assumption changes and per-store price dimensions come into play.

---

*Corresponds to [arc42 Section 7](https://docs.arc42.org/section-7/)*
