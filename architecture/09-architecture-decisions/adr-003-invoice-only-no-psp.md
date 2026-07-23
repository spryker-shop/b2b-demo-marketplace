# ADR-003: Invoice-only payment, no PSP (POC)

## Status

**Accepted** (2026-07-23) — for the POC.

## Context

The POC must demonstrate a complete purchase, but real payment processing is not part of proving feasibility of the service-sales flow. Invoice generation itself is handled **externally** (FMAT or manual), not in Spryker. Integrating a Payment Service Provider (PSP) would add scope (provider selection, webhooks, 3-D Secure, reconciliation) with no bearing on the questions the POC answers.

## Decision

We will use Spryker's **out-of-the-box invoice payment method only**, with a simple "Place Order" button and **no PSP integration** and **no approval workflow** for the POC. Real PSP integration is a Future workstream.

## Consequences

### Positive
- Checkout is OOTB and fast to deliver; no payment-provider dependency.
- No PCI/PSP concerns during the POC.
- Keeps focus on the dynamic-service and integration questions.

### Negative
- Not a real payment experience; cannot validate PSP-specific behaviour.
- The second FMAT post-order notification ("payment + activation date") has no real payment event to key off in the POC — it is Future and will need a real payment trigger.

> **Revisit trigger:** moving toward production or needing real settlement → add a PSP module (prefer a direct `spryker-eco` PSP module; note ACP is being sunset per shared research).

---

*Corresponds to [arc42 Section 9](https://docs.arc42.org/section-9/)*
