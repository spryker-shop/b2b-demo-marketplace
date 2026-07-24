# Architecture Constraints

Constraints that limit design freedom for the Scania Service Sales POC.

## Technical Constraints

| Constraint | Background / Motivation |
|------------|------------------------|
| Latest Spryker B2B Marketplace, new project from scratch | POC must validate the current platform; no legacy Spryker codebase to preserve. |
| Yves (traditional) storefront | Chosen frontend; storefront SSO and dynamic-service UI are built on Yves, not a headless SPA. This matters because storefront Federated Auth is GA while Glue/API-level SSO is **not** ([research §15](https://docs.spryker.com/docs/pbc/all/oauth/latest/federated-authentication)). |
| CIAM is the sole authentication method | Native Spryker login is disabled; every user authenticates through My Scania. Implemented via Spryker **Federated Authentication (OAuth2/OIDC)** — storefront-supported and GA. |
| Four external systems, all specs missing | CIAM, FMAT, SECM, PIM-SPISA are all **client-owned, no specs available yet**. Protocol assumed REST but unconfirmed. Blocks integration build until specs/mocks exist. |
| Services & prices live outside the Spryker catalog | SECM owns service availability and price per vehicle; there is no proven OOTB Spryker pattern for a fully dynamic, API-sourced priced line item — this is the flagship design problem (SD-001). |
| Dynamic Multistore (DMS), one store per country | GB/PL/DE for POC. DMS is **GA since release 202410.0** ([research §1](https://docs.spryker.com/docs/pbc/all/dynamic-multistore/latest/dynamic-multistore)); DMS-on changes URL/region, RabbitMQ vhost, Jenkins, and console `--store` semantics. |
| Invoice-only payment, no PSP | POC uses OOTB invoice payment; no payment gateway integration (ADR-003). |
| Post-order notifications via OMS, fire-and-forget | Two async FMAT calls modelled as OMS commands on transitions ([research §12](https://docs.spryker.com/docs/pbc/all/order-management-system/latest/base-shop/state-machine-cookbook/state-machine-cookbook-state-machine-fundamentals)). |
| No caching of assets for POC | Assets are live-fetched from FMAT on every request (ADR-002); acceptable only because POC volumes are small. |

## Organizational Constraints

| Constraint | Background / Motivation |
|------------|------------------------|
| POC, not production | Feasibility validation is the deliverable; production-hardening (error handling, PSP, caching, multi-merchant) is explicitly deferred. |
| Strict out-of-scope list; additions need approval | Guards against scope creep (a named risk); every capability tagged POC / Future / TBD in [section 1](01-introduction-and-goals.md). |
| Client owns all external system specs and access | Delivery pace is gated by when Scania supplies specs, sandboxes, and role definitions. |
| Existing Scania CIAM/FMAT implementation has no documentation | Reverse-engineering time must be budgeted; code is reviewable but undocumented. |

## Conventions

| Convention | Background / Motivation |
|------------|------------------------|
| Spryker architecture & module conventions | Layered Zed (Presentation/Communication/Business/Persistence), Client for external HTTP, Facade-only cross-module calls, Transfer Objects as DTOs, dependency injection (no `new` in business logic), no deprecated Bridges — per project `CLAUDE.md`. |
| Project namespace `Pyz` (and custom namespaces) for overrides/extensions | Custom Scania modules live under `src/Pyz/{Layer}/{Module}/`; core modules in `vendor/spryker*` are the pattern source. |
| PSR-4, PHP typing, static analysis (PHPStan/PHPCS) | Standard Spryker code-quality gates. |
| arc42 + C4 for architecture documentation | This document set; diagrams as code (Mermaid/PlantUML). |
| ISO dates in ADRs; one decision per ADR | ADR discipline (see [09](09-architecture-decisions/README.md)). |

---

*Corresponds to [arc42 Section 2](https://docs.arc42.org/section-2/)*
