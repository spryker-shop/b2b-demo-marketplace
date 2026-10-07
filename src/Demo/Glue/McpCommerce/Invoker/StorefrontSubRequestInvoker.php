<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Glue\McpCommerce\Invoker;

use Generated\Shared\Transfer\CustomerTransfer;
use Spryker\ApiPlatform\EventSubscriber\IdentityRequestSubscriber;
use Spryker\ApiPlatform\Security\ApiUser;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Http\Authenticator\Token\PostAuthenticationToken;
use Throwable;

/**
 * Executes MCP tool calls against the existing Storefront API resources by dispatching internal
 * Symfony sub-requests, so the resource `security:` expressions, providers and processors are reused
 * verbatim instead of being reimplemented.
 *
 * Two mechanics make this work:
 * - Identity travels as request attributes (`_oauth_identity_claims`, `CustomerTransfer`), the same
 *   attributes the Storefront identity subscribers would set; no `Authorization` header is involved,
 *   so no shop token is ever minted, stored or forwarded.
 * - The Symfony firewall short-circuits on sub-requests, so the token storage would be empty while
 *   API Platform still evaluates `is_granted('ROLE_CUSTOMER')`. The invoker therefore seeds the token
 *   storage itself and restores the previous token when the sub-request completes.
 */
class StorefrontSubRequestInvoker implements StorefrontSubRequestInvokerInterface
{
    /**
     * @var string
     */
    protected const FIREWALL_NAME = 'main';

    /**
     * @var string
     */
    protected const ROLE_CUSTOMER = 'ROLE_CUSTOMER';

    /**
     * @var string
     */
    protected const ROLE_USER = 'ROLE_USER';

    /**
     * @var string
     */
    protected const ATTRIBUTE_CUSTOMER_TRANSFER = 'CustomerTransfer';

    /**
     * @var string
     */
    protected const ATTRIBUTE_CUSTOMER_REFERENCE = 'customerReference';

    /**
     * @var string
     */
    protected const ATTRIBUTE_ID_CUSTOMER = 'idCustomer';

    /**
     * @var string
     */
    protected const CLAIM_CUSTOMER_REFERENCE = 'customer_reference';

    /**
     * @var string
     */
    protected const CLAIM_ID_CUSTOMER = 'id_customer';

    /**
     * @var string
     */
    protected const HEADER_ACCEPT = 'Accept';

    /**
     * @var string
     */
    protected const HEADER_CONTENT_TYPE = 'Content-Type';

    /**
     * @var string
     */
    protected const CONTENT_TYPE_JSON_API = 'application/vnd.api+json';

    /**
     * @var string
     */
    protected const SCOPE_CUSTOMER = 'customer';

    /**
     * @var int
     */
    protected const HTTP_STATUS_INTERNAL_SERVER_ERROR = 500;

    /**
     * @var int
     */
    protected const HTTP_STATUS_UNAUTHORIZED = 401;

    public function __construct(
        protected readonly HttpKernelInterface $httpKernel,
        protected readonly RequestStack $requestStack,
        protected readonly TokenStorageInterface $tokenStorage,
    ) {
    }

    /**
     * @param string $path
     * @param string $method
     * @param array<string, mixed> $identityClaims
     * @param array<string, mixed>|null $body
     * @param array<string, mixed> $queryParameters
     */
    public function invoke(
        string $path,
        string $method,
        array $identityClaims,
        ?array $body = null,
        array $queryParameters = [],
    ): StorefrontSubRequestResult {
        if (!$this->hasCustomerReference($identityClaims)) {
            return new StorefrontSubRequestResult(static::HTTP_STATUS_UNAUTHORIZED);
        }

        $subRequest = $this->createSubRequest($path, $method, $identityClaims, $body, $queryParameters);
        $previousToken = $this->tokenStorage->getToken();

        $this->tokenStorage->setToken($this->createCustomerToken($identityClaims));

        // `catch: true` lets the kernel's own exception subscribers convert a resource failure into
        // the regular JSON:API error response. With `catch: false` the exception is rethrown instead,
        // and the structured `errors[].detail` the tools report to the client would be lost.
        try {
            $response = $this->httpKernel->handle($subRequest, HttpKernelInterface::SUB_REQUEST, true);
        } catch (Throwable) {
            return new StorefrontSubRequestResult(static::HTTP_STATUS_INTERNAL_SERVER_ERROR);
        } finally {
            $this->tokenStorage->setToken($previousToken);
        }

        return $this->createResult($response->getStatusCode(), (string)$response->getContent());
    }

    /**
     * An empty customer reference must never reach a Storefront resource: the identity subscribers
     * would leave the request effectively anonymous while the seeded token still satisfies
     * `is_granted('ROLE_CUSTOMER')`, which would return every customer's data.
     *
     * @param array<string, mixed> $identityClaims
     */
    protected function hasCustomerReference(array $identityClaims): bool
    {
        $customerReference = $identityClaims[static::CLAIM_CUSTOMER_REFERENCE] ?? null;

        return is_string($customerReference) && trim($customerReference) !== '';
    }

