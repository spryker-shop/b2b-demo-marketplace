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
use Generated\Shared\Transfer\McpAccessTokenTransfer;
use Generated\Shared\Transfer\McpAuthorizationCodeRedemptionRequestTransfer;
use Generated\Shared\Transfer\McpAuthorizationCodeRedemptionResponseTransfer;
use Generated\Shared\Transfer\McpClientRequestTransfer;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Serves the OAuth 2.1 token endpoint at `POST /token`, exchanging a single-use authorization code
 * plus its PKCE verifier for an opaque MCP access token.
 *
 * The response carries **only** the MCP token. There is deliberately no shop access token and no
 * shop refresh token to omit: the identity behind the code is a `customer_reference` plus an
 * `id_customer`, so no shop credential exists anywhere in this code path to serialize by accident.
 *
 * Replayed codes and mismatched PKCE verifiers are both rejected in Zed — replay atomically,
 * via a conditional single-use update — and surfaced here as RFC 6749 `invalid_grant` errors with a
 * 400 status. Rejections are recorded in the security audit log without the code or the verifier.
 *
 * This is a plain Symfony invokable controller, not a Spryker `AbstractController`, so the `*Action`
 * method-suffix convention does not apply. `__invoke` is required because the Spryker controller
 * resolver only returns a controller *object* for an invokable service; a `Class::method` string
 * resolves to a callable array that the Glue REST controller listener rejects.
 *
 * @SuppressWarnings(PHPMD.CommunicationControllerRule)
 */
class TokenController
{
    /**
     * @var string
     */
    protected const PARAMETER_GRANT_TYPE = 'grant_type';

    /**
     * @var string
     */
    protected const PARAMETER_CODE = 'code';

    /**
     * @var string
     */
    protected const PARAMETER_CODE_VERIFIER = 'code_verifier';

    /**
     * @var string
     */
    protected const PARAMETER_CLIENT_ID = 'client_id';

    /**
     * @var string
     */
    protected const PARAMETER_REDIRECT_URI = 'redirect_uri';

    /**
     * @var string
     */
    protected const GRANT_TYPE_AUTHORIZATION_CODE = 'authorization_code';

    /**
     * @var string
     */
    protected const RESPONSE_KEY_ACCESS_TOKEN = 'access_token';

    /**
     * @var string
     */
    protected const RESPONSE_KEY_TOKEN_TYPE = 'token_type';

    /**
     * @var string
     */
    protected const RESPONSE_KEY_EXPIRES_IN = 'expires_in';

    /**
     * @var string
     */
    protected const RESPONSE_KEY_SCOPE = 'scope';

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
    protected const TOKEN_TYPE_BEARER = 'Bearer';

    /**
     * @var string
     */
    protected const ERROR_CODE_INVALID_REQUEST = 'invalid_request';

    /**
     * @var string
     */
    protected const ERROR_CODE_UNSUPPORTED_GRANT_TYPE = 'unsupported_grant_type';

    /**
     * @var string
     */
    protected const ERROR_CODE_INVALID_CLIENT = 'invalid_client';

    /**
     * @var string
     */
    protected const ERROR_MESSAGE_UNSUPPORTED_GRANT_TYPE = 'Only the authorization_code grant type is supported.';

    /**
     * @var string
     */
    protected const ERROR_MESSAGE_MISSING_CODE = 'A code parameter is required.';

    /**
     * @var string
     */
    protected const ERROR_MESSAGE_MISSING_CODE_VERIFIER = 'A code_verifier parameter is required.';

    /**
     * @var string
     */
    protected const ERROR_MESSAGE_MISSING_CLIENT_ID = 'A client_id parameter is required.';

    /**
     * OAuth 2.1 requires `redirect_uri` on the authorization-code grant so that the redemption is
     * bound to the same redirect URI the code was issued for. Treating it as optional would let a
     * code intercepted via one redirect URI be redeemed while claiming another.
     *
     * @var string
     */
    protected const ERROR_MESSAGE_MISSING_REDIRECT_URI = 'A redirect_uri parameter is required.';

    /**
     * @var string
     */
    protected const ERROR_MESSAGE_UNKNOWN_CLIENT = 'The client_id is not registered.';

    /**
     * @var string
     */
    protected const AUDIT_MESSAGE_TOKEN_ISSUED = 'MCP access token issued';

    /**
     * @var string
     */
    protected const AUDIT_MESSAGE_TOKEN_REQUEST_REJECTED = 'MCP access token request rejected';

    /**
     * @var string
     */
    protected const AUDIT_TAG_TOKEN_ISSUANCE = 'mcp_token_issuance';

