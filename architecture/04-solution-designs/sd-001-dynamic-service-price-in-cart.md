# SD-001: Modelling Dynamic Services & Prices in the Cart

## Metadata

| Field | Value |
|-------|-------|
| **Status** | Draft (exploration) |
| **Date** | 2026-07-23 |
| **Author(s)** | > **TODO:** solution architect |
| **Stakeholders** | Scania, Spryker PS, SECM team |

## Problem Statement

Services sold on the platform are **not** products in the Spryker catalog. They are fetched at runtime
from **SECM** per selected vehicle, each with a **price returned by SECM**. Spryker's cart, checkout,
vouchers and order history all assume catalog products with prices in the price tables. We need a way to
put a "service for vehicle X at price P" into the cart and carry it through checkout → order, for the
**POC**, without over-building for production.

This is called out in the TAD as an **architectural decision requiring discovery** ("Dynamic
service/price in cart", status: *Discovery needed*).

## Goals & Requirements

### Functional Requirements
- Add a service (identified by SECM, tied to a specific vehicle/asset) to the cart with its SECM price.
- Support **multiple assets and multiple services** in one cart; allow regular products to coexist.
- Carry the service + price through OOTB **checkout (invoice)**, **vouchers/discounts**, and **order
  history**.
- Persist the cart between sessions.

### Non-Functional Requirements
- **POC-minimal**: least custom code that still proves the flow; avoid deep OMS/catalog changes.
- Isolatable: pricing source (SECM) stays behind the `SecmServiceCatalogClient` boundary.

### Constraints
- Price is authoritative in SECM; Spryker holds no service price table.
- No caching for POC; price is read live at browse/add time.
- API protocol / SECM spec **TBD**.

## Proposed Solution

Evaluate the three options from the TAD and (for the POC) lean toward **Option A**, keeping B/C as
production candidates. Final choice pending a spike + the SECM spec.

### Options

**Option A — Virtual product on-the-fly (session/cart only).**
The service exists only as a cart item created at add-to-cart: a synthesised SKU + name + SECM price
held in the cart/session, never persisted as a catalog product. A custom cart item expander/price
plugin injects the price so OOTB cart/checkout/order treat it like any item.
- **Pros:** least footprint; no catalog/import; fits "not a Spryker product" reality; fastest for POC.
- **Cons:** no OOTB PDP/search for services (custom pages already planned); price handling is custom;
  vouchers/discounts on a synthetic item need verification.

**Option B — Extend SSP Service Management with checkout.**
Model services via Spryker **Self-Service Portal (SSP) Service Management**, where services are sold as
a service **product class** with service points/offers and optional **service date & time** at
checkout.
[docs](https://docs.spryker.com/docs/pbc/all/self-service-portal/latest/ssp-service-management-feature-overview)
- **Pros:** purpose-built for booking services incl. date/time (aligns with the Future date/time
  requirement); marketplace-aware (aligns with Future multi-merchant).
- **Cons:** heavier; assumes services map onto SSP's product-class/offer model, but our prices/catalog
  come **live from SECM**, not from Spryker product data; **SSP Service Management is incompatible with
  Order Amendment** (a Future concern). Likely too much for a POC.

**Option C — Pre-sync catalog, fetch prices dynamically.**
Pre-sync a service catalog into Spryker as real (abstract/concrete) products, but fetch the **price**
dynamically from SECM at add-to-cart / checkout.
- **Pros:** real PDP/search/OOTB behaviour for services; clean separation of catalog vs price.
- **Cons:** requires a sync/import pipeline (contradicts "no caching, live fetch" POC stance);
  compatibility filtering (per-vehicle) still needs SECM live; more moving parts than a POC needs.

### Architecture (Option A, POC)

**See diagram:** [Asset → Service → Cart](../diagrams/sequence/asset-to-service-to-cart.mmd)

### Key Components

| Component | Responsibility | Technology |
|-----------|----------------|------------|
| `SecmServiceCatalogClient` | Fetch services + price per vehicle | Spryker Client module |
| `DynamicServiceCart` cart plugins | Item expander + price plugin injecting SECM price; SKU synthesis; asset linkage | Spryker Cart/Calculation plugins |
| OOTB Cart / Checkout / Vouchers / Order History | Reused unchanged | Spryker core |

### Data Model

- **No new price tables** for POC (Option A). Service context (SECM service id, vehicle id, price,
  currency) is carried on the cart item (transfer fields / item-level attributes).
- > **TODO:** confirm which OOTB cart-item transfer fields carry the synthesised SKU, asset reference,
  and locked price; define a project transfer if needed.

## Implementation Plan

### Phases
1. **Spike (Option A):** add a synthetic service item to the cart with a SECM price; confirm it flows
   through invoice checkout and order history; test one voucher.
2. Wire `SecmServiceCatalogClient` + `DynamicServiceCart` plugins.
3. Validate multiple assets/services in one cart.

### Dependencies
- **Spryker modules:** Cart, Calculation, Checkout, SalesOrder, Discount (vouchers).
- **External:** SECM spec + endpoint (or mock).

### Rollout Strategy & Cost
- POC spike first; decision recorded in an ADR once the spike + SECM spec confirm feasibility.
- > **TODO:** effort estimate pending spike.

## Trade-offs & Considerations

### Advantages (Option A)
- Minimal, POC-appropriate, matches "services are not Spryker products".

### Disadvantages (Option A)
- Custom price/cart handling; no OOTB service PDP/search; vouchers on synthetic items unproven.

### Risks

| Risk | Impact | Mitigation |
|------|--------|------------|
| Vouchers/discounts misbehave on synthetic items | Medium | Test early in the spike; fall back to disabling discounts on service items for POC |
| Price drift between add-to-cart and checkout | Medium | Lock price at add-to-cart (POC); re-validation is an open client question |

## Alternatives Considered
- **Option B (SSP):** best long-term for booking + date/time + multi-merchant, but too heavy for POC and
  a poor fit while catalog/price live in SECM.
- **Option C (pre-synced catalog):** best OOTB behaviour, but needs a sync pipeline that contradicts the
  POC's live-fetch/no-cache stance.

## Open Questions
- Is quantity per service item always 1 (per asset), or changeable?
- Lock price at add-to-cart, or re-validate against SECM before checkout?
- Are services categorized in SECM (affects browsing/filtering)?
- Does the SECM price include tax/mode (net/gross), and per which store currency?

## Related Documentation
- **ADRs:** > **TODO:** create ADR once Option chosen (candidate: "adr-004-dynamic-service-cart-option-a").
- **Other Solution Designs:** [SD-002 — Configurable Product / external configurator](sd-002-configurable-product-external-configurator.md)
- **External:** SSP Service Management, Cart & Checkout docs (see §11 links).

---

*Corresponds to [arc42 Section 4](https://docs.arc42.org/section-4/)*
