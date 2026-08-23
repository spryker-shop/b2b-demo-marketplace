# Code references — MCP Commerce Server

Implementation crosswalk for [mcp-commerce-server.prd.md](mcp-commerce-server.prd.md). Everything here was verified against this working tree on branch `master-demo` (2026-08-23). The PRD body is deliberately code-free; this file carries the FQCNs, paths, config keys, and schema facts the planner needs.

---

## 1. Host application — Storefront API

The MCP server lives in the **Storefront API** Symfony application (`GlueStorefront`), not Glue Backend.

| Concern | Path |
|---|---|
| Bundle registration | `/Users/vitaliiivanov/Desktop/development/spryker/b2b-demo-marketplace/config/GlueStorefront/bundles.php` |
| Autowired service config | `/Users/vitaliiivanov/Desktop/development/spryker/b2b-demo-marketplace/config/GlueStorefront/ApplicationServices.php` |
| Route loading | `/Users/vitaliiivanov/Desktop/development/spryker/b2b-demo-marketplace/config/GlueStorefront/routes/api_platform.php` |
| API Platform config | `/Users/vitaliiivanov/Desktop/development/spryker/b2b-demo-marketplace/config/GlueStorefront/packages/api_platform.php` |
| Spryker AP config | `/Users/vitaliiivanov/Desktop/development/spryker/b2b-demo-marketplace/config/GlueStorefront/packages/spryker_api_platform.php` |
| Security / firewall | `/Users/vitaliiivanov/Desktop/development/spryker/b2b-demo-marketplace/config/GlueStorefront/packages/security.php` |
| Framework config | `/Users/vitaliiivanov/Desktop/development/spryker/b2b-demo-marketplace/config/GlueStorefront/packages/framework.php` |

**Registered bundles** (`config/GlueStorefront/bundles.php`):
`Symfony\Bundle\FrameworkBundle\FrameworkBundle`, `Symfony\Bundle\SecurityBundle\SecurityBundle`, `Symfony\Bundle\TwigBundle\TwigBundle`, `Spryker\ApiPlatform\SprykerApiPlatformBundle`, `ApiPlatform\Symfony\Bundle\ApiPlatformBundle`.

> `TwigBundle` is already registered — usable for rendering the `/authorize` login + consent screen without adding a dependency.

**Service wiring is fully autowired and public** (`ApplicationServices.php`): `->defaults()->autowire()->public()->autoconfigure()`. New controllers/services in the project namespace are picked up without explicit service definitions.

**Route loading is currently API-Platform-only.** `config/GlueStorefront/routes/api_platform.php` imports the `api_platform` loader under prefix `/`, guarded by `is_dir(dirname(__DIR__, 2) . '/src/Generated/Api/Storefront')`. Add a **sibling** route file in `config/GlueStorefront/routes/` for the MCP + OAuth endpoints — do not modify the API Platform one.

**Firewall shape** (`security.php`): a single stateless, lazy firewall `main` with provider `api_oauth_provider` (`Spryker\ApiPlatform\Security\ApiUserProvider`), entry point `Spryker\ApiPlatform\Security\GlueAuthenticationEntryPoint`, and blanket `accessControl()->path('^/')->roles(['PUBLIC_ACCESS'])`. Authorization is per-resource via security expressions, so the new public endpoints (`/.well-known/*`, `/register`, `/authorize`, `/token`) need no firewall exception, while `/mcp` must enforce its own Bearer check.

---

## 2. Runtime versions (the constraint that rules out API Platform's MCP server)

From `composer.json` / `composer.lock`:

- `symfony/framework-bundle`: `^6.4 || ^7.0 || ^8.0`
- `symfony/http-foundation`: `^6.4.14 || ^7.0 || ^8.0`
- `symfony/routing`: `^6.4 || ^7.0 || ^8.0`
- `spryker/api-platform`: `^1.22.0`
- `api-platform/*`: `^4.3.14` (`metadata`, `state`, `symfony`, `json-api`, `hal`, `doctrine-orm`)
- **No** MCP package present — `grep -niE 'mcp|model-context' composer.json` returns nothing.

Effective Symfony baseline is **6.4**, below what API Platform's MCP server component requires → project-level Streamable HTTP transport is mandatory.

---

## 3. Existing security layer to reuse

`vendor/spryker/api-platform/src/Spryker/ApiPlatform/Security/`:

