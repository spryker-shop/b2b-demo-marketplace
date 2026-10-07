<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace PyzTest\Glue\McpCommerce;

use Demo\Shared\McpCommerce\McpCommerceConstants;
use Orm\Zed\McpCommerce\Persistence\PyzMcpAccessTokenQuery;
use Orm\Zed\Sales\Persistence\SpySalesOrderQuery;
use SprykerTest\Glue\Testify\Tester\ApiEndToEndTester;

/**
 * Drives the MCP Commerce Server over real HTTP.
 *
 * Every helper here speaks the wire protocol an AI client would speak: form-encoded OAuth requests,
 * JSON-RPC 2.0 envelopes on `POST /mcp`, and a bearer credential that is an opaque MCP token — never
 * a shop JWT. Nothing is stubbed, so the assertions in the Cests cover the real routing, firewall and
 * sub-request pipeline.
 *
 * Inherited Methods
 *
 * @method void wantToTest($text)
 * @method void wantTo($text)
 * @method void execute($callable)
 * @method void expectTo($prediction)
 * @method void expect($prediction)
 * @method void amGoingTo($argumentation)
 * @method void am($role)
 * @method void lookForwardTo($achieveValue)
 * @method void comment($description)
 * @method void pause()
 *
 * @SuppressWarnings(\PyzTest\Glue\McpCommerce\PHPMD)
 */
class McpCommerceRestApiTester extends ApiEndToEndTester
{
    use _generated\McpCommerceRestApiTesterActions;

    /**
     * Customer used for every checkout and order assertion.
     *
     * `spencor.hopkin@acme.com` cannot be used: a B2B purchasing-limit permission refuses order
     * placement for that account. `DE--2` has stored addresses, which the checkout tool reads from
     * the `customers` resource, and no purchasing limit.
     *
     * @var string
     */
    public const CUSTOMER_EMAIL = 'maria.williams@acme.com';

    /**
     * @var string
     */
    public const CUSTOMER_REFERENCE = 'DE--2';

    /**
     * A second, distinct customer, used only to prove cross-customer isolation.
     *
     * @var string
     */
    public const OTHER_CUSTOMER_EMAIL = 'maggie.may@acme.com';

    /**
     * @var string
     */
    public const OTHER_CUSTOMER_REFERENCE = 'DE--3';

    /**
     * @var string
     */
    public const CUSTOMER_PASSWORD = 'change123';

    /**
     * Redirect URI used by every test client.
     *
     * It must satisfy two constraints at once. It has to be inside the registration allow-list (the
     * `http://127.0.0.1` pattern covers it — note the `*.spryker.local` pattern requires `https`), and
     * it has to be REACHABLE from the test container: `GlueRest::prepareHeaders()` re-enables redirect
     * following before every request, so the browser follows the authorization redirect no matter what
     * the test asks for, and a dead host surfaces as a cURL connection error instead of a test result.
     * This target simply 404s, which is harmless — the code and state are then read off the landing
     * URL by {@see self::grabRedirectTargetUrl()}.
     *
     * @var string
     */
    public const REDIRECT_URI = 'http://127.0.0.1:9000/mcp-test-callback';

    /**
     * Path component of {@see self::REDIRECT_URI}. The browser reports the landing URI relative to the
     * host, so assertions about where the authorization redirect went compare against this.
     *
     * @var string
     */
    public const REDIRECT_URI_PATH = '/mcp-test-callback';

    /**
     * @var string
     */
    public const STORE_NAME = 'DE';

    /**
     * A concrete SKU whose price clears the EUR 40 minimum-order threshold at quantity 2
     * (EUR 21.70 each). Resolved by SKU, never by an import-order-dependent id (PRD §4.4).
     *
     * @var string
     */
    public const CONCRETE_SKU = '420549';

    /**
     * The abstract SKU of the same product, used for the product detail assertions.
     *
     * @var string
     */
    public const ABSTRACT_SKU = 'M21090';

    /**
     * @var int
     */
    public const QUANTITY_CLEARING_MINIMUM_ORDER = 2;

    /**
     * @var string
     */
    public const HEADER_CONTENT_TYPE = 'Content-Type';

