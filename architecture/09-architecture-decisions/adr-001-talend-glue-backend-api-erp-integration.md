# ADR-001: Talend + Glue Backend API for ERP integration

## Status

**Accepted** (2024-07-25)

## Context

Plentymarkets ERP is the master of products, categories, stock, prices and order updates, and consumes NORMA's order info + payments. NORMA already standardises on **Talend** as its ETL / service bus for backend-to-backend integration. Spryker needs a supported, secured surface for this system-to-system traffic (catalog import inbound, order export outbound) at ~60k products with a 10-minute delta cadence.

## Decision

We will integrate the ERP **exclusively through Talend**, and expose/consume data on the Spryker side via the **Glue Backend API** ([docs](https://docs.spryker.com/docs/integrations/spryker-glue-api/backend-api/integrate-backend-api/integrate-backend-api)), secured with token authentication and authorization **scopes** ([docs](https://docs.spryker.com/docs/integrations/spryker-glue-api/backend-api/integrate-backend-api/integrate-the-authorization-scopes)). No direct Spryker↔Plentymarkets connection is built. Inbound catalog data feeds Spryker **Data Import** and then **Publish & Sync**; outbound orders/payments are exported the same way.

## Consequences

### Positive
- Uses the documented system-to-system API surface; Talend remains the single mandated integration bus.
- Token + scope security; decoupled from ERP internals.
- Same surface can host the **Data Exchange API** for DWH if chosen ([SD-002](../04-solution-designs/sd-002-dwh-integration-external-db-access.md)).

### Negative
- All ERP traffic depends on Talend availability/throughput (single broker).
- Requires the `spryker/glue-backend-api-application` and custom backend resources/routes for the NORMA payloads.

---

*Corresponds to [arc42 Section 9](https://docs.arc42.org/section-9/)*