| Class | Role |
|---|---|
| `Spryker\ApiPlatform\Security\OauthAuthenticator` | Extends `AbstractAuthenticator`. Reads `Authorization: Bearer …`, validates locally via `Spryker\Client\Oauth\OauthClientInterface` (no Zed round trip), maps claims → `ApiUser`. Uses `AuditLoggerTrait`, channel `security`. Constants: `AUTHORIZATION_HEADER`, `BEARER_PREFIX`, `TOKEN_TYPE_BEARER`, `CONTENT_TYPE_JSON_API` (`application/vnd.api+json`), `OAUTH_USER_DATA_KEY = 'uuid'`. |
| `Spryker\ApiPlatform\Security\ApiUser` | Authenticated principal. |
| `Spryker\ApiPlatform\Security\ApiUserProvider` | User provider bound to `api_oauth_provider`. |
| `Spryker\ApiPlatform\Security\GlueAuthenticationEntryPoint` | 401 entry point — the hook for emitting `WWW-Authenticate` with the protected-resource metadata URL (Story 1). |
| `Spryker\ApiPlatform\Security\Resolver\IdentityResolver` | Identity resolution for security expressions. |

**Pattern for the MCP token:** mint the customer's Spryker JWT server-side and store it against the opaque MCP token; on each `/mcp` call resolve MCP token → stored shop token → build the `ApiUser` context. The shop token must never be serialized outbound (PRD §Security, mandatory test 9).

Relevant transfers used by the authenticator: `OauthAccessTokenValidationRequestTransfer`, `OauthAccessTokenValidationResponseTransfer`, `AuditLoggerConfigCriteriaTransfer`.

---

## 4. OAuth: what exists, what is missing

**Vendored OAuth modules** (`vendor/spryker/`): `oauth`, `oauth-api`, `oauth-client`, `oauth-customer-connector`, `oauth-customer-validation`, `oauth-company-user`, `oauth-permission`, `oauth-revoke`, `oauth-cryptography`, `oauth-extension`, `authentication-oauth`, `security-oauth-knpu`, plus backend/agent/merchant variants.

**Grant types wired today** — `vendor/spryker/oauth/src/Spryker/Zed/Oauth/OauthConfig.php`:
- `OauthConfig::GRANT_TYPE_PASSWORD = 'password'` (line 21)
- `OauthConfig::GRANT_TYPE_REFRESH_TOKEN = 'refresh_token'` (line 26)
- **No** `authorization_code` constant, and no `AuthCodeGrant` reference anywhere under `vendor/spryker/oauth*/src`.

**Library capability** — `vendor/league/oauth2-server/src/Grant/` ships:
`AuthCodeGrant.php`, `AbstractAuthorizeGrant.php`, `AbstractGrant.php`, `ClientCredentialsGrant.php`, `ImplicitGrant.php`, `PasswordGrant.php`, `RefreshTokenGrant.php`, `GrantTypeInterface.php`.

→ **`League\OAuth2\Server\Grant\AuthCodeGrant` (PKCE-capable) is available but unwired. Wiring it is the principal new capability.**

**Persistence already fits DCR** — `vendor/spryker/oauth/src/Spryker/Zed/Oauth/Persistence/Propel/Schema/spy_oauth.schema.xml`:

| Table | Columns |
|---|---|
| `spy_oauth_client` | `id_oauth_client`, `identifier`, `name`, `redirect_uri`, `secret`, `is_confidential` |
| `spy_oauth_access_token` | `id_oauth_access_token`, `identifier` (VARCHAR 3024), `scopes`, `user_identifier`, `fk_oauth_client`, `expirity_date` |
| `spy_oauth_scope` | `id_oauth_scope`, `identifier`, `description` |

`redirect_uri` + `is_confidential` already exist → Dynamic Client Registration writes public (non-confidential) clients with a redirect URI onto the existing schema. An **authorization-code store** is the gap (single-use, ≤60 s, PKCE challenge + method) — likely a new project-level table.

Existing project-level OAuth glue for reference: `src/Pyz/Glue/OauthApi`, `src/Pyz/Glue/AuthRestApi`, `src/Pyz/Glue/MultiFactorAuth`.

---

## 5. Storefront resources the MCP tools wrap