    /**
     * @param array<string, mixed> $identityClaims
     * @param array<string, mixed>|null $body
     * @param array<string, mixed> $queryParameters
     */
    protected function createSubRequest(
        string $path,
        string $method,
        array $identityClaims,
        ?array $body,
        array $queryParameters,
    ): Request {
        $subRequest = Request::create(
            $path,
            strtoupper($method),
            $queryParameters,
            $this->getParentRequestCookies(),
            [],
            $this->getParentRequestServerParameters(),
            $this->encodeBody($body),
        );

        $subRequest->headers->set(static::HEADER_ACCEPT, static::CONTENT_TYPE_JSON_API);

        if ($body !== null) {
            $subRequest->headers->set(static::HEADER_CONTENT_TYPE, static::CONTENT_TYPE_JSON_API);
        }

        // Deliberately no Authorization header: identity is injected as request attributes instead,
        // which keeps the customer's shop token structurally absent from the whole call chain.
        $subRequest->attributes->set(IdentityRequestSubscriber::ATTRIBUTE_OAUTH_IDENTITY_CLAIMS, $identityClaims);

        $this->setCustomerAttributes($subRequest, $identityClaims);

        return $subRequest;
    }

    /**
     * @param array<string, mixed>|null $body
     */
    protected function encodeBody(?array $body): ?string
    {
        if ($body === null) {
            return null;
        }

        $encodedBody = json_encode($body);

        return $encodedBody === false ? null : $encodedBody;
    }

    /**
     * @param array<string, mixed> $identityClaims
     */
    protected function setCustomerAttributes(Request $subRequest, array $identityClaims): void
    {
        $customerReference = (string)$identityClaims[static::CLAIM_CUSTOMER_REFERENCE];
        $customerTransfer = (new CustomerTransfer())->setCustomerReference($customerReference);

        if (isset($identityClaims[static::CLAIM_ID_CUSTOMER])) {
            $customerTransfer->setIdCustomer((int)$identityClaims[static::CLAIM_ID_CUSTOMER]);
        }

        $subRequest->attributes->set(static::ATTRIBUTE_CUSTOMER_TRANSFER, $customerTransfer);
        $subRequest->attributes->set(static::ATTRIBUTE_CUSTOMER_REFERENCE, $customerReference);

        if ($customerTransfer->getIdCustomer() === null) {
            return;
        }

        $subRequest->attributes->set(static::ATTRIBUTE_ID_CUSTOMER, $customerTransfer->getIdCustomer());
    }

    /**
     * The Symfony firewall returns early on sub-requests, so `is_granted(...)` would evaluate against
     * an empty token storage. Seeding an authenticated token with the customer roles restores the
     * authorization decisions the Storefront resources rely on. `CUSTOMER_ACCESS` needs no explicit
     * role: its voter grants access as soon as an authenticated user is present.
     *
     * @param array<string, mixed> $identityClaims
     */
    protected function createCustomerToken(array $identityClaims): PostAuthenticationToken
    {
        $roles = [static::ROLE_CUSTOMER, static::ROLE_USER];

        $apiUser = new ApiUser(
            $this->createUserIdentifier($identityClaims),
            $roles,
        );

        return new PostAuthenticationToken($apiUser, static::FIREWALL_NAME, $roles);
    }

    /**
     * The `ApiUser` identifier is a JSON claims payload, not a bare reference: `ApiUserProvider`
     * encodes it that way and ownership voters such as `CustomerOwnershipVoter` decode it again to
     * read `customer_reference`. Passing a plain string would make every `CUSTOMER_OWNER` check
     * silently deny, which would break the customer-scoped resources the tools rely on.
     *
     * @param array<string, mixed> $identityClaims
     */
    protected function createUserIdentifier(array $identityClaims): string
    {
        $encodedIdentifier = json_encode($identityClaims);

        return $encodedIdentifier === false ? '' : $encodedIdentifier;
    }

    /**
     * @return array<string, mixed>
     */
    protected function getParentRequestServerParameters(): array
    {
        $parentRequest = $this->requestStack->getCurrentRequest();

        if ($parentRequest === null) {
            return [];
        }

        return [
            'HTTP_HOST' => $parentRequest->getHttpHost(),
            'SERVER_NAME' => $parentRequest->server->get('SERVER_NAME', $parentRequest->getHost()),
            'HTTPS' => $parentRequest->isSecure() ? 'on' : 'off',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function getParentRequestCookies(): array
    {
        $parentRequest = $this->requestStack->getCurrentRequest();

        return $parentRequest === null ? [] : $parentRequest->cookies->all();
    }

    protected function createResult(int $statusCode, string $rawBody): StorefrontSubRequestResult
    {
        $payload = json_decode($rawBody, true);

        return new StorefrontSubRequestResult(
            $statusCode,
            is_array($payload) ? $payload : [],
            $rawBody,
        );
    }
}
