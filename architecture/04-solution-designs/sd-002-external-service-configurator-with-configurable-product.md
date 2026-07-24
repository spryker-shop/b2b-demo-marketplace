# SD-002: External service configurator with Configurable Product

## Metadata

| Field | Value |
|-------|-------|
| **Status** | Draft |
| **Date** | 2026-07-23 |
| **Author(s)** | Spryker delivery team (Scania POC) |
| **Stakeholders** | Solution architect (Driver), Scania PIM-SPISA owner, Client |

## Problem Statement

For the POC, service selection is **basic** (view details, add to cart). The **Future** vision is a 1–2 step **service configuration wizard**. Scania's TAD asks whether Spryker's **Configurable Product** feature can be used, with **PIM-SPISA acting as the external configurator**. This is a feasibility question: Configurable Product supports an **external** configurator app the customer is redirected to, but it is **unknown whether PIM-SPISA exposes a configurator-capable endpoint** (Open Q #13, #14). This SD scopes the approach and the discovery needed.

## Goals & Requirements

### Functional Requirements
- On a service PDP, offer **Configure** for configurable services (Future).
- Redirect to an external configurator (candidate: PIM-SPISA), let the customer configure, and **return** to Spryker with a configuration.
- Add the configured service to the cart as a dynamic service item (integrates with [SD-001](sd-001-dynamic-service-and-price-in-cart.md)).
- Allow editing the configuration from the cart.

### Non-Functional Requirements
- Security: validate the returned configuration server-side; never trust client-tampered payloads.
- Extensibility: fall back to a custom in-Spryker wizard if PIM-SPISA cannot act as a configurator.

### Constraints
- Yves storefront; POC does basic selection only — the wizard is Future.
- Configuration must interoperate with the dynamic, SECM-priced item (SD-001).

## Proposed Solution

### Overview

Use Spryker **Configurable Product** with an **external configurator** integration ([research §5](https://docs.spryker.com/docs/pbc/all/product-information-management/latest/base-shop/feature-overviews/configurable-product-feature-overview/configurable-product-feature-overview)). The documented flow — customer clicks **Configure** on the PDP → external configurator → returns with a configuration → adds the configured item to the cart (editable from the cart) — matches the intended Scania service wizard. The doc even uses a **service** example (choosing a preferred service date/time), which aligns with Scania. Whether **PIM-SPISA** can be that external configurator is the open feasibility question; if not, a custom Spryker-side wizard replaces the external redirect while keeping the same Configurable Product plumbing.

### Architecture

**See diagram:** [Service Browse & Select](../diagrams/sequence/service-browse-select.mmd) · [C3 Component View](../diagrams/c4/c3-component-diagram.mmd)

Flow: **Configure (PDP) → external configurator (PIM-SPISA?) → return with configuration → add configured dynamic item to cart** (price still from SECM per SD-001).

### Key Components

| Component | Responsibility | Technology |
|-----------|----------------|------------|
| Configurable Product (core) | Product type + configuration lifecycle, Configure button, cart edit | Spryker core (PIM) |
| ScaniaSpisaDetail (Client + Zed) | Provide details; carry/validate the configuration payload; drive external configurator handshake | Spryker Client + Zed |
| ScaniaDynamicService (SD-001) | Represent the configured service as a priced cart item | Spryker Zed |

### Integration Points

- **Internal Modules:** Configurable Product configurator plugin wiring; Cart integration (configured item); reuse SD-001's dynamic item.
- **External Systems:** PIM-SPISA as external configurator endpoint (**capability TBD** — Open Q #14) and detail source; SECM for price (SD-001).
- **Data Flows:** redirect out to configurator, redirect back with a signed/validated configuration payload.

### Data Model

Configurable Product stores configuration on the cart/order item as a **configuration instance** (key/values) rather than as bespoke tables; the Scania-specific data is the **configuration payload** plus the service/vehicle/price fields already defined in SD-001.

**Configuration payload (transfer sketch):**

| Transfer | Fields | Notes |
|----------|--------|-------|
| `ScaniaServiceConfigurationTransfer` | `serviceRef`, `vehicleRef`, `configuratorSource` (`'PIM-SPISA'` \| `'internal'`), `selections` (list of key/value), `activationDate` (nullable), `signature` (for validation) | Produced on return from the configurator; validated server-side. |
| Reused: Configurable Product's configuration instance | `configuratorKey`, `configuration` (serialized selections), `isComplete` | OOTB structure attached to the cart/order item. |
| Reused: `ScaniaServiceItemTransfer` (SD-001) | `serviceRef`, `vehicleRef`, `unitPrice`, `priceSource` | The configured selection feeds the SD-001 dynamic item. |

**Persistence:** no new project table is required if OOTB Configurable Product instance storage plus the SD-001 `scania_service_order_item` extension suffice. The configured `selections` can be persisted in the configuration instance / `quote_data` for POC.

> **Named spike (mandatory — the data model cannot be finalized without it):** *"PIM-SPISA configurator feasibility."* Owner: solution architect + Client. Timebox: 5 days. Questions: (1) Does PIM-SPISA expose a redirectable configurator endpoint and a return contract (Open Q #14)? (2) Does it return the wizard structure/steps, or must Spryker define the flow (Open Q #13)? (3) What is the payload/return shape (Open Q #15)? Outcome decides external-configurator vs custom in-Spryker wizard.

## Implementation Plan

### Phases
1. **Spike:** PIM-SPISA configurator feasibility (above).
2. **If external-capable:** wire Configurable Product external configurator to PIM-SPISA; validate return payload; feed SD-001 item.
3. **If not:** build a custom 1–2 step Spryker wizard using Configurable Product's in-app configurator plumbing.

### Dependencies
- **Spryker modules:** ConfigurableProduct (+ product configuration), Cart, Product.
- **External:** PIM-SPISA configurator capability + spec (Open Q #13–#15).
- **Prerequisites:** SD-001 dynamic item in place; ADR-004 gate closed.

### Rollout Strategy & Cost
- **Approach:** Future workstream; spike first. **Timeline:** > **TODO:** Future.
- **Estimated effort:** ScaniaSpisaDetail grows to **M** if it drives the configurator (see [section 5](../05-building-block-view.md)).
- **Risk mitigation:** custom-wizard fallback keeps the Future capability deliverable regardless of PIM-SPISA.

## Trade-offs & Considerations

### Advantages
- Reuses a documented Spryker feature with a matching service example.
- External-configurator path offloads configuration UX/logic to PIM-SPISA if capable.
- Fallback (custom wizard) uses the same Configurable Product plumbing.

### Disadvantages
- Feasibility depends entirely on PIM-SPISA's unknown capabilities.
- External redirect requires a secure, validated return contract.
- Adds complexity on top of the already-custom dynamic service item.

### Risks

| Risk | Impact | Mitigation |
|------|--------|------------|
| PIM-SPISA cannot act as configurator | External path unavailable | Custom Spryker wizard fallback (same feature plumbing). |
| Return payload tampering | Wrong configuration/price | Server-side validation; signature; re-read price from SECM. |
| Scope creep from POC into Future | Timeline | Keep wizard strictly Future; POC does basic selection only. |

## Alternatives Considered

### Alternative 1: Fully custom wizard (no Configurable Product)
- **Description:** Build a bespoke multi-step form with no Configurable Product feature.
- **Pros:** Full control.
- **Cons:** Re-implements configuration lifecycle, cart-edit, and persistence that Configurable Product already provides.
- **Why not chosen:** wasteful vs reusing the documented feature; only warranted if Configurable Product proves unsuitable.

### Alternative 2: No configuration (basic selection only, permanently)
- **Description:** Never build a wizard.
- **Pros:** Simplest.
- **Cons:** Fails the Future vision of guided service configuration.
- **Why not chosen:** acceptable for POC, not for the target state.

## Open Questions

- Does PIM-SPISA return the wizard structure/steps, or does Spryker define the flow? (Open Q #13)
- Can PIM-SPISA act as an external configurator endpoint? (Open Q #14)
- Full PIM-SPISA API spec/docs? (Open Q #15)

## Related Documentation

- **ADRs:** informs future service-config decisions; no accepted ADR yet (Future).
- **Solution Designs:** [SD-001 — Dynamic service and price in cart](sd-001-dynamic-service-and-price-in-cart.md)
- **External References:** [Configurable Product feature overview](https://docs.spryker.com/docs/pbc/all/product-information-management/latest/base-shop/feature-overviews/configurable-product-feature-overview/configurable-product-feature-overview) · [How to create a product configurator](https://docs.spryker.com/docs/pbc/all/product-information-management/latest/base-shop/tutorials-and-howtos/howto-create-a-product-configurator)

---

*Corresponds to [arc42 Section 4](https://docs.arc42.org/section-4/)*