Resources are **YAML-declared and code-generated** (`spryker/api-platform`): `*.resource.yml` → `src/Generated/Api/Storefront`. 140 storefront resource YAMLs exist in vendor. Tools must call these, not reimplement.

### 5.1 Product search — `catalog-search`
`vendor/spryker/catalog-search-rest-api/resources/api/storefront/catalog-search.resource.yml`
- `name: CatalogSearch`, `shortName: catalog-search`
- Provider: `Spryker\Glue\CatalogSearchRestApi\Api\Storefront\Provider\CatalogSearchStorefrontProvider`
- Operation: `GetCollection` at `/catalog-search`
- `paginationEnabled: true`, `paginationItemsPerPage: 12` ← source of the PRD's "at most 12 products"
- Include: `abstract-products` via `Spryker\Glue\ProductsRestApi\Api\Storefront\Relationship\AbstractProductsRelationshipResolver`
- Readable props: `catalogSearchId` (identifier), `sort` (`CatalogSearchSort`: `sortParamNames`, `currentSortParam`, `currentSortOrder`, `sortParamLocalizedNames`), `pagination` (`Pagination`: `numFound`, `currentPage`, `maxPage`, `currentItemsPerPage`, `config.parameterName`, `config.itemsPerPageParameterName`, `config.defaultItemsPerPage`, `config.validItemsPerPageOptions`, `config.maxItemsPerPage`), `abstractProducts`
- Sort params available: `rating`, `name_asc`, `name_desc`, `price_asc`, `price_desc`, `popularity`

### 5.2 Product detail — `abstract-products` / `concrete-products`
`abstract-products.resource.yml` (4 layers), `concrete-products.resource.yml` (2), plus `abstract-product-prices`, `concrete-product-prices`, `abstract-product-availabilities`, `concrete-product-availabilities`, `abstract-product-image-sets`, `concrete-product-image-sets`.

### 5.3 Carts — `carts`
`vendor/spryker/carts-rest-api/resources/api/storefront/carts.resource.yml`
- `name: Carts`, `shortName: carts`
- Provider: `Spryker\Glue\CartsRestApi\Api\Storefront\Provider\CartsStorefrontProvider`
- Processor: `Spryker\Glue\CartsRestApi\Api\Storefront\Processor\CartsStorefrontProcessor`
- **`security: "is_granted('ROLE_CUSTOMER')"`** ← the authorization boundary the MCP token must satisfy; also what enforces per-customer cart isolation (PRD Story 6 scenario 4, mandatory test 7)
- Operations: `GetCollection`, `Post`, `Get`, `Patch` (`ifMatchRequired: true`), `Delete`; `paginationEnabled: false`
- Include: `items` → `CartItems` via `Spryker\Glue\CartsRestApi\Api\Storefront\Relationship\CartsItemsRelationshipResolver`; also `vouchers`
- Extension overlay: `vendor/spryker/order-amendments-rest-api/.../carts.resource.yml` adds `amendmentOrderReference`

### 5.4 Cart items — `cart-items`
6 layered `cart-items.resource.yml` files (`shortName: items`). Product-options layer (`vendor/spryker/product-options-rest-api`) adds `calculations` (`Calculations` object: `unitProductOptionPriceAggregation`, `sumProductOptionPriceAggregation`), readable `selectedProductOptions`, writable `productOptions`.

### 5.5 Checkout — `checkout`
`vendor/spryker/checkout-rest-api/resources/api/storefront/checkout.resource.yml`
- `name: Checkout`, `shortName: checkout`
- Processor: `Spryker\Glue\CheckoutRestApi\Api\Storefront\Processor\CheckoutStorefrontProcessor`
- **`security: "is_granted('CUSTOMER_ACCESS') and is_granted('ROLE_CUSTOMER')"`**, `securityAnonymousAuthRequired: true`
- Operation: `Post /checkout`, `normalizationContext: { gen_id: false }`
- Include: `orders` → `Orders`, `uriVariableMappings: { orderReferences: orderReference }`
- Props: `checkoutId` (identifier, never serialized), `idCart` (writable, cart UUID — e.g. `1ce91011-8d60-59ef-9fe0-4493ef36c4f0`), `customer` (`Customer` object, nullable/writable)
- Companion: `checkout-data.resource.yml` (default payment/shipment/address data — PRD relies on customer defaults)

### 5.6 Orders
`orders.resource.yml`; project overlay at `src/Pyz/Glue/OrdersRestApi`.