    /**
     * @var string
     */
    public const HEADER_AUTHORIZATION = 'Authorization';

    /**
     * @var string
     */
    protected const CONTENT_TYPE_FORM_URLENCODED = 'application/x-www-form-urlencoded';

    /**
     * @var string
     */
    protected const CONTENT_TYPE_JSON = 'application/json';

    /**
     * Storage entry holding every globally scoped Configuration Management value. This is the key the
     * Glue feature-flag read path resolves, so it is the only place a test can flip the flag with
     * confidence.
     *
     * @var string
     */
    protected const STORAGE_KEY_CONFIGURATION_GLOBAL = 'configuration:global';

    /**
     * Selects the form-urlencoded request body encoding used by the OAuth endpoints.
     *
     * `GlueRest::prepareHeaders()` re-stamps `Content-Type: application/vnd.api+json` before EVERY
     * request, and Codeception's REST module picks the body encoding from exactly that header. So a
     * plain `haveHttpHeader()` is silently reverted and an OAuth form post would arrive as JSON, with
     * an empty `$request->request` on the server. Opting out of the default header first is what makes
     * the override stick.
     *
     * @return void
     */
    public function useFormUrlEncodedContentType(): void
    {
        $this->dontSendDefaultHeader(static::HEADER_CONTENT_TYPE);
        $this->haveHttpHeader(static::HEADER_CONTENT_TYPE, static::CONTENT_TYPE_FORM_URLENCODED);
    }

    /**
     * Selects the plain JSON request body encoding used by `/register` and `/mcp`.
     *
     * See {@see self::useFormUrlEncodedContentType()} for why the default header must be suppressed.
     *
     * @return void
     */
    public function useJsonContentType(): void
    {
        $this->dontSendDefaultHeader(static::HEADER_CONTENT_TYPE);
        $this->haveHttpHeader(static::HEADER_CONTENT_TYPE, static::CONTENT_TYPE_JSON);
    }

