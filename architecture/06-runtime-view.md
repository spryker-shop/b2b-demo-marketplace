# Runtime View

Key runtime scenarios for NORMA, each with prose steps and a sequence diagram. Step order is confirmed against the shared Spryker docs research (P&S, OMS, Return Management have canonical shapes).

## 1. Delta product / price / stock import (Talend → Glue Backend API → P&S)

**See diagram:** [Delta import](diagrams/sequence/delta-import.mmd)

**Key Steps:**
1. Every ~10 minutes, Plentymarkets emits ~50 changed products (delta only); Talend transforms them.
2. Talend calls the **Glue Backend API** (Data Exchange / Dynamic Entity endpoints or custom resources) with an authenticated delta batch.
3. The `DeltaImportProcessor` writes product/price/stock in **batch** using CTE bulk upserts and preloaded lookups, following the Data Import optimization guidelines (research §11) — no per-row queries, no per-row facade calls.
4. Entity events are triggered in **bulk**; the API returns an idempotent accepted-count keyed on the delta cursor.
5. **Publish & Synchronize** consumes event messages in chunks, writes storage/search tables in bulk, then emits sync messages that populate Redis and OpenSearch.
6. Category PLPs reflect the delta after the P&S cycle. **Full-text search is served separately by SiteSearch360** (fed from the product feed), so its freshness is independent of this cycle.

> The initial/full catalog load reuses this exact path — it is a sequence of delta batches, not a bulk one-shot import.

## 2. Payone payment via direct module + OMS capture

**See diagram:** [Payone payment](diagrams/sequence/payone-payment.mmd)

**Key Steps:**
1. Customer submits checkout selecting a Payone method. Per [ADR-001](09-architecture-decisions/adr-001-payone-direct-module-over-acp.md), the **direct Payone module** is used (not the sunset ACP app).
2. Spryker pre-authorizes/initializes the payment with Payone (REST) and receives a PaymentID + redirect URL.
3. Customer is redirected to Payone (iframe/redirect), enters details, and is redirected back with a result.
4. The order is placed into the OMS with an initial "payment pending" state.
5. Payone publishes capture/status events onto the **MessageBroker** bus; a consumer calls `triggerEvent` on the order's OMS process (e.g. "capture successful").
6. The OMS runs the corresponding **command** (mark paid) as a **fire-and-forget** transition; if a command throws, the item stays in its source state for safe retry (research §12). COINs are modelled as additional OMS transitions.

## 3. Customer return via returns portal + carrier label

**See diagram:** [Customer return](diagrams/sequence/customer-return.mmd)

**Key Steps:**
1. Customer opens the custom Yves **returns portal** and selects an order/items. Items are returnable only if in **Shipped/Delivered** state and within the **Return Policy** window (research §14).
2. **Return Management** creates the return and persists return + return-item records.
3. A **return OMS sub-process** starts; return items move through their own OMS states.
4. An `onEnter` transition fires a **custom `ReturnLabelCommand`** that calls the selected carrier (DHL/DPD/GLS) over REST.
5. The carrier returns a label (PDF/URL); its reference is stored on the return.
6. The portal shows the confirmation and a downloadable label.

> Carrier-label generation is a **custom OMS-command extension**, not an OOTB packaged capability (research §14). See [SD-002](04-solution-designs/sd-002-returns-portal-and-carrier-labels.md).

## 4. Order splitting by warehouse allocation at checkout

**See diagram:** [Order splitting](diagrams/sequence/order-splitting.mmd)

**Key Steps:**
1. Customer proceeds to checkout with a multi-item cart.
2. Checkout determines the **warehouse/fulfiller allocation** per item.
3. Items sourced from different warehouses/fulfillers are grouped into separate shipments using **Marketplace Offer + Marketplace Shipment** primitives (the drop-shipment mechanism — research §17).
4. The order is placed with one shipment per allocation; each shipment runs its own OMS branch, so a supplier/fulfiller can ship its items directly.

---

*Corresponds to [arc42 Section 6](https://docs.arc42.org/section-6/)*
