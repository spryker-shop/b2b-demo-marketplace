# Runtime View

Key runtime scenarios for the Daimler Truck B2B Parts platform. Step order confirmed against [research-docs](../../.claude/.cache/architecture-prep/2026-07-23-3-tad-architectures-v2/research-docs.md) where a canonical Spryker shape exists (Publish & Sync, Merchant B2B Contracts, Federated Auth).

## Scenario 1: Large XML data import + Publish & Sync

The IPS full parts+price XML (~1.5M `<SET>` tags) is imported and propagated to storage — the throughput-critical path.

**See diagram:** [Large XML Import + P&S](diagrams/sequence/large-xml-import-publish-sync.mmd)

**Key Steps:**
1. IPS delivers XML to S3 via SFTP. Files are **not proxied through Spryker** (per [Data import optimization](https://docs.spryker.com/docs/dg/dev/data-import/latest/data-import-optimization-guidelines) best practice, research §11).
2. `XmlPartsImporter` records import start in `ImportExecutionTracker` and streams/reads the XML from S3 in chunks.
3. A pre-step loads lookups into memory (avoid a query per row).
4. Events are batched: `EventBehaviorConfig::disableEvent()` before the write loop.
5. Per chunk, a **CTE bulk INSERT/UPDATE** writes `spy_product_abstract` and `spy_price_product_store` (ORM avoided for bulk).
6. `triggerBulk(...)` then `enableEvent()` emits events once, in bulk; tracker records `finished_at` + success.
7. **Publish & Sync** workers consume event chunks, CTE-bulk-write `*_storage` tables, emit sync messages, then bulk-populate Redis. Product **search/filter is not indexed in Spryker ES** — it is delegated to the external catalog API.

> This is the canonical Spryker P&S shape (event → storage listener → sync → Redis), tuned with CTE + chunking + manual event batching for the 300k → 26M volume. See [SD-001](04-solution-designs/sd-001-large-scale-data-import-and-publish-sync.md).

## Scenario 2: Customer registration-request → dealer approval → order

A customer must be in a dealer's approved pool before ordering; built on Merchant B2B Contracts + Merchant Product Restrictions.

**See diagram:** [Registration → Approval → Order](diagrams/sequence/customer-registration-approval-order.mmd)

**Key Steps:**
1. Customer (company user) submits a **relation request** to a dealer from the storefront (business unit + owner + optional message) — the documented Merchant B2B Contract request flow (research §2). Status starts **Pending**.
2. Dealer reviews the request in the Merchant Portal "new customers" page; approves or rejects.
3. On approval, the merchant relation is created; the dealer assigns a **merchant-specific customer ID** and configures per-customer prices/delivery costs/methods.
4. **Merchant Product Restrictions** apply the dealer's per-customer product lists (allow/exclude), controlling what the customer can see/buy (research §3).
5. Customer selects an **active dealer**; the storefront resolves visible products + merchant-specific prices for (customer, dealer).
6. Customer adds to cart and checks out with **Invoice** payment; the order is scoped to the selected dealer/merchant.

## Scenario 3: DTAG IAM OAuth login

Storefront/BO login delegated to DTAG IAM via Federated Authentication (research §15).

**See diagram:** [DTAG IAM OAuth Login](diagrams/sequence/dtag-iam-oauth-login.mmd)

**Key Steps:**
1. User clicks "Login with DTAG IAM"; Yves/Back Office redirects to DTAG IAM (Authorization Code flow).
2. User authenticates at DTAG IAM; IdP redirects back with an auth code.
3. Spryker exchanges the code for an access token and fetches claims (`email` required).
4. Spryker matches the identity by `(provider, external_id)`; links an existing account or **just-in-time provisions** a new one.
5. The access token is discarded after claims fetch — the **Spryker session is independent of the IdP** (no federated logout yet).

> **Constraint (research §15):** Glue/API-level OAuth SSO is **not GA** ("coming later"). Headless clients need the CIAM-provider integration path or a project-level solution. **Claims → ACL role mapping is a future capability** and must be implemented at project level today (combine with Company Account roles / login-by-token).

## Scenario 4: Add-to-cart with merchant-specific price + availability lookup

Price comes from the merchant relation; availability is read live from TruckLog / MB LogBus DIMS.

**See diagram:** [Add-to-Cart Price + Availability](diagrams/sequence/add-to-cart-price-availability.mmd)

**Key Steps:**
1. Customer adds a part to the cart (active dealer already selected); cart recalculates.
2. Price is resolved via the merchant relation — dealer list price and the customer's discount group (calculated dynamically), not a static per-product price.
3. Availability is requested for the SKU + market: **TruckLog** where available for that market, otherwise **MB LogBus DIMS** fallback.
4. Availability is returned for display and is **not stored as Spryker stock** and is non-blocking.
5. Cached storage data (product/price) is read from Redis; the cart line is rendered with price + availability.

---

*Corresponds to [arc42 Section 6](https://docs.arc42.org/section-6/)*