    /**
     * Registers a fresh public OAuth client through Dynamic Client Registration and returns its
     * generated identifier.
     *
     * @param string|null $redirectUri
     *
     * @return string
     */
    public function haveRegisteredClientIdentifier(?string $redirectUri = null): string
    {
        $this->sendRegistrationRequest([
            'client_name' => 'PyzTest MCP client',
            'redirect_uris' => [$redirectUri ?? static::REDIRECT_URI],
        ]);

        $clientIdentifier = $this->grabResponseValue('client_id');

        $this->assertIsString($clientIdentifier, 'Client registration did not return a client_id.');
        $this->assertNotSame('', $clientIdentifier);

        return $clientIdentifier;
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return void
     */
    public function sendRegistrationRequest(array $payload): void
    {
        $this->useJsonContentType();
        $this->sendPost(McpCommerceConstants::PATH_REGISTER, $payload);
    }

    /**
     * Returns a fresh, cryptographically random PKCE code verifier.
     *
     * @return string
     */
    public function createCodeVerifier(): string
    {
        return rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
    }

    /**
     * Returns the S256 code challenge for a verifier: base64url(sha256(verifier)).
     *
     * @param string $codeVerifier
     *
     * @return string
     */
    public function createCodeChallenge(string $codeVerifier): string
    {
        return rtrim(strtr(base64_encode(hash('sha256', $codeVerifier, true)), '+/', '-_'), '=');
    }

    /**
     * Submits the consent screen with real customer credentials and returns the issued
     * authorization code, taken from the `Location` redirect exactly as a browser-based client would.
     *
     * @param string $clientIdentifier
     * @param string $codeChallenge
     * @param string $customerEmail
     * @param string $state
     *
     * @return string
     */
    public function haveAuthorizationCode(
        string $clientIdentifier,
        string $codeChallenge,
        string $customerEmail = self::CUSTOMER_EMAIL,
        string $state = 'pyztest-state',
    ): string {
        $this->sendAuthorizeRequest($clientIdentifier, $codeChallenge, $customerEmail, $state);

        $code = $this->grabAuthorizationCodeFromLocationHeader();

        $this->assertNotNull($code, 'The authorize endpoint did not redirect with an authorization code.');

        return (string)$code;
    }

    /**
     * @param string $clientIdentifier
     * @param string $codeChallenge
     * @param string $customerEmail
     * @param string $state
     * @param string $codeChallengeMethod
     *
     * @return void
     */
    public function sendAuthorizeRequest(
        string $clientIdentifier,
        string $codeChallenge,
        string $customerEmail = self::CUSTOMER_EMAIL,
        string $state = 'pyztest-state',
        string $codeChallengeMethod = 'S256',
    ): void {
        $this->stopFollowingRedirects();
        $this->useFormUrlEncodedContentType();
        $this->sendPost(McpCommerceConstants::PATH_AUTHORIZE, [
            'response_type' => 'code',
            'client_id' => $clientIdentifier,
            'redirect_uri' => static::REDIRECT_URI,
            'code_challenge' => $codeChallenge,
            'code_challenge_method' => $codeChallengeMethod,
            'state' => $state,
            'email' => $customerEmail,
            'password' => static::CUSTOMER_PASSWORD,
            'approve' => 'yes',
        ]);
    }

    /**
     * @return string|null
     */
    public function grabAuthorizationCodeFromLocationHeader(): ?string
    {
        return $this->grabQueryParameterFromLocationHeader('code');
    }

    /**
     * Returns a single response header as a string.
     *
     * `grabHttpHeader()` is typed as `string|array|null` because a header may legitimately repeat, so
     * every caller would otherwise need its own cast. Repeated values are joined, which keeps a
     * `assertStringContainsString` assertion meaningful either way.
     *
     * @param string $headerName
     *
     * @return string
     */
    public function grabHeaderAsString(string $headerName): string
    {
        $headerValue = $this->grabHttpHeader($headerName, false);

        if (is_array($headerValue)) {
            return implode(', ', $headerValue);
        }

        return (string)$headerValue;
    }

    /**
     * Returns the URL the authorization endpoint redirected to.
     *
     * Redirect following cannot be switched off for a single request — `GlueRest::prepareHeaders()`
     * re-enables it before every call — so by the time a test inspects the result the browser has
     * already landed on the callback URL. That landing URL carries the same `code` and `state` query
     * parameters the 302 `Location` header carried, so it is read first and the header is used only as
     * a fallback for the cases where no redirect happened at all.
     *
     * @return string
     */
    public function grabRedirectTargetUrl(): string
    {
        $currentUri = $this->grabCurrentUri();

        if (str_contains($currentUri, '?')) {
            return $currentUri;
        }

        if ($this->grabResponseCode() < 300 || $this->grabResponseCode() >= 400) {
            return '';
        }

        return $this->grabHeaderAsString('Location');
    }

    /**
     * @param string $parameterName
     *
     * @return string|null
     */
    public function grabQueryParameterFromLocationHeader(string $parameterName): ?string
    {
        $query = parse_url($this->grabRedirectTargetUrl(), PHP_URL_QUERY);

        if (!is_string($query)) {
            return null;
        }

        $parameters = [];
        parse_str($query, $parameters);

        $value = $parameters[$parameterName] ?? null;

        return is_string($value) ? $value : null;
    }

    /**
     * Exchanges an authorization code for an MCP access token.
     *
     * @param string $clientIdentifier
     * @param string $code
     * @param string $codeVerifier
     *
     * @return void
     */
    public function sendTokenRequest(string $clientIdentifier, string $code, string $codeVerifier): void
    {
        $this->useFormUrlEncodedContentType();
        $this->sendPost(McpCommerceConstants::PATH_TOKEN, [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'client_id' => $clientIdentifier,
            'redirect_uri' => static::REDIRECT_URI,
            'code_verifier' => $codeVerifier,
        ]);
    }

    /**
     * Posts an arbitrary token-request payload so a test can omit or corrupt individual parameters.
     * Needed to assert that a missing parameter is rejected rather than silently skipping a check.
     *
     * @param array<string, string> $payload
     *
     * @return void
     */
    public function sendRawTokenRequest(array $payload): void
    {
        $this->useFormUrlEncodedContentType();
        $this->sendPost(McpCommerceConstants::PATH_TOKEN, $payload);
    }

    /**
     * Runs the whole cold-start authorization chain — register, PKCE authorize, token exchange — and
     * returns the resulting opaque MCP access token.
     *
     * @param string $customerEmail
     *
     * @return string
     */
    public function haveMcpAccessToken(string $customerEmail = self::CUSTOMER_EMAIL): string
    {
        $codeVerifier = $this->createCodeVerifier();
        $clientIdentifier = $this->haveRegisteredClientIdentifier();
        $code = $this->haveAuthorizationCode(
            $clientIdentifier,
            $this->createCodeChallenge($codeVerifier),
            $customerEmail,
        );

        $this->sendTokenRequest($clientIdentifier, $code, $codeVerifier);

        $accessToken = $this->grabResponseValue('access_token');

        $this->assertIsString($accessToken, 'The token endpoint did not return an access_token.');
        $this->assertNotSame('', $accessToken);

        return $accessToken;
    }

    /**
     * Sends a JSON-RPC 2.0 request to `POST /mcp`.
     *
     * @param string $method
     * @param array<string, mixed>|null $params
     * @param string|null $accessToken
     * @param int $id
     *
     * @return void
     */
    public function sendMcpRequest(
        string $method,
        ?array $params = null,
        ?string $accessToken = null,
        int $id = 1,
    ): void {
        $payload = [
            'jsonrpc' => McpCommerceConstants::JSON_RPC_VERSION,
            'id' => $id,
            'method' => $method,
        ];

        if ($params !== null) {
            $payload['params'] = $params;
        }

        $this->useJsonContentType();

        if ($accessToken !== null) {
            $this->haveHttpHeader(static::HEADER_AUTHORIZATION, 'Bearer ' . $accessToken);
        }

        $this->sendPost(McpCommerceConstants::PATH_MCP, $payload);
    }

    /**
     * Calls an MCP tool and returns the decoded `result` payload.
     *
     * @param string $toolName
     * @param array<string, mixed> $arguments
     * @param string $accessToken
     * @param int $id
     *
     * @return array<string, mixed>
     */
    public function callMcpTool(
        string $toolName,
        array $arguments,
        string $accessToken,
        int $id = 1,
    ): array {
        $this->sendMcpRequest(
            'tools/call',
            ['name' => $toolName, 'arguments' => $arguments],
            $accessToken,
            $id,
        );

        $result = $this->grabResponseJson()['result'] ?? null;

        $this->assertIsArray($result, sprintf('Tool "%s" did not return a JSON-RPC result.', $toolName));

        return $result;
    }

    /**
     * Calls a tool and asserts it succeeded, returning its structured content.
     *
     * @param string $toolName
     * @param array<string, mixed> $arguments
     * @param string $accessToken
     *
     * @return array<string, mixed>
     */
    public function callSuccessfulMcpTool(string $toolName, array $arguments, string $accessToken): array
    {
        $result = $this->callMcpTool($toolName, $arguments, $accessToken);

        $this->assertFalse(
            $result['isError'] ?? true,
            sprintf('Tool "%s" reported an error: %s', $toolName, $this->extractToolText($result)),
        );

        $structuredContent = $result['structuredContent'] ?? null;
        $this->assertIsArray($structuredContent, sprintf('Tool "%s" returned no structuredContent.', $toolName));

        return $structuredContent;
    }

    /**
     * Returns the human-readable text a tool put in its first content block.
     *
     * @param array<string, mixed> $result
     *
     * @return string
     */
    public function extractToolText(array $result): string
    {
        $content = $result['content'] ?? [];

        if (!is_array($content) || $content === []) {
            return '';
        }

        $firstBlock = reset($content);

        return is_array($firstBlock) ? (string)($firstBlock['text'] ?? '') : '';
    }

    /**
     * @param string $key
     *
     * @return mixed
     */
    public function grabResponseValue(string $key)
    {
        return $this->grabResponseJson()[$key] ?? null;
    }

    /**
     * Asserts the whole raw response body carries neither a shop access token nor a refresh token.
     *
     * A Spryker shop JWT always starts with the base64url-encoded `{"typ"...` header, whose prefix is
     * `eyJ`. PRD Goal 3 / mandatory scenario 9 demand that neither it nor a refresh token ever leaves
     * the MCP surface, in a success body or in an error body.
     *
     * @return void
     */
    public function dontSeeResponseContainsShopToken(): void
    {
        $rawResponse = $this->grabResponse();

        $this->assertStringNotContainsString(
            'eyJ',
            $rawResponse,
            'The MCP response body contains what looks like a shop JWT.',
        );
        $this->assertStringNotContainsStringIgnoringCase(
            'refresh_token',
            $rawResponse,
            'The MCP response body mentions a refresh token.',
        );
        $this->assertStringNotContainsStringIgnoringCase(
            'refreshToken',
            $rawResponse,
            'The MCP response body mentions a refresh token.',
        );
        $this->assertStringNotContainsStringIgnoringCase(
            'accessToken',
            $rawResponse,
            'The MCP response body mentions a shop accessToken attribute.',
        );
    }

    /**
     * Mints a real shop access token through the platform's own `/access-tokens` endpoint. Used only
     * to set up preconditions the MCP surface deliberately cannot create — an existing empty cart, or
     * a cart belonging to a different customer.
     *
     * @param string $customerEmail
     *
     * @return string
     */
    public function haveShopAccessToken(string $customerEmail): string
    {
        // An MCP bearer credential left over from an earlier step must not travel to `/access-tokens`,
        // which authenticates by request body and rejects a foreign Authorization header.
        $this->unsetHttpHeader(static::HEADER_AUTHORIZATION);
        $this->useJsonContentType();
        $this->sendPost('/access-tokens', [
            'data' => [
                'type' => 'access-tokens',
                'attributes' => [
                    'username' => $customerEmail,
                    'password' => static::CUSTOMER_PASSWORD,
                ],
            ],
        ]);

        $accessToken = $this->grabResponseJson()['data']['attributes']['accessToken'] ?? null;

        $this->assertIsString(
            $accessToken,
            sprintf(
                'Could not mint a shop access token for %s. Response: %s',
                $customerEmail,
                substr($this->grabResponse(), 0, 300),
            ),
        );

        return $accessToken;
    }

    /**
     * Creates a real, genuinely EMPTY cart for a customer through the Storefront API.
     *
     * This exists because the MCP surface offers no way to create an empty cart: `add_to_cart` always
     * puts an item in. Mandatory scenario 12 requires an EXISTING but EMPTY cart, which is a stronger
     * assertion than "cart not found".
     *
     * @param string $customerEmail
     *
     * @return string
     */
    public function haveEmptyCartUuid(string $customerEmail): string
    {
        $shopAccessToken = $this->haveShopAccessToken($customerEmail);

        $this->useJsonContentType();
        $this->haveHttpHeader(static::HEADER_AUTHORIZATION, 'Bearer ' . $shopAccessToken);
        $this->sendPost('/carts', [
            'data' => [
                'type' => 'carts',
                'attributes' => [
                    'name' => 'PyzTest empty cart',
                    'priceMode' => 'GROSS_MODE',
                    'currency' => 'EUR',
                    'store' => static::STORE_NAME,
                ],
            ],
        ]);

        $cartUuid = $this->getDataFromResponseByJsonPath('$.data.id');

        if (is_array($cartUuid)) {
            $cartUuid = reset($cartUuid);
        }

        $this->assertIsString($cartUuid, 'Could not create an empty cart for ' . $customerEmail);

        $this->unsetHttpHeader(static::HEADER_AUTHORIZATION);

        return $cartUuid;
    }

    /**
     * Counts the orders currently placed for a customer, read straight from the sales table so the
     * assertion cannot be satisfied by a filtered API view.
     *
     * @param string $customerReference
     *
     * @return int
     */
    public function getOrderCount(string $customerReference): int
    {
        return SpySalesOrderQuery::create()
            ->filterByCustomerReference($customerReference)
            ->count();
    }

    /**
     * Puts an item into a cart using the OWNING customer's shop credential.
     *
     * Needed to set up a cart that belongs to another customer and is worth checking out, so the
     * cross-customer checkout test asserts a refusal on a genuinely valid target rather than on an
     * empty or nonexistent one.
     *
     * @param string $cartUuid
     * @param string $shopAccessToken
     * @param string $sku
     * @param int $quantity
     *
     * @return void
     */
    public function addItemToCartWithShopToken(
        string $cartUuid,
        string $shopAccessToken,
        string $sku,
        int $quantity,
    ): void {
        $this->useJsonContentType();
        $this->haveHttpHeader(static::HEADER_AUTHORIZATION, 'Bearer ' . $shopAccessToken);
        $this->sendPost(sprintf('/carts/%s/items', rawurlencode($cartUuid)), [
            'data' => [
                'type' => 'items',
                'attributes' => [
                    'sku' => $sku,
                    'quantity' => $quantity,
                ],
            ],
        ]);

        $this->unsetHttpHeader(static::HEADER_AUTHORIZATION);
    }

    /**
     * Counts the items currently in a cart, read through the Storefront API with the customer's own
     * shop credential.
     *
     * Used to assert that a rejected `add_to_cart` truly left the cart untouched (mandatory
     * scenario 11) rather than merely reporting an error.
     *
     * @param string $cartUuid
     * @param string $customerEmail
     *
     * @return int
     */

    /**
     * Guarantees the customer has a delivery address, creating one through the storefront API when
     * they have none. A freshly seeded environment (CI) ships customers without addresses, so a
     * checkout test that merely assumes demo data has one fails with
     * "shippingAddress.address1: This value should not be blank" — a precondition the test must
     * arrange itself rather than inherit from whatever state the database happens to be in.
     *
     * @return void
     */
    public function haveCustomerDeliveryAddress(string $customerEmail): void
    {
        $shopAccessToken = $this->haveShopAccessToken($customerEmail);
        $customerReference = $customerEmail === static::OTHER_CUSTOMER_EMAIL
            ? static::OTHER_CUSTOMER_REFERENCE
            : static::CUSTOMER_REFERENCE;
        $addressesPath = sprintf('/customers/%s/addresses', rawurlencode($customerReference));

        $this->haveHttpHeader(static::HEADER_AUTHORIZATION, 'Bearer ' . $shopAccessToken);
        $this->sendGet($addressesPath);

        $payload = $this->grabResponseJson();
        $existingAddresses = is_array($payload['data'] ?? null) ? $payload['data'] : [];

        if ($existingAddresses !== []) {
            $this->unsetHttpHeader(static::HEADER_AUTHORIZATION);

            return;
        }

        $this->haveHttpHeader(static::HEADER_CONTENT_TYPE, static::CONTENT_TYPE_JSON);
        $this->sendPost($addressesPath, [
            'data' => [
                'type' => 'addresses',
                'attributes' => [
                    'salutation' => 'Ms',
                    'firstName' => 'Mcp',
                    'lastName' => 'Tester',
                    'address1' => 'Julie-Wolfthorn-Strasse',
                    'address2' => '1',
                    'zipCode' => '10115',
                    'city' => 'Berlin',
                    'iso2Code' => 'DE',
                    'isDefaultShipping' => true,
                    'isDefaultBilling' => true,
                ],
            ],
        ]);

        $this->unsetHttpHeader(static::HEADER_AUTHORIZATION);
    }

    public function getCartItemCount(string $cartUuid, string $customerEmail): int
    {
        $shopAccessToken = $this->haveShopAccessToken($customerEmail);

        $this->haveHttpHeader(static::HEADER_AUTHORIZATION, 'Bearer ' . $shopAccessToken);
        $this->sendGet(sprintf('/carts/%s?include=items', rawurlencode($cartUuid)));

        $payload = $this->grabResponseJson();

        $this->unsetHttpHeader(static::HEADER_AUTHORIZATION);

        $included = $payload['included'] ?? [];

        if (!is_array($included)) {
            return 0;
        }

        $itemCount = 0;

        foreach ($included as $resource) {
            if (!is_array($resource) || !(($resource['type'] ?? null) === 'items')) {
                continue;
            }

            $itemCount++;
        }

        return $itemCount;
    }

    /**
     * Reports whether a given order reference belongs to the given customer.
     *
     * Order references are randomly generated and are NOT prefixed with the customer reference, so
     * ownership has to be read from the sales table rather than inferred from the string.
     *
     * @param string $customerReference
     * @param string $orderReference
     *
     * @return bool
     */
    public function hasOrderWithReference(string $customerReference, string $orderReference): bool
    {
        return SpySalesOrderQuery::create()
            ->filterByCustomerReference($customerReference)
            ->filterByOrderReference($orderReference)
            ->exists();
    }

    /**
     * Backdates an already-issued MCP token past its expiry.
     *
     * Mandatory scenario 2 needs a genuinely expired token. Waiting out the 8-hour TTL is not an
     * option, and minting a pre-expired token through a test-only code path would not exercise the
     * production validator, so the stored expiry is moved into the past instead.
     *
     * @param string $accessTokenIdentifier
     *
     * @return void
     */
    public function expireMcpAccessToken(string $accessTokenIdentifier): void
    {
        $pyzMcpAccessTokenEntity = PyzMcpAccessTokenQuery::create()
            ->filterByIdentifier($accessTokenIdentifier)
            ->findOne();

        $this->assertNotNull($pyzMcpAccessTokenEntity, 'The MCP access token was not persisted.');

        $pyzMcpAccessTokenEntity->setExpiresAt(date('Y-m-d H:i:s', time() - 3600));
        $pyzMcpAccessTokenEntity->save();
    }

    /**
     * Revokes an already-issued MCP token through the same store the validator reads, so mandatory
     * scenario 3 exercises the production revocation check.
     *
     * @param string $accessTokenIdentifier
     *
     * @return void
     */
    public function revokeMcpAccessToken(string $accessTokenIdentifier): void
    {
        $pyzMcpAccessTokenEntity = PyzMcpAccessTokenQuery::create()
            ->filterByIdentifier($accessTokenIdentifier)
            ->findOne();

        $this->assertNotNull($pyzMcpAccessTokenEntity, 'The MCP access token was not persisted.');

        $pyzMcpAccessTokenEntity->setIsRevoked(true);
        $pyzMcpAccessTokenEntity->save();
    }

    /**
     * Switches the MCP Commerce Server feature flag and verifies the change actually landed in the
     * storage the Glue layer reads.
     *
     * The flag is read through `Configuration` -> `ConfigurationStorageReader`, which resolves it from
     * the key-value store entry `kv:configuration:global`. Writing the database row alone is NOT
     * sufficient — `configuration:sync` does not propagate a value to that key on this environment, so
     * a database-only flip would silently no-op and produce a false PASS. Writing the storage entry is
     * therefore the only reliable way to toggle the flag from a test, and the value is read back so a
     * failed write can never masquerade as a passing assertion.
     *
     * @param bool $isEnabled
     *
     * @return void
     */
    public function setFeatureFlag(bool $isEnabled): void
    {
        $storageClient = $this->getLocator()->storage()->client();
        $configuration = $storageClient->get(static::STORAGE_KEY_CONFIGURATION_GLOBAL);

        if (!is_array($configuration)) {
            $configuration = [];
        }

        $expectedValue = $isEnabled ? 'true' : 'false';
        $configuration[McpCommerceConstants::CONFIGURATION_KEY_IS_ENABLED] = $expectedValue;

        $storageClient->set(
            static::STORAGE_KEY_CONFIGURATION_GLOBAL,
            (string)json_encode($configuration),
        );

        $writtenConfiguration = $storageClient->get(static::STORAGE_KEY_CONFIGURATION_GLOBAL);

        $this->assertIsArray($writtenConfiguration, 'The configuration storage entry could not be read back.');
        $this->assertSame(
            $expectedValue,
            $writtenConfiguration[McpCommerceConstants::CONFIGURATION_KEY_IS_ENABLED] ?? null,
            'The feature flag was not persisted to the storage the Glue layer reads.',
        );
    }

    /**
     * @return void
     */
    public function deleteAuthorizationHeader(): void
    {
        $this->unsetHttpHeader(static::HEADER_AUTHORIZATION);
    }
}
