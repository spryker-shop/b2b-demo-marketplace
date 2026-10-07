<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Glue\McpCommerce\Controller;

use Demo\Client\McpCommerce\McpCommerceClientInterface;
use Demo\Glue\McpCommerce\Logger\McpAuthorizationAuditLoggerInterface;
use Demo\Glue\McpCommerce\McpCommerceConfig;
use Generated\Shared\Transfer\McpClientRegistrationRequestTransfer;
use Generated\Shared\Transfer\McpClientRegistrationResponseTransfer;
use Generated\Shared\Transfer\McpClientTransfer;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Implements RFC 7591 Dynamic Client Registration at `POST /register`, so an MCP client can obtain a
 * `client_id` on first use without an administrator pre-provisioning credentials for it.
 *
 * Every client is registered as a **public** client: no client secret is generated or stored, and
 * PKCE is therefore mandatory for the authorization-code exchange. Redirect URIs are checked against
 * the configured allow-list before anything is persisted, so a rejected registration leaves no
 * client record behind.
 *
 * This is a plain Symfony invokable controller, not a Spryker `AbstractController`, so the `*Action`
 * method-suffix convention does not apply. `__invoke` is required because the Spryker controller
 * resolver only returns a controller *object* for an invokable service; a `Class::method` string
 * resolves to a callable array that the Glue REST controller listener rejects.
 *
 * @SuppressWarnings(PHPMD.CommunicationControllerRule)
 */
class RegistrationController
{
    /**
     * @var string
     */
    protected const REQUEST_KEY_REDIRECT_URIS = 'redirect_uris';

    /**
     * @var string
     */
    protected const REQUEST_KEY_CLIENT_NAME = 'client_name';

    /**
     * @var string
     */
    protected const RESPONSE_KEY_CLIENT_ID = 'client_id';

    /**
     * @var string
     */
    protected const RESPONSE_KEY_CLIENT_NAME = 'client_name';

    /**
     * @var string
     */
    protected const RESPONSE_KEY_REDIRECT_URIS = 'redirect_uris';

    /**
     * @var string
     */
    protected const RESPONSE_KEY_GRANT_TYPES = 'grant_types';

    /**
     * @var string
     */
    protected const RESPONSE_KEY_RESPONSE_TYPES = 'response_types';

    /**
     * @var string
     */
    protected const RESPONSE_KEY_TOKEN_ENDPOINT_AUTH_METHOD = 'token_endpoint_auth_method';

    /**
     * @var string
     */
    protected const RESPONSE_KEY_CODE_CHALLENGE_METHOD = 'code_challenge_method';

    /**
     * @var string
     */
    protected const RESPONSE_KEY_CLIENT_ID_ISSUED_AT = 'client_id_issued_at';

    /**
     * @var string
     */
    protected const RESPONSE_KEY_ERROR = 'error';

    /**
     * @var string
     */
    protected const RESPONSE_KEY_ERROR_DESCRIPTION = 'error_description';

    /**
     * @var string
     */
    protected const RESPONSE_KEY_INVALID_FIELD = 'invalid_field';

    /**
     * @var string
     */
    protected const TOKEN_ENDPOINT_AUTH_METHOD_NONE = 'none';

    /**
     * @var string
     */
    protected const CODE_CHALLENGE_METHOD_S256 = 'S256';

    /**
     * @var string
     */
    protected const ERROR_CODE_INVALID_CLIENT_METADATA = 'invalid_client_metadata';

    /**
     * @var string
     */
    protected const ERROR_MESSAGE_MALFORMED_BODY = 'The registration request body must be a JSON object.';

    /**
     * @var string
     */
    protected const AUDIT_MESSAGE_CLIENT_REGISTERED = 'MCP client registered';

    /**
     * @var string
     */
    protected const AUDIT_MESSAGE_CLIENT_REGISTRATION_REJECTED = 'MCP client registration rejected';

    /**
     * @var string
     */
    protected const AUDIT_TAG_CLIENT_REGISTRATION = 'mcp_client_registration';

    /**
     * @var string
     */
    protected const AUDIT_CONTEXT_KEY_CLIENT_ID = 'client_id';

    /**
     * @var string
     */
    protected const AUDIT_CONTEXT_KEY_ERROR_CODE = 'error_code';

    public function __construct(
        protected readonly McpCommerceConfig $mcpCommerceConfig,
        protected readonly McpCommerceClientInterface $mcpCommerceClient,
        protected readonly McpAuthorizationAuditLoggerInterface $mcpAuthorizationAuditLogger,
    ) {
    }

