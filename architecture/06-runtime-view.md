# Runtime View

Behavior and interactions of building blocks as runtime scenarios for NORMA. Flow shapes are grounded in Spryker's documented Data Import / Publish & Sync, OMS state-machine, and ACP payment patterns (see [research](../.claude/.cache/architecture-prep/2026-07-23-3-tad-architectures/research-docs.md) references cited inline).

## Key Scenarios

### 1. Delta product/price/stock import (Plentymarkets → Talend → Glue Backend API → P&S)

The core recurring flow: every 10 minutes Talend pushes ~50 changed products into Spryker, which persists them and publishes to the storefront via Publish & Sync.

**See diagram:** [Delta Import Flow](diagrams/sequence/sd-delta-import.mmd)

**Key Steps:**
1. Scheduler / Talend triggers a delta feed (~50 products, every 10 min).
2. Talend calls the Glue **Backend API** ([docs](https://docs.spryker.com/docs/integrations/spryker-glue-api/backend-api/integrate-backend-api/integrate-backend-api)) with the delta payload (products, prices, stock, categories).
3. Data Import batch-applies rows to MariaDB (batch/PDO, pre-gathered lookups — [optimization guidelines](https://docs.spryker.com/docs/dg/dev/data-import/latest/data-import-optimization-guidelines)).
4. Entity changes emit events to RabbitMQ; **Publish** listeners stage data to `*_storage`/`*_search` tables.
5. **Sync** writes to Redis (key-value) and Elasticsearch across multiple publisher queues.
6. Storefront serves updated catalog. A full/initial import runs the same path in larger batches ([ADR-004](09-architecture-decisions/adr-004-delta-only-import-strategy.md)).

### 2. Checkout with Payone ACP payment + order splitting

Customer checkout, payment via the Payone ACP app (iframe/redirect + events bus), and shipment split by warehouse.

**See diagram:** [Checkout & Payone ACP Payment](diagrams/sequence/sd-checkout-payone-acp.mmd)

**Key Steps:**
1. Customer submits checkout on Yves; address verified via Melissa (REST).
2. Cart is split into shipments by warehouse allocation.
3. Payment initialised via the Payone ACP app; customer completes payment through iframe/redirect ([Payone ACP docs](https://docs.spryker.com/docs/pbc/all/payment-service-provider/latest/base-shop/third-party-integrations/payone/app-composition-platform-integration/payone-acp-app)).
4. ACP emits payment events to the ACP events bus (4,000–6,000 msg/day); Spryker consumes and updates payment state.
5. OMS foreign/ACP payment state machine advances the order (Preauthorization/Capture) ([OMS + ACP docs](https://docs.spryker.com/docs/dg/dev/acp/integrate-acp-payment-apps-with-spryker-oms-configuration)).
6. Order confirmed; order-confirmation email fired as an OMS command; order queued for export to Plentymarkets.

### 3. Returns with carrier label (DHL/DPD/GLS) via OMS + custom return portal

Customer initiates a return in the Yves return portal; a carrier label is generated as an OMS side-effect.

**See diagram:** [Returns & Carrier Label](diagrams/sequence/sd-returns-carrier-label.mmd)

**Key Steps:**
1. Customer opens the custom **return portal** (Yves) and selects returnable items (Shipped/Delivered, within return policy).
2. Return created; OMS moves items toward `Waiting for return` ([Return Management docs](https://docs.spryker.com/docs/pbc/all/return-management/latest/base-shop/return-management-feature-overview)).
3. A **custom OMS command** calls the relevant carrier (DHL/DPD/GLS) REST API to generate a return label/voucher (not OOTB).
4. Label returned to the customer; return progresses `Returned` → `Refunded`.
5. Return/refund state exported to Plentymarkets via Talend.

### 4. Order export (Spryker → Talend → Plentymarkets)

**See diagram:** [Order Export](diagrams/sequence/sd-order-export.mmd)

**Key Steps:**
1. On order placement / state change, orders + payments are staged for export.
2. Scheduler triggers a delta export every 10 min via Glue Backend API.
3. Talend pulls/receives the delta and forwards to Plentymarkets ERP.
4. ERP returns order-status updates on the inbound catalog/order channel.

---

*Corresponds to [arc42 Section 6](https://docs.arc42.org/section-6/)*
