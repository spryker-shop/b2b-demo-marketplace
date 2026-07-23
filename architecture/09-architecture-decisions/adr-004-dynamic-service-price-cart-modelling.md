# ADR-004: Dynamic service & price in cart — modelling approach

## Status

**Proposed** (2026-07-23) — **launch/POC-blocking decision gate** (G1). Stays Proposed until the SD-001 spike closes.

## Context

Services and their prices do **not** exist in the Spryker catalog — they are returned live by **SECM per vehicle**. Spryker has no out-of-the-box pattern for a fully dynamic, API-sourced priced line item that flows through cart → checkout → order. The cart must also hold multiple assets, allow regular products alongside services, persist between sessions, and apply OOTB vouchers/discounts.

Three options are on the table (explored in **[SD-001](../04-solution-designs/sd-001-dynamic-service-and-price-in-cart.md)**):

- **Option A — Virtual/on-the-fly product:** create a lightweight product representation for the service at add-to-cart, carrying the SECM price and service/vehicle refs.
- **Option B — Extend SSP service-request with checkout:** reuse SSP Service Management primitives to represent the service as a service request that proceeds to checkout.
- **Option C — Pre-sync catalog + dynamic price:** pre-import services as catalog products and fetch/override price dynamically at cart time.

This is the flagship architectural challenge and a **POC-blocking decision**; it cannot be finalized until the SD-001 spike (and the price re-validation and quantity open questions) resolve.

## Decision

We will select **one** of the three options for the POC based on the SD-001 spike. SD-001 currently **recommends Option A (virtual/on-the-fly product)** as the lowest-friction fit for a dynamic, non-catalog priced item with the SECM price **locked at add-to-cart**. This ADR remains **Proposed** — the decision is confirmed only when the spike validates Option A against real SECM behaviour and the price-lock/re-validation policy (Open Q #18) is set.

## Consequences

### Positive
- A single, explicit model for the dynamic service line item, with clear price-source ownership.
- Locking the SECM price at add-to-cart gives a deterministic order total (matches the POC price-lock suggestion).

### Negative
- Whatever option wins carries trade-offs (see SD-001): Option A needs custom cart/quote/order handling; Option B couples to SSP semantics; Option C adds a sync burden and a stale-price risk.
- Decision is blocked on SECM spec and the spike; build of ScaniaDynamicService cannot start until the gate closes.
- If price re-validation (rather than lock) is chosen, the model must add a checkout-time SECM re-read.

## Related

- **[SD-001 — Dynamic service and price in cart](../04-solution-designs/sd-001-dynamic-service-and-price-in-cart.md)** (options, data model, decision gate)
- Decision gate **G1** in [Risks §Decision Gates](../11-risks-and-technical-debt.md)

---

*Corresponds to [arc42 Section 9](https://docs.arc42.org/section-9/)*