### 5.7 Auth reference — `access-tokens`
`vendor/spryker/auth-rest-api/resources/api/storefront/access-tokens.resource.yml`
- `name: AccessTokens`, `shortName: access-tokens`
- `resourceAttributesClassName: Generated\Shared\Transfer\RestAccessTokensAttributesTransfer`
- Processor: `Spryker\Glue\AuthRestApi\Api\Storefront\Processor\AccessTokensStorefrontProcessor`
- Props: `username` (required, email), `password` (required), `accessToken` (readable identifier), `tokenType` (`Bearer`)
- Sample creds in the YAML are `sonia@spryker.com` / `change123` — **note:** per project memory, `sonia@spryker.com` does not exist in this shop; use `spencor.hopkin@acme.com` / `change123` for real logins and test fixtures.

Project-level Glue overlays that may need touching: `src/Pyz/Glue/CartsRestApi`, `src/Pyz/Glue/CheckoutRestApi`, `src/Pyz/Glue/ProductsRestApi`, `src/Pyz/Glue/AuthRestApi`, `src/Pyz/Glue/OrdersRestApi`, `src/Pyz/Glue/GlueApplication`, `src/Pyz/Glue/Router`.

---

## 6. Target namespace

`src/Demo/` currently has **`Client`, `Shared`, `Yves`, `Zed` — no `Glue`.** This feature introduces `src/Demo/Glue/`.

Existing demo modules for convention reference:
- `src/Demo/Zed/AiCommerce/` → `Business/BackofficeAssistant`, `Communication/Plugin`
- `src/Demo/Zed/AiCommerce/AiCommerceConfig.php`
- `src/Demo/Shared/AiCommerce/`
- `src/Demo/Yves/AiCommerce/`
- Also present: `src/Demo/Zed/AiFoundation`, `AnalyticsGui`, `AmazonQuicksight`

Namespace registration lives in `config/Shared/config_default.php` (project namespace list) — confirm `Demo` is registered for the Glue/GlueStorefront application before adding the layer.

---

## 7. Feature flag — Configuration Management

Descriptor convention: `data/configuration/<feature>.configuration.yml`. Existing files: `ai_commerce.configuration.yml`, `ai_vendor.configuration.yml`, `availability_widget.configuration.yml`, `shop_ui.configuration.yml`.

Schema: `Spryker/Configuration/resources/configuration/configuration-schema-v1.json` (referenced via `# yaml-language-server: $schema=../../../../Spryker/Configuration/resources/configuration/configuration-schema-v1.json`).

Structure observed in `data/configuration/ai_commerce.configuration.yml`:

```yaml
features:
  - key: ai_commerce
    order: 1
    tabs:
      - key: backoffice_assistant
        enabled: true
        groups:
          - key: ai_vendor
            name: AI Vendor
            description: …
            enabled: true
            order: 1
            scopes: [global]
            settings:
              - key: ai_configuration
                name: AI Configuration
                type: radio        # also seen: string
                default_value: 'AI_COMMERCE:AI_CONFIGURATION_BACKOFFICE_ASSISTANT_OPENAI'
                enabled: true
                secret: false
                storefront: false
                order: 1
                scopes: [global]
                options:
                  - { value: '…', label: OpenAI }
                dependencies:
                  - when:
                      any:
                        - setting: ai_commerce:backoffice_assistant:ai_vendor:ai_configuration
                          operator: equals
                          value: '…'
```

Setting-key addressing is `<feature>:<tab>:<group>:<setting>`. Suggested flag: **`mcp_commerce:server:general:is_enabled`** (type `boolean`, `scopes: [global]`, `secret: false`, `storefront: false`).

> Per project memory: the Configuration Management panel renders without a cache clear.

---

## 8. Endpoint hosts (resolved from `deploy.dev.yml`)

| Application | Host |
|---|---|
| Storefront API (Glue) — **MCP target** | `http://glue.eu.spryker.local` |
| Glue Backend | `http://glue-backend.eu.spryker.local` |
| Back Office | `http://backoffice.eu.spryker.local` (primal) |
| Yves EU | `http://yves.eu.spryker.local` |
| Merchant Portal | `http://mp.eu.spryker.local` |
| Backend Gateway | `http://backend-gateway.eu.spryker.local` |
| Swagger / Storybook | `swagger` container / `http://storybook.spryker.local` |

