# Risks and Technical Debt

Known risks and technical debt for NORMA, drawn from the TAD's own risks/challenges/open questions. Every Spryker-capability mitigation names the feature and links its docs.

## Technical Risks

| Risk | Impact | Mitigation |
|------|--------|------------|
| **Strict, compressed timeline** — Technical Go-Live 2024-09-09, Public 2024-09-23; TAD started only ~2 months before go-live | Inaccuracies, late-found blockers | Technical details aligned within the TAD; prioritise import/P&S + storefront performance + payment E2E validation on Staging first. |
| **DWH integration — no native external-DB support** | BI data access blocked | Cloud team confirmed a secure **site-to-site VPN** DB connection; alternatively the **Data Exchange API** (REST over DB entities, [docs](https://docs.spryker.com/docs/integrations/spryker-glue-api/backend-api/data-exchange-api/data-exchange-api)). VPN requirement is Cloud-network guidance, not a Data Exchange doc — confirm with Cloud/account team. See [SD-002](04-solution-designs/sd-002-dwh-integration-external-db-access.md). |
| **Large set of storefront 3rd-party (JS) integrations** | End-user performance degradation; cross-service conflicts | Measure page-load impact; validate no cross-service conflicts; consent-gate via Usercentrics; isolate failing widgets ([§8](08-crosscutting-concepts.md)). |
| **Data import of products / P&S at 60k scale** | Full sync / high volume can degrade performance | Apply **Data Import & P&S optimization guidelines** ([docs](https://docs.spryker.com/docs/dg/dev/data-import/latest/data-import-optimization-guidelines)): batch/PDO, pre-gather lookups, multi-queue publishers, chunk tuning; benchmark per volume before go-live. |
| **2,000+ active categories** | Load-time degradation building category nodes / fulfillment storages | Performance checks + testing to measure impact before go-live; tune P&S category-storage publishers. |
| **Payone COINs** | Impacts business requirements; **not a go-live blocker** | COIN development on track; topic detail TBD. |
| **Encrypted-password migration from Shopware — under clarification** | Customers may be unable to log in post-migration | Confirm hashing scheme; rehash-on-login strategy. [SD-001](04-solution-designs/sd-001-shopware5-data-migration.md). |
| **Historical orders/returns from DWH — under clarification** | Missing order/return history at go-live | Clarify scope/volumes/timeline with Norma24; may defer non-critical history. |
| **Carrier return labels not OOTB** | Returns flow incomplete if custom integration slips | Build as custom OMS command on the return transition (Return Management PBC, [docs](https://docs.spryker.com/docs/pbc/all/return-management/latest/base-shop/return-management-feature-overview)); stub until DHL/DPD/GLS credentials ready. [ADR-003 neighbour]. |
| **External search delegation (SiteSearch360) not a named feature** | Search behaviour/relevance risk | Custom search-client integration alongside OOTB **Search PBC** ES ([docs](https://docs.spryker.com/docs/pbc/all/search/latest/search)); clearly split full-text (SiteSearch360) vs category pages (ES). [ADR-003](09-architecture-decisions/adr-003-sitesearch360-plus-ootb-search.md). |
| **Drop-shipment not a named Spryker feature** | Merchant fulfilment model ambiguity for +…Y | Realise via Marketplace merchant-order + **Merchant state machine** ([docs](https://docs.spryker.com/docs/pbc/all/order-management-system/latest/marketplace/marketplace-order-management-feature-overview/marketplace-and-merchant-state-machines-overview/marketplace-and-merchant-state-machines-overview)); confirm flow for target release. |

## Technical Debt

| Item | Impact | Plan |
|------|--------|------|
| **Full import built on delta path** | Initial/full loads reuse delta mechanics; may be slower or awkward for bulk | Accepted for Phase 1 ([ADR-004](09-architecture-decisions/adr-004-delta-only-import-strategy.md)); revisit a dedicated bulk path if delta-based full import underperforms. |
| **Multi-store topology deferred** | +…Y 4-country expansion has no infra/topology decision yet | Produce a multi-store SD before expansion; decide DMS + shared-vs-separate infra ([§7](07-deployment-view.md)). |
| **Several TBDs carried from TAD** | Concurrent-user, BO-user, feed/DWH frequency, COIN topic unknown | Track and resolve during Phase 1 discovery; do not fabricate for sizing. |
| **Payone ACP state machine not project-customizable (per docs note)** | Limited ability to tailor payment states | Use the OOTB foreign/ACP payment state machine as-is; monitor Spryker updates. |

---

*Corresponds to [arc42 Section 11](https://docs.arc42.org/section-11/)*
