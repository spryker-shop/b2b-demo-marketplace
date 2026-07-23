# Cross-Cutting Concepts

Principles and patterns that span multiple building blocks of the NORMA platform.

## Authentication & Authorization

- **Customers (Yves):** native Spryker customer authentication in Phase 1. **Social login / SSO is Phase 2** and, when built, uses **Federated Authentication via OAuth2/OIDC** on the storefront, which is a supported capability (research §15). Historical customers migrate from Shopware with a password re-hash strategy (see [SD-001](04-solution-designs/sd-001-erp-integration-and-data-migration.md)).
- **Back Office users (~20):** Spryker **User Management + ACL** — Users → Groups → Roles → Resources (`/module/controller/action`) (research §18).
- **Glue Backend API (Talend):** system-to-system auth via API key or OAuth 2.0 with authorization scopes; no interactive login.

## Data Import & Publish/Synchronize

The delta import and read-model population is the platform's backbone. Two rules apply everywhere import touches:
- **Batch, never per-row:** preload lookups, use CTE bulk upserts, bulk-trigger events during import (`EventBehaviorConfig::disableEvent()` … `triggerBulk` … `enableEvent`), release memory between batches (research §11).
- **Storefront reads come from read models only:** Redis (KV) and OpenSearch, populated via **Publish & Synchronize** — never direct DB reads on the request path.

## Search Strategy (split)

Two search backends coexist: **SiteSearch360** serves full-text search (custom integration following the "integrate any search engine" pattern — no packaged connector), while **OOTB Elasticsearch** serves category listing pages. This split is a deliberate architectural boundary; see [ADR-002](09-architecture-decisions/adr-002-external-full-text-search-sitesearch360.md).

## OMS as the Integration Backbone

External side-effects hang off the **OMS state machine** as commands on transitions:
- **Payment:** Payone capture/status arrive as MessageBroker events that `triggerEvent` on the order process; commands run fire-and-forget with safe retry.
- **Returns:** the return OMS sub-process fires the carrier-label command.
- **Order export:** delta order/payment export is driven from order state changes.

This keeps external calls off the synchronous request path and gives retry semantics for free (research §12).

## Drop Shipment via Marketplace Primitives

"Drop Shipment" is **not a Spryker feature**. NORMA implements it with **Product Offer + Marketplace Shipment**: suppliers/fulfillers are merchants, items are offers, and checkout splits the order into per-fulfiller shipments so suppliers ship directly (research §17). Order splitting by warehouse allocation reuses the same primitives.

## Internationalisation & Multi-Store

Phase 1 is single store (`de_DE`, EUR). The design keeps store-specific data store-scoped so a later move to **Dynamic Multistore** (GA since 202410.0) is possible within one EU region; the region decision must precede the first additional country (see [Section 7](07-deployment-view.md)).

## Storefront Performance & Third-Party JS

With ~15 storefront JS integrations, performance is a cross-cutting concern:
- All non-essential widgets load **consent-gated** through Usercentrics.
- A **performance budget** applies to third-party scripts; impact and cross-service conflicts are measured before Go-Live (see [Section 11](11-risks-and-technical-debt.md)).
- Prefer async/deferred loading and tag-manager-managed injection over inline blocking scripts.

## Logging, Observability & Error Handling

- Application logging via Spryker's Log module; import and OMS command failures are logged with the delta cursor / order reference for traceability.
- Import is **idempotent** (delta cursor), so a failed batch is safely retried without duplicates.
- Payone and carrier-label commands fail safe: on error the OMS item stays in its source state for retry rather than advancing.

## Security & Compliance

- EU data residency (single EU region); GDPR-relevant flows are consent-gated (Usercentrics) and address handling uses Melissa.
- reCAPTCHA v3 protects public forms; Glue Backend API is scoped and authenticated for Talend only.

---

*Corresponds to [arc42 Section 8](https://docs.arc42.org/section-8/)*
