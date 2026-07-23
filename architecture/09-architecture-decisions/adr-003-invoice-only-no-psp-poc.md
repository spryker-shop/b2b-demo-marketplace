# ADR-003: Invoice-Only Checkout, No PSP, for the POC

## Status

**Accepted (for POC)** (2026-07-23)

## Context

The POC must complete an end-to-end purchase, but a real payment integration is out of scope. The TAD
specifies **OOTB checkout with Invoice only (no PSP)**, a single "Place Order" button, **no approval
workflow**, and the **OOTB order-confirmation email**. Invoice generation itself is handled externally
(FMAT or manual), not in Spryker.

## Decision

We will use **OOTB Spryker checkout with an invoice-only payment method** and no PSP integration for the
POC. No approval workflow is added. Order placement triggers OMS side-effects (async FMAT
notification + confirmation email) but no payment authorization/capture. Real PSP integration is a
Future phase.

## Consequences

### Positive
- Completes the order flow with **no PCI/PSP surface** and minimal effort.
- Keeps checkout OOTB — no payment-state OMS customization for the POC.
- Lets the POC focus on the integration story (SSO, assets, services, FMAT notify) rather than payments.

### Negative
- Not a realistic payment flow — production will need a real PSP (ACP payment app or native PSP module),
  which introduces a payment state machine and additional OMS states.
  [docs](https://docs.spryker.com/docs/pbc/all/payment-service-provider/latest/payment-service-provider)
- "Payment + activation date" is exactly the trigger for the **Future** second FMAT post-order call —
  that notification cannot be meaningfully exercised until a real payment step exists.
- No approval workflow means no buyer/approver separation (acceptable for POC; revisit if required —
  open client question).

---

*Corresponds to [arc42 Section 9](https://docs.arc42.org/section-9/)*
