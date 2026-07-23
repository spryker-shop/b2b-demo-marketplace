# ADR-002: Payone via App Composition Platform (ACP)

## Status

**Accepted** (2024-07-25)

## Context

NORMA needs a single PSP for Phase 1: **Payone**. Payment must integrate with the Spryker OMS (order payment state), keep card data out of Spryker (PCI), and handle 4,000–6,000 payment messages/day. Payone is available as an **ACP payment app**.

## Decision

We will integrate Payone as an **App Composition Platform (ACP) payment app** ([Payone ACP docs](https://docs.spryker.com/docs/pbc/all/payment-service-provider/latest/base-shop/third-party-integrations/payone/app-composition-platform-integration/payone-acp-app)), using iframe/redirect for the customer payment step and the **ACP events bus** for asynchronous payment events. The OMS integrates via the **foreign/ACP payment state machine** ([OMS + ACP integration docs](https://docs.spryker.com/docs/dg/dev/acp/integrate-acp-payment-apps-with-spryker-oms-configuration)), modelling Preauthorization/Capture.

## Consequences

### Positive
- Card data handled by Payone via iframe/redirect (PCI SAQ-A posture).
- Event-driven; decoupled from synchronous checkout.
- Uses the documented ACP + foreign payment state machine pattern.

### Negative
- Per Spryker docs, the Payone ACP app's **state machine is still in development and not yet customizable per project** — we adopt it as-is and monitor updates.
- Depends on ACP events-bus reliability; state-reconciliation needed for missed events.
- **Payone COIN** topic is still TBD (not a go-live blocker).

---

*Corresponds to [arc42 Section 9](https://docs.arc42.org/section-9/)*
