<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Glue\McpCommerce\Controller;

use Demo\Glue\McpCommerce\McpCommerceConfig;
use Demo\Shared\McpCommerce\McpCommerceConstants;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Publishes the two OAuth 2.1 discovery documents an MCP client needs to bootstrap authorization
 * without any manual configuration:
 *
 * - RFC 8414 authorization server metadata at `/.well-known/oauth-authorization-server`
 * - RFC 9728 protected resource metadata at `/.well-known/oauth-protected-resource`
 *
 * Both documents are derived from the incoming request host, so the same code serves every store
 * domain without configuring absolute URLs. Both answer `404` while the feature flag is off, which
 * makes the whole MCP surface invisible rather than merely unusable.
 *
 * This is a plain Symfony invokable controller, not a Spryker `AbstractController`, so the `*Action`
 * method-suffix convention does not apply. `__invoke` is required because the Spryker controller
 * resolver only returns a controller *object* for an invokable service; a `Class::method` string
 * resolves to a callable array that the Glue REST controller listener rejects.
 *
 * @SuppressWarnings(PHPMD.CommunicationControllerRule)
 */
class MetadataController
{
    /**
     * @var string
     */
    protected const KEY_ISSUER = 'issuer';

    /**
     * @var string
     */
    protected const KEY_AUTHORIZATION_ENDPOINT = 'authorization_endpoint';

    /**
     * @var string
     */
    protected const KEY_TOKEN_ENDPOINT = 'token_endpoint';

    /**
     * @var string
     */
    protected const KEY_REGISTRATION_ENDPOINT = 'registration_endpoint';

    /**
     * @var string
     */
    protected const KEY_GRANT_TYPES_SUPPORTED = 'grant_types_supported';

    /**
     * @var string
     */
    protected const KEY_RESPONSE_TYPES_SUPPORTED = 'response_types_supported';

    /**
     * @var string
     */
    protected const KEY_CODE_CHALLENGE_METHODS_SUPPORTED = 'code_challenge_methods_supported';

    /**
     * @var string
     */
    protected const KEY_TOKEN_ENDPOINT_AUTH_METHODS_SUPPORTED = 'token_endpoint_auth_methods_supported';

    /**
     * @var string
     */
    protected const KEY_SCOPES_SUPPORTED = 'scopes_supported';

    /**
     * @var string
     */
    protected const KEY_RESOURCE = 'resource';

    /**
     * @var string
     */
    protected const KEY_RESOURCE_NAME = 'resource_name';

    /**
     * @var string
     */
    protected const KEY_AUTHORIZATION_SERVERS = 'authorization_servers';

    /**
     * @var string
     */
    protected const KEY_BEARER_METHODS_SUPPORTED = 'bearer_methods_supported';

    /**
     * @var string
     */
    protected const BEARER_METHOD_HEADER = 'header';

    /**
     * @var string
     */
    protected const HEADER_CACHE_CONTROL = 'Cache-Control';

    /**
     * @var string
     */
    protected const CACHE_CONTROL_FORMAT = 'public, max-age=%d';

    public function __construct(
        protected readonly McpCommerceConfig $mcpCommerceConfig,
    ) {
    }

    /**
     * Specification:
     * - Serves the RFC 8414 authorization server metadata document.
     * - Returns 404 when the MCP Commerce Server feature is disabled.
     * - Marks the document publicly cacheable for the configured discovery TTL.
     *
     * @api
     */
    public function __invoke(Request $request): Response
    {
        if (!$this->mcpCommerceConfig->isEnabled()) {
            return new Response('', Response::HTTP_NOT_FOUND);
        }

        if ($request->getPathInfo() === McpCommerceConstants::PATH_OAUTH_PROTECTED_RESOURCE_METADATA) {
            return $this->createCacheableResponse($this->createProtectedResourceMetadata($request));
        }

        return $this->createCacheableResponse($this->createAuthorizationServerMetadata($request));
    }

    /**
     * @return array<string, mixed>
     */
    protected function createAuthorizationServerMetadata(Request $request): array
    {
        $issuer = $request->getSchemeAndHttpHost();

        return [
            static::KEY_ISSUER => $issuer,
            static::KEY_AUTHORIZATION_ENDPOINT => $issuer . McpCommerceConstants::PATH_AUTHORIZE,
            static::KEY_TOKEN_ENDPOINT => $issuer . McpCommerceConstants::PATH_TOKEN,
            static::KEY_REGISTRATION_ENDPOINT => $issuer . McpCommerceConstants::PATH_REGISTER,
            static::KEY_GRANT_TYPES_SUPPORTED => $this->mcpCommerceConfig->getSupportedGrantTypes(),
            static::KEY_RESPONSE_TYPES_SUPPORTED => $this->mcpCommerceConfig->getSupportedResponseTypes(),
            static::KEY_CODE_CHALLENGE_METHODS_SUPPORTED => $this->mcpCommerceConfig->getSupportedCodeChallengeMethods(),
            static::KEY_TOKEN_ENDPOINT_AUTH_METHODS_SUPPORTED => $this->mcpCommerceConfig->getSupportedTokenEndpointAuthMethods(),
            static::KEY_SCOPES_SUPPORTED => $this->mcpCommerceConfig->getSupportedScopes(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function createProtectedResourceMetadata(Request $request): array
    {
        $issuer = $request->getSchemeAndHttpHost();

        return [
            static::KEY_RESOURCE => $issuer . McpCommerceConstants::PATH_MCP,
            static::KEY_RESOURCE_NAME => $this->mcpCommerceConfig->getServerName(),
            static::KEY_AUTHORIZATION_SERVERS => [$issuer],
            static::KEY_SCOPES_SUPPORTED => $this->mcpCommerceConfig->getSupportedScopes(),
            static::KEY_BEARER_METHODS_SUPPORTED => [static::BEARER_METHOD_HEADER],
        ];
    }

    /**
     * @param array<string, mixed> $metadata
     */
    protected function createCacheableResponse(array $metadata): JsonResponse
    {
        $response = new JsonResponse($metadata);

        $response->headers->set(
            static::HEADER_CACHE_CONTROL,
            sprintf(
                static::CACHE_CONTROL_FORMAT,
                $this->mcpCommerceConfig->getDiscoveryMetadataCacheTtlSeconds(),
            ),
        );

        return $response;
    }
}
