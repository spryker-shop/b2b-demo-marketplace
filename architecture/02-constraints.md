# Architecture Constraints

Constraints that limit design freedom for the Scania Service Sales POC. Most are POC-scoping
decisions; several are hard external dependencies (the four integration specs).

## Technical Constraints

| Constraint | Background / Motivation ("why") |
|------------|--------------------------------|
| Spryker B2B Marketplace, **latest version**, new project from scratch | POC must prove the current platform is a viable foundation; greenfield avoids legacy baggage. |
| **Yves (traditional) frontend** | TAD mandates Yves; mobile-responsive OOTB, standard theme + a few custom pages. No design system / wireframes yet. |
| **SSO via CIAM is the only authentication** — no Spryker-native login | Identity is owned by My Scania (CIAM); duplicating credentials in Spryker is out of scope and against the single-IdP intent. Requires a **custom OAuth customer authentication strategy** (OOTB B2C auto-create strategy is insufficient for B2B — no company context). [docs](https://docs.spryker.com/docs/pbc/all/oauth/latest/storefront-account-bootstrapping) |
| **Live fetch, no caching** for assets/services/prices | POC prioritises proving integration over performance; caching/sync is Future. External API latency directly affects UX — accepted for POC. |
| **Invoice only, no PSP** | Payment integration is deferred; OOTB invoice + "Place Order" is enough to complete the order flow. |
| **API protocol for all four integrations is TBD** | REST assumed ("REST (Sync)") but SOAP/GraphQL not ruled out — no specs yet. Client modules must not hard-assume REST specifics. |
| **All four external API specs unavailable at TAD time** | Integration cannot be finalised until CIAM/FMAT/SECM/PIM-SPISA specs arrive; mock APIs are needed to unblock. (Top project risk — see §11.) |
| **Dynamic Multi-Store (DMS)**, one store per country | Prove multi-market capability from one codebase/region. DMS models multiple stores within one region served via the `Store` header. GA status not explicitly labelled in docs — confirm against target release. [docs](https://docs.spryker.com/docs/pbc/all/dynamic-multistore/latest/base-shop/dynamic-multistore-feature-overview) |
| **No OMS customization** for the POC | Basic OOTB statuses are enough; the only OMS extension points used are commands for the FMAT post-order calls + confirmation email. [docs](https://docs.spryker.com/docs/pbc/all/order-management-system/latest/base-shop/state-machine-cookbook/state-machine-cookbook-state-machine-fundamentals) |
| **No graceful error handling for POC** | On customer-creation or integration failure the POC may fail with an exception; robust handling is Future. |

## Organizational Constraints

| Constraint | Background / Motivation ("why") |
|------------|--------------------------------|
| POC engagement, **strict out-of-scope list** | Scope creep is a named risk; additions require explicit approval to protect the demo timeline. |
| Dependency on **four external teams** (CIAM, FMAT, SECM, PIM-SPISA) for specs | Integration work is gated on specs Scania must provide; slippage blocks the POC. |
| **No documentation for the old Scania project** (code only) | CIAM/FMAT patterns may be reused, but require reverse-engineering time budgeted in. |
| > **TODO:** team size, delivery cadence, timeline | Not specified in the TAD. |

## Conventions

| Convention | Background / Motivation ("why") |
|------------|--------------------------------|
| Spryker coding standards & module structure | Standard platform maintainability; one **Client module per external system** for isolation. |
| arc42 + C4 + ADR/SD (this folder) | Architecture-as-code standard for the project. |
| One global service **price** and one **global catalog** across stores (POC) | Simplifies DMS proof; per-country price/catalog differences are a Future question for the client. |
| Post-order FMAT calls implemented as **OMS commands** on transitions | Documented Spryker pattern for post-order external side-effects. [docs](https://docs.spryker.com/docs/pbc/all/order-management-system/latest/base-shop/state-machine-cookbook/state-machine-cookbook-state-machine-fundamentals) |

---

*Corresponds to [arc42 Section 2](https://docs.arc42.org/section-2/)*
