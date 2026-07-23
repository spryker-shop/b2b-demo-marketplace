# Runtime View

Key runtime scenarios for the Daimler Truck B2B Parts platform.

## Scenario 1 — Customer authentication via DTAG IAM (OAuth SSO)

Federated customer login through Daimler's IAM (Empower ID) rather than local credentials.

**See diagram:** [OAuth SSO](diagrams/sequence/oauth-sso-dtag-iam.mmd)

**Key Steps:**
1. Customer requests a protected storefront resource; Yves detects no session.
2. Yves redirects the browser to DTAG IAM (OAuth authorization endpoint).
3. Customer authenticates at DTAG IAM; IAM redirects back with an authorization code.
4. Spryker exchanges the code for tokens at the IAM token endpoint.
5. A **custom `OauthCustomerAuthenticationStrategyPlugin`** resolves the customer and establishes company/dealer-pool context (the OOTB `AcceptOnly` and `CreateCustomer` strategies are insufficient for B2B — see [research](https://docs.spryker.com/docs/pbc/all/oauth/latest/storefront-account-bootstrapping) and [ADR-001](09-architecture-decisions/adr-001-merchant-relationship-for-dealer-customer-model.md)).
6. Session created; customer proceeds, seeing only their active dealer's catalog/prices.

> **TODO:** Confirm whether federated customers are pre-provisioned (from CRISP) or bootstrapped on first login; determines the exact strategy plugin behaviour. Owner: identity/integration team.

## Scenario 2 — Large XML parts/price import (S3 → Middleware split → CTE import → Publish & Sync)

The highest-risk runtime path: a FULL per-store import of ~1.5M `<SET>#productdata` tags within a ~30-minute budget.

**See diagram:** [Large XML Import](diagrams/sequence/large-xml-import.mmd)

**Key Steps:**
1. IPS delivers the full XML file to an **S3 bucket** (SFTP → S3); Spryker is **not** used as a file proxy ([ADR-003](09-architecture-decisions/adr-003-direct-s3-xml-import-not-proxied.md)).
2. **ETL Middleware** splits the large XML into manageable chunks per entity/store.
3. Spryker **DataImport** ingests chunks using **CTE-based / PDO bulk** writes (avoiding per-row ORM queries — [data-import optimization](https://docs.spryker.com/docs/dg/dev/data-import/latest/data-import-optimization-guidelines)).
4. Import execution is tracked (type, `created_at`, `finished_at`, success) for HQ monitoring.
5. Persistence changes emit events; **Publish & Sync** propagates via **multiple RabbitMQ publisher queues** with tuned chunk sizes to Redis ([P&S optimization](https://docs.spryker.com/docs/dg/dev/guidelines/performance-guidelines/architecture-performance-guidelines)).
6. Only product-related data needed on the Storefront is exported to Redis; **ES is not populated for product search** (delegated to IParts).

> **TODO:** The "split on middleware / CTE-based import" technique is a project pattern, not verbatim in Spryker guideline docs (documented equivalents: batch/PDO over ORM, pre-gather steps, multi-queue P&S, chunk tuning). Validate against the target release.

## Scenario 3 — Customer→dealer registration/approval, then order

Customer joins a dealer's pool via Merchant Relationship, dealer approves, then customer orders at dealer-scoped prices.

**See diagram:** [Customer↔Dealer Registration & Order](diagrams/sequence/customer-dealer-registration-order.mmd)

**Key Steps:**
1. Customer selects a dealer (optionally via **Dealer Locator** linkout) and sends a registration/join request to that dealer.
2. Request appears on the dealer's "new customers" Merchant Portal overview.
3. Dealer accepts/rejects; on accept, assigns a merchant-specific customer ID and configures prices/delivery per the approved customer — realized on **Merchant B2B Contracts / Contract Requests** ([Merchant Relationship](https://docs.spryker.com/docs/pbc/all/merchant-management/latest/base-shop/merchant-b2b-contracts-and-contract-requests-feature-overview)).
4. **Merchant Product Restrictions** ([docs](https://docs.spryker.com/docs/pbc/all/merchant-management/latest/base-shop/merchant-product-restrictions-feature-overview/merchant-product-restrictions-feature-overview)) and **Merchant Custom Prices** ([docs](https://docs.spryker.com/docs/pbc/all/price-management/latest/base-shop/merchant-custom-prices-feature-overview)) scope what the customer sees.
5. Customer selects the active dealer at order time; only that dealer's products/prices are shown; discount groups apply ([SD-001](04-solution-designs/sd-001-dealer-prices-discount-groups.md)).
6. Order is placed with **Invoice** payment; the dealer (merchant) owns fulfilment & shipping; dealer↔customer comments are available on the order.

## Confirmed canonical Spryker flow shapes

- **Publish & Sync** follows the standard event → RabbitMQ → listener/publisher → Redis flow ([multi-queue publish](https://docs.spryker.com/docs/dg/dev/integrate-and-configure/integrate-multi-queue-publish-structure)).
- **OMS** is XML-driven; post-order side-effects (e.g. Invoice handling, notifications) hang off transitions/commands ([state machine fundamentals](https://docs.spryker.com/docs/pbc/all/order-management-system/latest/base-shop/state-machine-cookbook/state-machine-cookbook-state-machine-fundamentals)).

---

*Corresponds to [arc42 Section 6](https://docs.arc42.org/section-6/)*
