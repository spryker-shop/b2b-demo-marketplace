# ADR-001: Payone via direct module (not ACP)

## Status

**Proposed** (2024-07-25) — launch-blocking; stays Proposed until decision gate **G1** closes (confirm direct Payone module availability). See [Section 11](../11-risks-and-technical-debt.md).

## Context

The NORMA TAD plans the Payone payment integration on the **App Composition Platform (ACP)**. However, Spryker is **sunsetting ACP**: *"The Spryker App Composition Platform is being sunset. No additional activations or changes are allowed."* Spryker now publishes `migrate-from-acp-to-*` guides moving PSP integrations to **direct `spryker-eco/*` modules** (research §13).

NORMA has fixed Go-Live dates (technical 2024-09-09, public 2024-09-23) and needs a payment integration that is buildable now and supportable long-term. Volume is 4,000–6,000 payment messages/day, plus Payone COINs.

## Decision

We will integrate **Payone via the direct Payone module**, not via ACP. Capture/status events are handled over the **MessageBroker** event bus, and OMS **commands** on order transitions execute the payment side-effects (research §12). COINs are modelled as additional OMS transitions.

This decision remains **Proposed** until gate **G1** confirms the exact direct-module availability and viability for NORMA's payment methods.

## Consequences

### Positive
- Avoids building on a sunset platform that accepts "no additional activations or changes".
- Keeps payment side-effects on the OMS with async, retry-safe semantics.
- Aligns with Spryker's documented direction (migrate off ACP).

### Negative
- Requires confirming and possibly adapting the direct Payone module (and COIN support) under time pressure — a launch-blocking dependency.
- More project-side wiring than a low-code ACP app would have required.

---

*Corresponds to [arc42 Section 9](https://docs.arc42.org/section-9/)*
