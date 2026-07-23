# Risks and Technical Debt

Known risks (with mitigations that name real Spryker features), technical debt, and pre-Go-Live decision gates given the hard deadlines.

## Technical Risks

| Risk | Impact | Mitigation |
|------|--------|------------|
| **ACP sunset breaks the planned Payone-via-ACP integration** | The TAD plans Payone on ACP, but ACP is being sunset ("no additional activations or changes are allowed"). A launch-blocking correctness/continuity risk. | Use the **direct Payone module** with the **MessageBroker** event bus for capture/status, and OMS commands for transitions ([ADR-001](09-architecture-decisions/adr-001-payone-direct-module-over-acp.md)). Docs: [Payment Service Provider](https://docs.spryker.com/docs/pbc/all/payment-service-provider/latest/base-shop/third-party-integrations/payone/app-composition-platform-integration/payone-acp-app), [migrate off ACP](https://docs.spryker.com/docs/pbc/all/payment-service-provider/latest/base-shop/third-party-integrations/stripe/migrate-from-acp-to-stripe). |
| **DWH integration** — Spryker has no default external-DB connection | Cannot feed BI without a chosen mechanism | Align with **Spryker Cloud** on a secure **site2site VPN** DB connection (confirmed feasible) or the **Data Exchange API** (REST over Spryker DB) ([ADR-004](09-architecture-decisions/adr-004-dwh-access-mechanism.md)). Docs: [Data Exchange API](https://docs.spryker.com/docs/integrations/spryker-glue-api/backend-api/data-exchange-api/data-exchange-api). |
| **Large set of storefront third-party JS integrations** | ~15 JS widgets can degrade Core Web Vitals; cross-service conflicts | Consent-gate via **Usercentrics**; enforce a performance budget; measure impact and validate no conflicts before Go-Live ([Section 8](08-crosscutting-concepts.md)). |
| **Product / P&S import performance** | 60k+ products and full-catalog build on delta can degrade P&S | Apply **Data Import optimization guidelines** (batch, CTE bulk, bulk event triggering) and **P&S chunked processing** (research §11). Docs: [Data Import optimization](https://docs.spryker.com/docs/dg/dev/data-import/latest/data-import-optimization-guidelines). |
| **2,000+ active categories** | Navigation/PLP build and load-time degradation (nodes / storages) | Load-test category navigation and PLPs before Go-Live; tune category storage/publish. |
| **SiteSearch360 has no packaged connector** | Custom search integration risk | Follow the documented **"integrate any search engine"** pattern (QueryInterface + response mapping); keep OOTB ES for PLPs ([ADR-002](09-architecture-decisions/adr-002-external-full-text-search-sitesearch360.md)). Docs: [Integrate any search engine](https://docs.spryker.com/docs/pbc/all/search/latest/base-shop/tutorials-and-howtos/tutorial-integrate-any-search-engine-into-a-project). |
| **Carrier return labels are not OOTB** | Returns Go-Live depends on custom label generation | Implement as a **custom OMS command** on the return sub-process calling DHL/DPD/GLS ([SD-002](04-solution-designs/sd-002-returns-portal-and-carrier-labels.md)). Docs: [Return Management](https://docs.spryker.com/docs/pbc/all/return-management/latest/base-shop/return-management-feature-overview). |
| **Strict deadlines; TAD started ~2 months before Go-Live** | Late-discovered blockers threaten the fixed dates | Track launch-blocking unknowns as **decision gates** (below); keep at-risk ADRs `Proposed` until closed. |
| **Payone COINs** | Business-requirement impact; **not** a Go-Live blocker | COINs development on track; delivered as OMS transitions. |

## Decision Gates (pre-Go-Live)

Launch-blocking unknowns promoted to gates because the Go-Live dates are fixed.

| Gate | Question | Spike / Action | Owner | Needed by |
|------|----------|----------------|-------|-----------|
| **G1 — Payone direct module** | Is the direct Payone module (non-ACP) available and viable for NORMA's methods + COINs? | Confirm module availability; POC authorize/capture over MessageBroker | Spryker + Norma24 | Before payment build; **hard blocker** |
| **G2 — DWH access mechanism** | site2site VPN DB connection vs Data Exchange API? | Spryker Cloud confirmation + connectivity spike | Spryker Cloud + Norma24 | Before DWH extract build |
| **G3 — Go-Live readiness** | Are all Phase-1 integrations (import, payment, returns, search, consent) validated on staging? | Integration-readiness checklist ([Section 7](07-deployment-view.md)) fully green | Spryker + KPS + Norma24 | Before 2024-09-09 technical Go-Live |

## Open Questions (carried verbatim from the TAD, owner-named)

> **TODO (Norma24):** Historical **orders** migration from DWH — "under clarification".
> **TODO (Norma24):** Historical **returns** migration from DWH — "under clarification".
> **TODO (Norma24 + Spryker):** **Encrypted passwords** migration from Shopware — "under clarification" (re-hash strategy in [SD-001](04-solution-designs/sd-001-erp-integration-and-data-migration.md)).
> **TODO (Norma24):** Concurrent customers / concurrent BO users at Go-Live — TBD (interim load-test assumption in [Section 10](10-quality-requirements.md)).
> **TODO (Norma24):** Feed Dynamix / Emarsys / Melissa / DWH data volumes — TBD.
> **TODO (Norma24 + Spryker Cloud):** Long-term region strategy (same-region DMS vs per-country regions) — no timeline.
> **TODO (Norma24):** Phase-2 social login / SSO provider(s) — "not yet defined".
> **TODO (Norma24):** Long-term Marketplace merchant/offer counts (300 dropshippers) — TBD.

## Technical Debt

| Item | Impact | Plan |
|------|--------|------|
| **Search split (SiteSearch360 + ES)** | Two search backends to keep in sync; feed freshness independent of P&S | Monitor feed lag; document the boundary; revisit if SiteSearch360 coverage can extend to PLPs |
| **Custom carrier-label OMS commands** | Per-carrier API maintenance (DHL/DPD/GLS) | Isolate carrier logic behind a common command interface; add per-carrier tests |
| **Delta-only import (no bulk feed)** | Full-catalog build is a long chain of deltas | Keep the cursor/idempotency robust; add a controlled "replay" path for catalog rebuilds |
| **Single environment topology (1 staging)** | Limited parallel test streams | Add non-prod environments if Phase 2 scope grows |

---

*Corresponds to [arc42 Section 11](https://docs.arc42.org/section-11/)*