New routes (all greenfield, all on the Storefront API host):

| Path | Purpose | Story |
|---|---|---|
| `/.well-known/oauth-authorization-server` | AS metadata (RFC 8414) | 1 |
| `/.well-known/oauth-protected-resource` | PR metadata (RFC 9728) | 1 |
| `/register` | Dynamic Client Registration (RFC 7591) | 2 |
| `/authorize` | Authorization endpoint + login/consent UI (Twig) | 3 |
| `/token` | Token endpoint, `authorization_code` + PKCE `S256` | 3 |
| `/mcp` | Streamable HTTP MCP transport | 4–7 |

Back Office toggle: `http://backoffice.eu.spryker.local/configuration` (existing, Configuration module) — Story 8.

---

## 9. Reference implementation notes (Silpo)

Fetched from `https://ai-factory.silpo.ua/docs/mcp`:
- Single endpoint `https://mcp.silpo.ua/mcp`, Streamable HTTP.
- 401 → `/.well-known/oauth-authorization-server` → `POST /register` → PKCE → `/authorize` (login on `auth.silpo.ua`, phone+OTP or password) → MCP token via `Authorization: Bearer <mcp_token>`.
- Explicit property: *"AI-клієнт ніколи не бачить Silpo JWT гостя"* — the internal JWT stays inside the infrastructure. Mirrored as PRD Goal 3 + mandatory test 9.
- 39 tools across Location/Delivery (6), Product Discovery (7), Catalog/Categories (6), Cart (7), Orders (2), Profile (4), Loyalty (7). Naming convention `silpo_<verb>_<noun>`.
- Rate limiting per user via `Cookie: mcp-user={userId}`.
- Docs do **not** document an `Mcp-Session-Id` header or protocol-version field → this PRD specifies protocol-version negotiation on `initialize` itself (Story 4).

**This PRD's 5 tools** (scoped down from 39) map to Silpo equivalents as:

| This feature | Silpo analogue | Backing resource |
|---|---|---|
| product search | `silpo_get_products` / `silpo_find_products_batch` | `catalog-search` |
| product detail | `silpo_get_product_details` | `abstract-products` / `concrete-products` |
| add to cart | `silpo_add_or_update_cart_products` | `carts` + `cart-items` |
| checkout | *(no direct analogue — Silpo updates cart then orders)* | `checkout` + `checkout-data` |
| order list | `silpo_get_my_online_orders` | `orders` |

---

## 10. Commands the implementation will need

Per project conventions (`.claude/CLAUDE.md`, `.claude/CLAUDE.local.md`):

```bash
script -q /dev/null docker/sdk run          # start the app
docker/sdk cli console transfer:generate    # after any transfer.xml change
docker/sdk cli console propel:install       # after schema change (auth-code store)
docker/sdk cli console cache:empty-all
docker/sdk cli console cache:class-resolver:build   # if Demo overrides core classes
sh .claude/bash-local/validation.sh         # interim static analysis
```

The API Platform resource generator must be re-run after any `*.resource.yml` change so `src/Generated/Api/Storefront` is refreshed — note `config/GlueStorefront/routes/api_platform.php` no-ops if that directory is absent.

---

## 11. Open implementation decisions (for the planner, not the PRD)

1. **Streamable HTTP transport** — hand-roll a minimal JSON-RPC 2.0 over HTTP handler (`initialize`, `tools/list`, `tools/call`) vs. adopt a third-party PHP MCP library. Hand-rolling avoids a new dependency and the 5-tool scope is small; a library buys spec conformance. Recommend hand-rolled for the demo, isolated behind an interface.
2. **MCP-token ↔ shop-token storage** — new project table vs. reuse `spy_oauth_access_token` with a distinguishing scope. A new table keeps revocation independent (PRD §Security) and avoids coupling to core token lifecycle.
3. **Authorization-code store** — new table required (single-use, ≤60 s TTL, PKCE challenge + method); nothing existing fits.
4. **Login on `/authorize`** — reuse the storefront customer authentication via the Auth API processor path, rendered through the already-registered `TwigBundle`.
5. **Rate limiting** (60 calls/min per token) — in-transport counter backed by the Redis key-value store.
6. **`AuthCodeGrant` wiring** — extend the Oauth module's grant configuration from the `Demo` namespace so it stays demo-branch-only.
