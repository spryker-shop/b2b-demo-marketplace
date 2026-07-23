# Cross-Cutting Concepts

Principles and patterns that span multiple building blocks of the NORMA system.

## Storefront 3rd-party integration & consent management (primary cross-cutting concern)

NORMA carries **~24 external integrations**, most of them **JS-widget storefront** integrations (GTM/GA, Easy Marketing, MS Clarity, Sovendus, Klarna, Jotform, ParcelLab, OMQ, Facebook CAPI, reCAPTCHA v3, Kameleoon, SiteSearch360, Usercentrics). This is itself a system-wide concern:

- **Performance:** every JS widget adds render/blocking cost. The storefront must stay within budget during the March–August main season (60k visitors/day). Impact must be **measured** ([§11](11-risks-and-technical-debt.md) risk).
- **Consent gating:** **Usercentrics** is the consent-management platform; all non-essential tags/widgets must load only after consent (GDPR). Usercentrics is a cross-cutting gate, not a standalone feature.
- **Tag management:** GTM / Easy Marketing centralise tag injection; avoid duplicate/conflicting tags across services (cross-service conflict is a flagged risk).
- **Isolation:** widgets should fail independently — a failing third-party script must not break the page.

## Authentication & Authorization

- **Storefront customers:** native Spryker customer accounts in Phase 1; **social login / SSO is Phase 2 (Red)** and would use the OAuth PBC / an `OauthCustomerAuthenticationStrategyPluginInterface` strategy ([docs](https://docs.spryker.com/docs/pbc/all/oauth/latest/storefront-account-bootstrapping)).
- **Back Office users:** Spryker **User Management** — roles & groups via ACL/Auth/User bundles ([docs](https://docs.spryker.com/docs/pbc/all/user-management/latest/user-management)); best practice is roles→groups→users.
- **Backend API:** Talend and DWH access secured via Glue **Backend API** token auth + authorization scopes ([docs](https://docs.spryker.com/docs/integrations/spryker-glue-api/backend-api/integrate-backend-api/integrate-the-authorization-scopes)).

## Internationalisation & Multi-Store

Phase 1 is single store (de_DE / EUR). The future 4-country expansion is a cross-cutting concern touching catalog, price, CMS and customer scoping — realisable via **Dynamic Multi-Store** ([docs](https://docs.spryker.com/docs/pbc/all/dynamic-multistore/latest/base-shop/dynamic-multistore-feature-overview)); topology TBD ([§7](07-deployment-view.md)).

## Caching, Publish & Sync

- Storefront reads served from **Redis** (key-value) and **Elasticsearch**, populated by **Publish & Sync** from MariaDB.
- Given the 60k-product catalog and 10-min delta cadence, P&S is tuned per the [optimization guidelines](https://docs.spryker.com/docs/dg/dev/data-import/latest/data-import-optimization-guidelines): **multiple publisher queues**, worker scaling per queue, chunk-size tuning (500–1000), and exporting only necessary data to the KV store.

## Data Import / Export

- **Inbound:** delta-only catalog import from Plentymarkets via Talend → Glue Backend API; full/initial import built on the same delta path ([ADR-004](09-architecture-decisions/adr-004-delta-only-import-strategy.md)). Use batch/PDO over ORM, pre-gather lookups (avoid per-row queries).
- **Outbound:** order + payment delta export every 10 min; product feed export to Feed Dynamix; BI export to DWH.

## Order Management (OMS) as the integration backbone

Several external side-effects are implemented as **OMS commands** bound to state transitions ([OMS docs](https://docs.spryker.com/docs/pbc/all/order-management-system/latest/base-shop/state-machine-cookbook/state-machine-cookbook-state-machine-fundamentals)): order-confirmation email, Payone ACP payment (foreign/ACP state machine), order export trigger, and **carrier return-label generation** (DHL/DPD/GLS — custom). Timeouts/conditions require the periodic `oms:check-timeout` / `oms:check-condition` console commands.

## Error Handling & Resilience

- External calls (carriers, Melissa, feed, CRM) should be fire-and-forget or retried via queue where they are OMS side-effects; a failing external call must not block order placement.
- No-SLA imports mean the pipeline must be **idempotent** (delta re-application must not corrupt state) and observable.

## Security & Compliance

- **GDPR:** EU-region hosting; consent via Usercentrics; 3M customer records.
- **PCI:** Payone ACP iframe/redirect keeps card data out of Spryker (SAQ-A posture).
- **Abuse protection:** reCAPTCHA v3 on forms.
- **Migrated passwords:** encrypted-password migration from Shopware is **under clarification** — rehash-on-login strategy TBD ([SD-001](04-solution-designs/sd-001-shopware5-data-migration.md)).

---

*Corresponds to [arc42 Section 8](https://docs.arc42.org/section-8/)*
