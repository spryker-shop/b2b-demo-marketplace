# Building Block View

Static decomposition of the NORMA Spryker platform into containers and the custom domains built on top of the core.

## C4 Level 2: Container View

**See diagram:** [C2 Container View](diagrams/c4/c2-spryker-container.mmd)

### Main Containers

| Container | Responsibility |
|-----------|----------------|
| **Yves (Storefront)** | Customer-facing shop (single store, `de_DE`, EUR); hosts the custom returns portal and all storefront JS-widget integrations. |
| **Zed (Back Office / Backend)** | Business logic, OMS, catalog management, returns processing, ~20 BO users. |
| **Glue Backend API** | System-to-system surface for Talend-driven delta import and order/payment export (Data Exchange / Dynamic Entity + custom resources). |
| **Backend Gateway** | Zed RPC endpoint for Yves/Glue → Zed calls. |
| **MariaDB** | Single relational database (Phase 1). |
| **Redis** | Key-value storage and sessions; storefront read models. |
| **OpenSearch / Elasticsearch** | Search index for **category listing pages** (full-text search is SiteSearch360). |
| **RabbitMQ** | Event/sync message broker; also the MessageBroker bus for Payone events. |
| **Scheduler (Jenkins)** | Runs Publish & Sync workers, the import cron, OMS cron, and feed/label jobs. |

## C4 Level 3: Component View (custom domains)

**See diagram:** [C3 Component View](diagrams/c4/c3-component-diagram.mmd)

The custom project code is organized into three domains layered on core Spryker modules.

### Custom Domains & t-shirt Sizing

| Domain / Module | Responsibility | Size | Driver |
|-----------------|----------------|------|--------|
| **ERP Integration** (delta import + order/payment export) | Consume Talend delta batches via Glue Backend API; batch/CTE writers for product/price/stock; delta cursor + idempotency; export orders & payments | **XL** | Delta-only model, idempotency, full-import-on-delta, performance at 60–80k products; the central Phase-1 build. See [SD-001](04-solution-designs/sd-001-erp-integration-and-data-migration.md). |
| **Returns Portal + Carrier Labels** | Custom Yves returns portal on Return Management; return OMS sub-states; DHL/DPD/GLS label generation via OMS command | **L** | Custom storefront flow + carrier-label OMS commands (not OOTB). See [SD-002](04-solution-designs/sd-002-returns-portal-and-carrier-labels.md). |
| **Payment (direct Payone module)** | Payone authorize/capture/status; OMS transitions; COINs | **L** | Direct module (ACP sunset), MessageBroker event handling, 4–6k msg/day. See [ADR-001](09-architecture-decisions/adr-001-payone-direct-module-over-acp.md). |
| **Search delegation (SiteSearch360)** | Replace full-text search plugins with SiteSearch360; keep OOTB ES for PLPs | **M** | Custom QueryInterface + response mapping following the "integrate any search engine" pattern. See [ADR-002](09-architecture-decisions/adr-002-external-full-text-search-sitesearch360.md). |
| **Product Feed Generation** | API-based product export for Feed Dynamix | **M** | Feed builder over product read models; also feeds SiteSearch360 content. |
| **Order Splitting (warehouse allocation)** | Split checkout into shipments per warehouse/fulfiller using Marketplace Offer + Shipment | **M** | Marketplace primitives for drop-shipment; per-shipment OMS branches. |
| **DWH extract** | Provide BI data to Talend/DWH | **S–M** | Depends on unresolved mechanism (VPN vs Data Exchange API). See [ADR-004](09-architecture-decisions/adr-004-dwh-access-mechanism.md). |
| **CRM export (Emarsys)** | Push customer info for campaigns | **S** | Outbound REST integration. |
| **Address verification (Melissa)** | Verify addresses at checkout | **S** | Outbound REST call in checkout. |
| **Storefront JS widget wiring** | Integrate ~15 vendor JS widgets, consent-gated | **M** | Volume of integrations + performance budget + consent gating, not per-widget complexity. |

### Zed Application Layers (core)

- **Presentation** — Controllers, Forms, UI (returns portal admin, catalog).
- **Communication** — Facades, Plugins, Gateway (RPC), Glue Backend API resources.
- **Business** — OMS, delta import processing, returns, feed generation, order splitting.
- **Persistence** — Entities, Repositories, Entity Managers; batch/CTE writers for import.

---

*Corresponds to [arc42 Section 5](https://docs.arc42.org/section-5/)*