    /**
     * Specification:
     * - Registers an MCP client through RFC 7591 Dynamic Client Registration.
     * - Returns 404 when the MCP Commerce Server feature is disabled.
     * - Returns 201 with the generated `client_id` on success.
     * - Returns 400 naming the invalid field when the redirect URIs are missing or not allow-listed.
     *
     * @api
     */
    public function __invoke(Request $request): Response
    {
        if (!$this->mcpCommerceConfig->isEnabled()) {
            return new Response('', Response::HTTP_NOT_FOUND);
        }

        $requestPayload = $this->decodeRequestPayload($request);

        if ($requestPayload === null) {
            return $this->createErrorResponse(
                static::ERROR_CODE_INVALID_CLIENT_METADATA,
                static::ERROR_MESSAGE_MALFORMED_BODY,
                null,
            );
        }

        $mcpClientRegistrationResponseTransfer = $this->mcpCommerceClient->registerClient(
            $this->createRegistrationRequestTransfer($requestPayload),
        );

        if ($mcpClientRegistrationResponseTransfer->getIsSuccessful() !== true) {
            return $this->createRejectionResponse($mcpClientRegistrationResponseTransfer);
        }

        return $this->createRegistrationResponse($mcpClientRegistrationResponseTransfer->getMcpClientOrFail());
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function decodeRequestPayload(Request $request): ?array
    {
        $rawContent = trim((string)$request->getContent());

        if ($rawContent === '') {
            return [];
        }

        $decodedPayload = json_decode($rawContent, true);

        return is_array($decodedPayload) ? $decodedPayload : null;
    }

    /**
     * @param array<string, mixed> $requestPayload
     */
    protected function createRegistrationRequestTransfer(
        array $requestPayload,
    ): McpClientRegistrationRequestTransfer {
        $redirectUris = $requestPayload[static::REQUEST_KEY_REDIRECT_URIS] ?? [];

        return (new McpClientRegistrationRequestTransfer())
            ->setClientName((string)($requestPayload[static::REQUEST_KEY_CLIENT_NAME] ?? ''))
            ->setRedirectUris(is_array($redirectUris) ? array_values($redirectUris) : []);
    }

    protected function createRegistrationResponse(McpClientTransfer $mcpClientTransfer): JsonResponse
    {
        $this->mcpAuthorizationAuditLogger->logAuthorizationEvent(
            static::AUDIT_MESSAGE_CLIENT_REGISTERED,
            [static::AUDIT_TAG_CLIENT_REGISTRATION],
            [static::AUDIT_CONTEXT_KEY_CLIENT_ID => $mcpClientTransfer->getIdentifier()],
        );

        return new JsonResponse(
            [
                static::RESPONSE_KEY_CLIENT_ID => $mcpClientTransfer->getIdentifier(),
                static::RESPONSE_KEY_CLIENT_NAME => $mcpClientTransfer->getClientName(),
                static::RESPONSE_KEY_REDIRECT_URIS => [$mcpClientTransfer->getRedirectUri()],
                static::RESPONSE_KEY_GRANT_TYPES => $this->mcpCommerceConfig->getSupportedGrantTypes(),
                static::RESPONSE_KEY_RESPONSE_TYPES => $this->mcpCommerceConfig->getSupportedResponseTypes(),
                static::RESPONSE_KEY_TOKEN_ENDPOINT_AUTH_METHOD => static::TOKEN_ENDPOINT_AUTH_METHOD_NONE,
                static::RESPONSE_KEY_CODE_CHALLENGE_METHOD => static::CODE_CHALLENGE_METHOD_S256,
                static::RESPONSE_KEY_CLIENT_ID_ISSUED_AT => time(),
            ],
            Response::HTTP_CREATED,
        );
    }

    protected function createRejectionResponse(
        McpClientRegistrationResponseTransfer $mcpClientRegistrationResponseTransfer,
    ): JsonResponse {
        return $this->createErrorResponse(
            (string)$mcpClientRegistrationResponseTransfer->getErrorCode(),
            (string)$mcpClientRegistrationResponseTransfer->getErrorMessage(),
            $mcpClientRegistrationResponseTransfer->getInvalidField(),
        );
    }

    protected function createErrorResponse(
        string $errorCode,
        string $errorMessage,
        ?string $invalidField,
    ): JsonResponse {
        $this->mcpAuthorizationAuditLogger->logAuthorizationEvent(
            static::AUDIT_MESSAGE_CLIENT_REGISTRATION_REJECTED,
            [static::AUDIT_TAG_CLIENT_REGISTRATION],
            [static::AUDIT_CONTEXT_KEY_ERROR_CODE => $errorCode],
        );

        $errorPayload = [
            static::RESPONSE_KEY_ERROR => $errorCode,
            static::RESPONSE_KEY_ERROR_DESCRIPTION => $errorMessage,
        ];

        if ($invalidField !== null) {
            $errorPayload[static::RESPONSE_KEY_INVALID_FIELD] = $invalidField;
        }

        return new JsonResponse($errorPayload, Response::HTTP_BAD_REQUEST);
    }
}
