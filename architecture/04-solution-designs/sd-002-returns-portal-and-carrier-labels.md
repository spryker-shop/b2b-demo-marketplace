# SD-002: Returns Portal & Carrier Labels

## Metadata

| Field | Value |
|-------|-------|
| **Status** | Draft |
| **Date** | 2024-07-25 |
| **Author(s)** | NORMA architecture (from TAD v47) |
| **Stakeholders** | Norma24, Spryker |

## Problem Statement

NORMA needs a **custom customer returns portal** in the Yves storefront and **return labels generated from DHL, DPD, and GLS**. Spryker's Return Management provides return creation and OMS-based return states, but **carrier-label generation is not an out-of-the-box capability** — it must be built as a custom OMS command (research §14).

## Goals & Requirements

### Functional Requirements
- Customers create returns (whole order or individual items) from a Yves portal.
- Items are returnable only if in **Shipped/Delivered** and within the **Return Policy** window.
- Generate a carrier return label (DHL/DPD/GLS) and make it downloadable.
- Track return + label state through OMS.

### Non-Functional Requirements
- **Reliability:** label generation must fail safe (retry, never lose a return).
- **Security:** carrier API credentials stored securely; label artifacts access-controlled.

### Constraints
- Built on core **Return Management**; carrier labels are a custom OMS-command extension.
- Return Policy window is code-config only (no UI).

## Proposed Solution

### Overview

A custom Yves controller drives the returns portal on top of core Return Management. Return creation starts the **return OMS sub-process**; an `onEnter` transition fires a custom `ReturnLabelCommand` that calls the selected carrier and stores the label reference. The portal then offers the label for download.

### Architecture

- **See diagram:** [Customer return sequence](../diagrams/sequence/customer-return.mmd)
- **See diagram:** [Returns ERD](../diagrams/erd/returns-erd.puml)

### Key Components

| Component | Responsibility | Technology |
|-----------|----------------|------------|
| Yves Returns Controller | Portal UI + return creation | Yves (SprykerShop) |
| Return Management | Return + return-item persistence, return states | Core module |
| Return OMS sub-process | Drive return item states | OMS / StateMachine |
| ReturnLabelCommand | Call DHL/DPD/GLS, store label | OMS command (Zed Business) |
| Carrier adapters | Per-carrier REST client behind a common interface | Zed Business |

### Integration Points

- **Internal Modules:** SalesReturn / Return Management, Oms, Sales, Yves storefront.
- **External Systems:** DHL, DPD, GLS return-label REST APIs.
- **Data Flows:** OMS transition → carrier REST call → label stored (S3 blob or URL reference).

### Data Model

Extends core Return Management with one custom table for carrier labels.

- **Core (reused):** `spy_sales_return` (return + reference + store + customer), `spy_sales_return_item` (links to `spy_sales_order_item`, reason), return OMS states via `spy_oms_order_item_state`.
- **`norma_return_label`** (custom): `id_return_label` (PK), `fk_sales_return` (FK → `spy_sales_return`), `carrier` (DHL|DPD|GLS), `status` (requested|generated|failed), `tracking_number`, `label_url`, `label_blob_ref` (S3 key if the PDF is stored), `error_message`, timestamps. **Owner: Returns domain, read-write** (written by `ReturnLabelCommand`, read by the portal). One return can have multiple labels (multi-carrier / retry).
- **Transfers:** reuse core `ReturnTransfer` / `ReturnItemTransfer`; add a `NormaReturnLabelTransfer` for label state and carrier response.

## Implementation Plan

### Phases
1. **Returns portal (Yves)** — controller/views on Return Management; enforce Shipped/Delivered + policy window.
2. **Return OMS sub-process** — states + transitions for return + label lifecycle.
3. **Carrier label command** — common command interface + DHL/DPD/GLS adapters; label storage.
4. **Download + notifications** — expose label to customer.

### Dependencies
- **Spryker modules:** SalesReturn / Return Management, Oms, Sales, Customer, Yves ShopApplication.
- **External:** DHL/DPD/GLS API credentials + label formats (integration-readiness checklist, [Section 7](../07-deployment-view.md)).
- **Prerequisites:** Return Policy window configured; carrier accounts provisioned.

### Rollout Strategy & Cost
- **Approach:** phased; portal + returns first, carrier labels per carrier.
- **Estimated Effort:** L; estimate in team-days at planning.
- **Risk Mitigation:** fail-safe OMS command (item stays in source state on error).

## Trade-offs & Considerations

### Advantages
- Reuses core Return Management + OMS; only labels are custom.
- Fail-safe retry via OMS command semantics.

### Disadvantages
- Three carrier integrations to build and maintain.
- Return Policy window has no BO UI (code-config).

### Risks

| Risk | Impact | Mitigation |
|------|--------|------------|
| Carrier API downtime | Medium | OMS retry; item never advances past failed label |
| Label storage / GDPR | Low | Access-controlled S3 references; no PII in label metadata beyond necessity |

## Alternatives Considered

### Alternative 1: Third-party returns SaaS portal
- **Cons:** duplicates order/return data outside Spryker; extra integration.
- **Why not chosen:** TAD specifies a custom Yves portal on Spryker Return Management.

### Alternative 2: Manual label generation in Back Office only
- **Cons:** no customer self-service.
- **Why not chosen:** requirement is a customer-facing portal.

## Open Questions
- Preferred label storage (inline URL vs stored PDF in S3)?
- Per-carrier retry/back-off policy?

## Related Documentation
- **Sections:** [03](../03-system-scope-and-context.md), [06](../06-runtime-view.md), [08](../08-crosscutting-concepts.md)
- **Research:** shared Spryker docs research §14 (Return Management) — [Return Management docs](https://docs.spryker.com/docs/pbc/all/return-management/latest/base-shop/return-management-feature-overview)

---

*Corresponds to [arc42 Section 4](https://docs.arc42.org/section-4/)*
