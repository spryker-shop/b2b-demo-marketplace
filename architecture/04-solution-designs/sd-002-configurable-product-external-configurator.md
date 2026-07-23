# SD-002: Configurable Product with PIM-SPISA as External Configurator (Future)

## Metadata

| Field | Value |
|-------|-------|
| **Status** | Draft (exploration — Future phase) |
| **Date** | 2026-07-23 |
| **Author(s)** | > **TODO:** solution architect |
| **Stakeholders** | Scania, Spryker PS, PIM-SPISA team |

## Problem Statement

The Future service **wizard** (1–2 steps) should let a customer configure a service before adding it to
the cart. The TAD asks whether Spryker's **Configurable Product** can use **PIM-SPISA as an external
configurator**. For the **POC**, service configuration is only "basic selection"; this SD explores the
Future wizard and is explicitly **discovery** — it ends with open questions, not a decision.

The TAD lists this as an architectural decision "requiring discovery" (status: *Discovery needed*,
depends on PIM-SPISA API capabilities & format).

## Goals & Requirements

### Functional Requirements
- Let a customer configure a service via a 1–2 step wizard from the service detail page.
- Carry the selected configuration into cart → checkout → order (viewable, not changeable, in BO).

### Non-Functional Requirements
- Reuse OOTB Configurable Product mechanics if PIM-SPISA can fulfil the external-configurator contract.
- Keep the configurator as an isolated concern (external app on its own host, if used).

### Constraints
- PIM-SPISA API capabilities/format are **unknown** (spec required).
- This is a **Future** capability; the POC only does basic selection.

## Proposed Solution (to be validated)

Spryker's **Configurable Product** supports a **third-party / external configurator**: the shop
interacts with it purely by passing **parameters** (customer + product context, `store_name`, return
URL) on redirect **out**, and receiving updated parameters back on redirect **in**. The external
configurator is a **standalone web app on its own host**, integrated via a configurator key + shared
secret/redirect — it is not part of Yves/Zed.
[overview](https://docs.spryker.com/docs/pbc/all/product-information-management/latest/base-shop/feature-overviews/configurable-product-feature-overview/configurable-product-feature-overview) ·
[build a configurator](https://docs.spryker.com/docs/pbc/all/product-information-management/latest/base-shop/tutorials-and-howtos/howto-create-a-product-configurator)

The candidate design: treat a configurable **service** as a Spryker configurable product whose
"Configure" button redirects to **PIM-SPISA** (or a thin adapter fronting it) acting as the external
configurator; the returned configuration parameters are carried on the cart item.

### Key Components

| Component | Responsibility | Technology |
|-----------|----------------|------------|
| Configurable Product (Spryker) | Product type + "Configure" redirect + carry config into cart/order | Spryker PIM — Product Configuration feature |
| PIM-SPISA (external configurator) | Present wizard steps; return configuration parameters | External web app (or adapter) — spec TBD |
| `SpisaServiceDetailClient` | Provide detail + (Future) configurator entry parameters | Spryker Client module |

### Data Model
- Regular product is created first, then converted to configurable by importing configuration
  parameters (`product_concrete_pre_configuration.csv`). Two parameter types: **configuration
  parameters** (shop↔configurator) and **display parameters**.
- > **TODO:** this assumes services exist as Spryker products, which conflicts with SD-001's live-fetch
  model. Reconcile: does the wizard require pre-synced service products (SD-001 Option C territory)?

## Implementation Plan

### Phases
1. **Discovery:** confirm PIM-SPISA can (a) receive redirect parameters incl. `store_name`/return URL
   and (b) return configuration parameters in the expected shape.
2. If yes: prototype one configurable service using the external-configurator contract.
3. If no: design a **custom wizard** in Yves instead.

### Dependencies
- **Spryker modules:** Product Configuration + Product Configuration Glue API, Configurable Product.
- **External:** PIM-SPISA configurator spec.

### Rollout Strategy & Cost
- Future phase; not scheduled for POC. > **TODO:** effort pending discovery.

## Trade-offs & Considerations

### Advantages
- Reuses OOTB Configurable Product; keeps configurator concerns out of Yves/Zed.

### Disadvantages
- Requires services to be modelled as Spryker products (tension with SD-001 live-fetch).
- Depends entirely on PIM-SPISA's (unknown) API capabilities.

### Risks

| Risk | Impact | Mitigation |
|------|--------|------------|
| PIM-SPISA cannot meet the external-configurator contract | High | Fall back to a custom in-shop wizard |
| Conflict with SD-001 live-fetch service model | Medium | Decide catalog strategy (live vs pre-synced) before wizard work |

## Alternatives Considered
- **Custom in-shop wizard (Yves):** full control, no dependency on PIM-SPISA contract; more custom code
  and no OOTB configurable-product carry-through.

## Open Questions
- Does PIM-SPISA **return wizard steps**, or does Spryker define them?
- Is the PIM-SPISA endpoint capable of acting as an external configurator (redirect parameter contract)?
- Full PIM-SPISA API spec?
- How does a configurable service reconcile with SD-001's non-catalog, live-priced service model?

## Related Documentation
- **Other Solution Designs:** [SD-001 — Dynamic service & price in cart](sd-001-dynamic-service-price-in-cart.md)
- **External:** Configurable Product docs (linked above).

---

*Corresponds to [arc42 Section 4](https://docs.arc42.org/section-4/)*
