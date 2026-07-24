# SD-001: Dynamic service and price in cart

## Metadata

| Field | Value |
|-------|-------|
| **Status** | Draft |
| **Date** | 2026-07-23 |
| **Author(s)** | Spryker delivery team (Scania POC) |
| **Stakeholders** | Solution architect (Driver), Scania SECM owner, Client |

## Problem Statement

Services sold by the Scania shop are **not** in the Spryker catalog. For a selected vehicle, **SECM** returns the compatible services and a **price per service**. That priced service must flow through **cart → checkout → order** while also allowing multiple assets per cart, regular products alongside services, session persistence, and OOTB vouchers/discounts. Spryker has no OOTB pattern for a fully dynamic, API-sourced priced line item, so an architectural approach must be chosen and de-risked before the cart workstream can start. This is the flagship POC challenge and the gate behind **[ADR-004](../09-architecture-decisions/adr-004-dynamic-service-price-cart-modelling.md)**.

## Goals & Requirements

### Functional Requirements
- Add a SECM-priced service (bound to a specific vehicle) to the cart.
- Support multiple assets and multiple services in one cart; allow regular products to coexist.
- Persist the cart between sessions (survives logout/login).
- Apply OOTB vouchers/discounts and produce a correct order total.
- Lock the SECM price at add-to-cart (POC suggestion; re-validation is Open Q #18).

### Non-Functional Requirements
- Performance: standard Spryker performance acceptable; price read adds one SECM call at add-to-cart.
- Security: SECM credentials in config; price source is trusted only from SECM, never client-supplied.
- Extensibility: must not block the Future service wizard (SD-002), per-country pricing (Open Q #20), or quantity handling (Open Q #17).

### Constraints
- Services/prices are external (SECM); no catalog ownership for POC.
- Yves storefront; invoice-only checkout (ADR-003); no PSP.
- One global price for POC (DMS stores share the catalog/price).

## Proposed Solution

### Overview

Represent each service as a **dynamic (virtual) line item** created at add-to-cart (**Option A**), carrying the SECM price plus service and vehicle references. The price is **locked** into the item (and later the quote/order) at add-to-cart; the cart persists as a Spryker **Quote**. Regular products remain normal catalog items in the same cart. This keeps the service out of the catalog (no P&S burden) while reusing OOTB Cart/Quote/Checkout/Sales, including vouchers and discounts.

### Architecture

**See diagram:** [Dynamic Service Cart → Checkout](../diagrams/sequence/dynamic-service-cart-checkout.mmd) · [C3 Component View](../diagrams/c4/c3-component-diagram.mmd)

### Key Components

| Component | Responsibility | Technology |
|-----------|----------------|------------|
| ScaniaDynamicService (Zed) | Build the dynamic item; lock SECM price; expand cart/quote/order | Spryker Zed module, Cart plugins |
| ScaniaSecmService Client | Read service price/compatibility per vehicle | Spryker Client (HTTP) |
| OOTB Cart / Quote / Checkout / Sales | Hold items, persist quote, place invoice order | Spryker core |

### Integration Points

- **Internal Modules:** Cart expander/item plugins to inject the dynamic item and its price; Quote persistence for session survival; Checkout & Sales for order placement.
- **External Systems:** SECM (price read) via ScaniaSecmService Client at add-to-cart (and again at checkout only if re-validation is chosen).
- **Data Flows:** synchronous SECM read at add-to-cart; no queue involvement for pricing.

### Data Model

**See ERD:** [Dynamic Service Item ERD](../diagrams/erd/dynamic-service-item-erd.puml)

Reuse Spryker's Sales/Quote tables and attach a small project extension for the service-specific fields.

**Database schema (Propel):**

| Table | Key columns | Notes |
|-------|-------------|-------|
| `spy_quote` (core) | `id_quote` (PK), `fk_customer`, `fk_store`, `quote_data` (JSON) | Cart persistence; for POC the dynamic service items live inside `quote_data`, materialized on order placement. |
| `spy_sales_order_item` (core) | `id_sales_order_item` (PK), `fk_sales_order` (FK), `sku`, `name`, `gross_price`, `quantity` | Standard order item; `sku` is a synthetic SKU for the service; `gross_price` holds the locked SECM price (minor units). |
| `scania_service_order_item` (project) | `id_scania_service_order_item` (PK), `fk_sales_order_item` (FK → `spy_sales_order_item`), `service_ref`, `vehicle_ref`, `secm_price_snapshot`, `price_source`, `activation_date` | 1:1 extension present **only** for dynamic service items. `price_source = 'SECM'`; `secm_price_snapshot` is the locked price; `activation_date` is Future (SSP date/time). |

**Transfer Objects (`*.transfer.xml`):**
- `ScaniaServiceItemTransfer` — `serviceRef`, `vehicleRef`, `unitPrice`, `priceSource`, `activationDate` (nullable), carried on the `ItemTransfer` (via extension) through cart → order.
- Reuse OOTB `QuoteTransfer`, `ItemTransfer`, `OrderTransfer`.

**Ownership / read-write rules:**
- `scania_service_order_item` is owned **read-write** by ScaniaDynamicService (project).
- `secm_price_snapshot` / `gross_price` are **read-only after the add-to-cart lock** (unless the re-validation policy is chosen — Open Q #18).
- Regular product items have **no** `scania_service_order_item` row.

> If SECM's real response shape makes Option A undraftable as above, run a **named spike** (below) before committing the schema.

## Implementation Plan

### Phases
1. **Spike:** validate Option A against real/mock SECM responses; confirm price-lock policy (closes ADR-004 gate G1).
2. **Build:** ScaniaDynamicService cart plugins + `scania_service_order_item` schema + transfers; wire SECM price read.
3. **Checkout/Order:** materialize dynamic items into `spy_sales_order_item` (+ extension) on placement; verify vouchers/discounts.

### Dependencies
- **Spryker modules:** Cart, Quote, Checkout, Sales, Money/Price, Kernel.
- **External:** SECM spec/mock (Open Q #10, #12).
- **Prerequisites:** ADR-004 gate closed; price re-validation policy decided (Open Q #18); quantity policy decided (Open Q #17).

### Rollout Strategy & Cost
- **Approach:** phased (spike → build). **Timeline:** > **TODO:** set at kickoff.
- **Estimated effort:** ScaniaDynamicService sized **L** (see [section 5](../05-building-block-view.md)).
- **Risk mitigation:** build against SECM mock; keep the item model isolated so an option change is contained.

## Trade-offs & Considerations

### Advantages
- Keeps services out of the catalog (no Publish & Sync burden, always current).
- Reuses OOTB Cart/Quote/Checkout/Sales incl. vouchers/discounts.
- Deterministic order total via price lock.

### Disadvantages
- Custom cart/quote/order handling for the virtual item.
- Synthetic SKUs need a clear generation scheme.
- Per-country pricing (Future) and quantity handling need explicit design.

### Risks

| Risk | Impact | Mitigation |
|------|--------|------------|
| SECM response shape unknown | Schema/transfer rework | Spike against mock; isolate mapping in Client. |
| Price drift between add-to-cart and checkout | Wrong total / disputes | Lock at add-to-cart (POC); or add checkout re-read if re-validation chosen. |
| Option A doesn't fit real SECM semantics | Switch options late | ADR-004 stays Proposed until spike; contain the model. |

## Alternatives Considered

### Alternative 1: Option B — Extend SSP service request with checkout
- **Description:** Represent the service via **SSP Service Management** primitives (service product/offer, service request) and drive it to checkout. [Research §6](https://docs.spryker.com/docs/pbc/all/self-service-portal/latest/ssp-service-management-feature-overview)
- **Pros:** Uses a purpose-built service feature; natural home for service date/time (the Future capability).
- **Cons:** SSP models services as products with offers/service points — heavier than needed for a POC where price is fully dynamic from SECM; **SSP Service Management is not compatible with Order Amendment** (research §6), and couples the POC to SSP semantics prematurely.
- **Why not chosen (for POC):** more setup and coupling than the feasibility question requires; revisit if service date/time and service-point booking become central.

### Alternative 2: Option C — Pre-sync catalog + dynamic price
- **Description:** Pre-import services as catalog products; override price dynamically from SECM at cart time.
- **Pros:** Services become first-class catalog entities (search, PDP, reuse).
- **Cons:** Requires a sync of SECM's per-vehicle service space into the catalog (large/volatile), plus a dynamic price override — two moving parts and a stale-catalog risk, for a POC that does not need catalog features.
- **Why not chosen (for POC):** highest build/sync cost; contradicts "services not in catalog."

## Open Questions

- Lock price at add-to-cart, or re-validate at checkout? (Open Q #18) — decides whether a checkout-time SECM re-read is needed.
- One quantity per service per asset, or variable quantity? (Open Q #17) — affects the item model.
- Will services/prices differ per country in future? (Open Q #20) — would make price store-aware.
- **Named spike:** *"Confirm Option A against real SECM responses"* — Owner: solution architect; Timebox: 3–5 days; Question: does SECM return a stable per-vehicle service+price shape that maps cleanly to `ScaniaServiceItemTransfer`, and can price be locked deterministically?

## Related Documentation

- **ADRs:** [ADR-004 — Dynamic service & price in cart](../09-architecture-decisions/adr-004-dynamic-service-price-cart-modelling.md) (gate G1)
- **Solution Designs:** [SD-002 — External service configurator](sd-002-external-service-configurator-with-configurable-product.md)
- **Runtime:** [Scenario 4 — Add dynamic service to cart → checkout](../06-runtime-view.md)

---

*Corresponds to [arc42 Section 4](https://docs.arc42.org/section-4/)*