    /**
     * @var string
     */
    protected const AUDIT_TAG_TOKEN_REJECTION = 'mcp_token_rejection';

    /**
     * @var string
     */
    protected const AUDIT_CONTEXT_KEY_CLIENT_ID = 'client_id';

    /**
     * @var string
     */
    protected const AUDIT_CONTEXT_KEY_CUSTOMER_REFERENCE = 'customer_reference';

    /**
     * @var string
     */
    protected const AUDIT_CONTEXT_KEY_ERROR_CODE = 'error_code';

    /**
     * @var string
     */
    protected const HEADER_CACHE_CONTROL = 'Cache-Control';

    /**
     * @var string
     */
    protected const CACHE_CONTROL_NO_STORE = 'no-store';

    public function __construct(
        protected readonly McpCommerceConfig $mcpCommerceConfig,
        protected readonly McpCommerceClientInterface $mcpCommerceClient,
        protected readonly McpAuthorizationAuditLoggerInterface $mcpAuthorizationAuditLogger,
    ) {
    }

    /**
     * Specification:
     * - Exchanges a single-use authorization code and its PKCE verifier for an MCP access token.
     * - Returns 404 when the MCP Commerce Server feature is disabled.
     * - Returns 200 with `access_token`, `token_type` and `expires_in` on success.
     * - Returns 400 with an OAuth error code on a replayed code or a mismatched PKCE verifier.
     * - Never returns a shop access token or a shop refresh token.
     *
     * @api
     */
    public function __invoke(Request $request): Response
    {
        if (!$this->mcpCommerceConfig->isEnabled()) {
            return new Response('', Response::HTTP_NOT_FOUND);
        }

        $tokenRequestParameters = $this->readTokenRequestParameters($request);

        $validationError = $this->validateTokenRequest($tokenRequestParameters);

        if ($validationError !== null) {
            return $this->createErrorResponse(
                $validationError[0],
                $validationError[1],
                $tokenRequestParameters[static::PARAMETER_CLIENT_ID],
            );
        }

        $mcpAuthorizationCodeRedemptionResponseTransfer = $this->mcpCommerceClient->redeemAuthorizationCode(
            $this->createRedemptionRequestTransfer($tokenRequestParameters),
        );

        if ($mcpAuthorizationCodeRedemptionResponseTransfer->getIsSuccessful() !== true) {
            return $this->createRedemptionErrorResponse(
                $mcpAuthorizationCodeRedemptionResponseTransfer,
                $tokenRequestParameters[static::PARAMETER_CLIENT_ID],
            );
        }

        return $this->createAccessTokenResponse(
            $this->mcpCommerceClient->issueAccessToken(
                $mcpAuthorizationCodeRedemptionResponseTransfer->getMcpIdentityOrFail(),
            ),
        );
    }

    /**
     * @return array<string, string>
     */
    protected function readTokenRequestParameters(Request $request): array
    {
        $parameterNames = [
            static::PARAMETER_GRANT_TYPE,
            static::PARAMETER_CODE,
            static::PARAMETER_CODE_VERIFIER,
            static::PARAMETER_CLIENT_ID,
            static::PARAMETER_REDIRECT_URI,
        ];

        $tokenRequestParameters = [];

        foreach ($parameterNames as $parameterName) {
            $tokenRequestParameters[$parameterName] = trim((string)$request->request->get($parameterName, ''));
        }

        return $tokenRequestParameters;
    }

    /**
     * @param array<string, string> $tokenRequestParameters
     *
     * @return array{0: string, 1: string}|null
     */
    protected function validateTokenRequest(array $tokenRequestParameters): ?array
    {
        if ($tokenRequestParameters[static::PARAMETER_GRANT_TYPE] !== static::GRANT_TYPE_AUTHORIZATION_CODE) {
            return [static::ERROR_CODE_UNSUPPORTED_GRANT_TYPE, static::ERROR_MESSAGE_UNSUPPORTED_GRANT_TYPE];
        }

        if ($tokenRequestParameters[static::PARAMETER_CODE] === '') {
            return [static::ERROR_CODE_INVALID_REQUEST, static::ERROR_MESSAGE_MISSING_CODE];
        }

        if ($tokenRequestParameters[static::PARAMETER_CODE_VERIFIER] === '') {
            return [static::ERROR_CODE_INVALID_REQUEST, static::ERROR_MESSAGE_MISSING_CODE_VERIFIER];
        }

        if ($tokenRequestParameters[static::PARAMETER_CLIENT_ID] === '') {
            return [static::ERROR_CODE_INVALID_REQUEST, static::ERROR_MESSAGE_MISSING_CLIENT_ID];
        }

        if ($tokenRequestParameters[static::PARAMETER_REDIRECT_URI] === '') {
            return [static::ERROR_CODE_INVALID_REQUEST, static::ERROR_MESSAGE_MISSING_REDIRECT_URI];
        }

        $mcpClientResponseTransfer = $this->mcpCommerceClient->findClient(
            (new McpClientRequestTransfer())
                ->setClientIdentifier($tokenRequestParameters[static::PARAMETER_CLIENT_ID]),
        );

        if ($mcpClientResponseTransfer->getMcpClient() === null) {
            return [static::ERROR_CODE_INVALID_CLIENT, static::ERROR_MESSAGE_UNKNOWN_CLIENT];
        }

        return null;
    }

