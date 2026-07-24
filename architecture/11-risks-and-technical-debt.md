# Risks and Technical Debt

Known risks, deliberate POC debt, launch-blocking decision gates, and the client open questions carried verbatim from the TAD.

## Technical Risks

Mitigations name real Spryker features and link the shared research where relevant.

| Risk | Impact | Mitigation |
|------|--------|------------|
| **External API specs not available** — all four integrations (CIAM, FMAT, SECM, PIM-SPISA) need client specs; none exist. | Integration build cannot start; timeline at risk. | Request specs early; **build against mock APIs** so the end-to-end flow proceeds; block integration until specs land. Tracked in the [integration-readiness checklist](07-deployment-view.md). |
| **Dynamic pricing architecture** — no proven OOTB pattern for a fully dynamic, API-sourced priced line item in cart/checkout. | The flagship capability may need rework; POC-blocking. | Technical discovery via **SD-001**; evaluate the 3 options; spike early. Decision gated by **ADR-004** (stays Proposed until the spike closes). |
| **Configurable Product compatibility** — unknown whether PIM-SPISA can act as an external configurator. | Service wizard (Future) may need a fully custom build. | Discovery via **SD-002** grounded in Spryker **Configurable Product** ([research §5](https://docs.spryker.com/docs/pbc/all/product-information-management/latest/base-shop/feature-overviews/configurable-product-feature-overview/configurable-product-feature-overview)); custom wizard fallback if incompatible. |
| **FMAT post-order = 2 separate integrations** — two async calls, two specs, two OMS points. | Partial delivery / spec dependency. | Request both specs; model as **two independent OMS commands** on transitions ([research §12](https://docs.spryker.com/docs/pbc/all/order-management-system/latest/base-shop/state-machine-cookbook/state-machine-cookbook-state-machine-fundamentals)); call #2 is Future. |
| **Role mapping complexity** — 6–8 CIAM roles → Company Roles, unknown until spec; claims→role mapping is not GA. | Auth/ACL rework. | Start with **1:1 mapping** for a POC subset via **Company Account roles** ([research §7](https://docs.spryker.com/docs/pbc/all/customer-relationship-management/latest/base-shop/company-account-feature-overview/company-user-roles-and-permissions-overview)); mapping implemented at project level (ADR-001). |
| **No docs for old Scania project** — previous CIAM/FMAT implementation undocumented. | Slower integration; hidden assumptions. | Allocate reverse-engineering time; review the code as a pattern source only. |
| **POC scope creep** — risk of expanding beyond feasibility validation. | Timeline/budget overrun. | Strict out-of-scope list ([section 1](01-introduction-and-goals.md)); additions require approval. |
| **External API latency/availability coupling** (no caching) | Poor UX / errors if a system is slow or down. | Accepted for POC (ADR-002); asset caching is a named Future workstream. |

## Deliberate POC Technical Debt

| Item | Impact | Plan |
|------|--------|------|
| **Live-fetch, no caching (assets)** | Latency + availability coupling to FMAT. | Revisit ADR-002; add caching/sync in Future. |
| **Exceptions instead of graceful error handling** | Rough UX on external failures. | Graceful error handling is a Future workstream. |
| **Invoice-only, no PSP** | Not a real payment flow. | Real PSP integration is Future (revisit ADR-003). |
| **Sole merchant, no Merchant Portal** | Not exercising marketplace multi-merchant. | Infra kept ready; multi-merchant is Future. |
| **Dynamic service modelled outside catalog** | Non-standard cart item; whichever SD-001 option is chosen carries trade-offs. | Confirm via ADR-004 before build; document ownership/read-write rules (SD-001). |

## Launch-Blocking Decision Gates

These must close before the corresponding build workstream starts.

| Gate | Decision | Spike / SD | Owner | Target |
|------|----------|-----------|-------|--------|
| **G1 — Dynamic service/price model** | Which of the 3 options (virtual product / SSP service-request extension / pre-sync + dynamic price) | SD-001 spike → **ADR-004** (Proposed) | Solution architect (Driver) | > **TODO:** set date at kickoff |
| **G2 — Configurator feasibility** | Can PIM-SPISA drive Configurable Product? | SD-002 spike | Solution architect + Client | > **TODO:** set date (Future-facing but affects service-config design) |
| **G3 — Price re-validation policy** | Lock at add-to-cart vs re-validate at checkout | Client decision (Open Q #18) | Client | > **TODO** |

## Open Questions for Client (carried VERBATIM from the TAD)

Owner for all: **Client** (spec/decision required).

1. CIAM — Which CIAM provider?
2. CIAM — Full response payload spec?
3. CIAM — 6-8 predefined roles & permission levels?
4. CIAM — Does CIAM provide purchased/licensed products per customer, format?
5. FMAT — What asset attributes? (VIN, model, year, mileage, location?)
6. FMAT — Max assets per customer?
7. FMAT — Full API spec/docs?
8. FMAT Post-Order — 2 separate specs: (1) order creation, (2) payment + activation date?
9. SECM — Are services categorized?
10. SECM — Price fixed or variable (region, vehicle type, contract)?
11. SECM — Different service types, structure?
12. SECM — Full API spec/docs?
13. PIM-SPISA — Returns wizard structure/steps, or Spryker defines flow?
14. PIM-SPISA — Can act as external configurator endpoint?
15. PIM-SPISA — Full API spec/docs?
16. All Integrations — API protocol (REST, SOAP, GraphQL)?
17. Cart — Can customer change quantity, or always 1 per service per asset?
18. Cart — Price re-validation: lock or re-validate?
19. Checkout — Approval workflow needed? (Suggestion: no for POC)
20. Multi-Store — Will services/prices differ per country in future?

---

*Corresponds to [arc42 Section 11](https://docs.arc42.org/section-11/)*