    /**
     * @param array<string, string> $tokenRequestParameters
     */
    protected function createRedemptionRequestTransfer(
        array $tokenRequestParameters,
    ): McpAuthorizationCodeRedemptionRequestTransfer {
        $mcpAuthorizationCodeRedemptionRequestTransfer = (new McpAuthorizationCodeRedemptionRequestTransfer())
            ->setCode($tokenRequestParameters[static::PARAMETER_CODE])
            ->setCodeVerifier($tokenRequestParameters[static::PARAMETER_CODE_VERIFIER])
            ->setClientIdentifier($tokenRequestParameters[static::PARAMETER_CLIENT_ID]);

        return $mcpAuthorizationCodeRedemptionRequestTransfer
            ->setRedirectUri($tokenRequestParameters[static::PARAMETER_REDIRECT_URI]);
    }

    protected function createAccessTokenResponse(McpAccessTokenTransfer $mcpAccessTokenTransfer): JsonResponse
    {
        $this->mcpAuthorizationAuditLogger->logAuthorizationEvent(
            static::AUDIT_MESSAGE_TOKEN_ISSUED,
            [static::AUDIT_TAG_TOKEN_ISSUANCE],
            [
                static::AUDIT_CONTEXT_KEY_CLIENT_ID => $mcpAccessTokenTransfer->getClientIdentifier(),
                static::AUDIT_CONTEXT_KEY_CUSTOMER_REFERENCE => $mcpAccessTokenTransfer->getCustomerReference(),
            ],
        );

        $response = new JsonResponse([
            static::RESPONSE_KEY_ACCESS_TOKEN => $mcpAccessTokenTransfer->getIdentifier(),
            static::RESPONSE_KEY_TOKEN_TYPE => static::TOKEN_TYPE_BEARER,
            static::RESPONSE_KEY_EXPIRES_IN => $mcpAccessTokenTransfer->getExpiresIn(),
            static::RESPONSE_KEY_SCOPE => $mcpAccessTokenTransfer->getScopes(),
        ]);

        $response->headers->set(static::HEADER_CACHE_CONTROL, static::CACHE_CONTROL_NO_STORE);

        return $response;
    }

    protected function createRedemptionErrorResponse(
        McpAuthorizationCodeRedemptionResponseTransfer $mcpAuthorizationCodeRedemptionResponseTransfer,
        string $clientIdentifier,
    ): JsonResponse {
        return $this->createErrorResponse(
            (string)$mcpAuthorizationCodeRedemptionResponseTransfer->getErrorCode(),
            (string)$mcpAuthorizationCodeRedemptionResponseTransfer->getErrorMessage(),
            $clientIdentifier,
        );
    }

    protected function createErrorResponse(
        string $errorCode,
        string $errorMessage,
        string $clientIdentifier,
    ): JsonResponse {
        $this->mcpAuthorizationAuditLogger->logAuthorizationEvent(
            static::AUDIT_MESSAGE_TOKEN_REQUEST_REJECTED,
            [static::AUDIT_TAG_TOKEN_REJECTION],
            [
                static::AUDIT_CONTEXT_KEY_CLIENT_ID => $clientIdentifier,
                static::AUDIT_CONTEXT_KEY_ERROR_CODE => $errorCode,
            ],
        );

        $response = new JsonResponse(
            [
                static::RESPONSE_KEY_ERROR => $errorCode,
                static::RESPONSE_KEY_ERROR_DESCRIPTION => $errorMessage,
            ],
            Response::HTTP_BAD_REQUEST,
        );

        $response->headers->set(static::HEADER_CACHE_CONTROL, static::CACHE_CONTROL_NO_STORE);

        return $response;
    }
}
