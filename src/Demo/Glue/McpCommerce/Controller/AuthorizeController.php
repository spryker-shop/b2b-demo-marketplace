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
use Demo\Glue\McpCommerce\Renderer\ConsentScreenRendererInterface;
use Demo\Glue\McpCommerce\Security\McpCustomerIdentityReaderInterface;
use Generated\Shared\Transfer\McpAuthorizationCodeTransfer;
use Generated\Shared\Transfer\McpClientRequestTransfer;
use Generated\Shared\Transfer\McpClientTransfer;
use Generated\Shared\Transfer\McpIdentityTransfer;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Serves the OAuth 2.1 authorization endpoint at `GET|POST /authorize`.
 *
 * `GET` validates the authorization request and renders the login-and-consent screen. `POST` is the
 * screen's own submission: it authenticates the customer with the existing shop password flow and,
 * on approval, issues a single-use authorization code and redirects back to the client's registered
 * redirect URI carrying `code` and the untouched `state`.
 *
 * Two security properties are load-bearing here:
 * - The shop token minted while checking credentials never leaves
 *   {@link \Demo\Glue\McpCommerce\Security\McpCustomerIdentityReader}. This controller only ever sees
 *   a `customerReference` and an `idCustomer`, so there is no shop token available to leak into the
 *   redirect, the rendered page, or the audit log.
 * - `plain` PKCE is rejected outright. Only the configured S256 method is accepted, so a downgraded
 *   challenge cannot be smuggled past the code exchange.
 *
 * This is a plain Symfony invokable controller, not a Spryker `AbstractController`, so the `*Action`
 * method-suffix convention does not apply. `__invoke` is required because the Spryker controller
 * resolver only returns a controller *object* for an invokable service; a `Class::method` string
 * resolves to a callable array that the Glue REST controller listener rejects.
 *
 * @SuppressWarnings(PHPMD.CommunicationControllerRule)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class AuthorizeController
{
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
    protected const PARAMETER_RESPONSE_TYPE = 'response_type';

    /**
     * @var string
     */
    protected const PARAMETER_CODE_CHALLENGE = 'code_challenge';

    /**
     * @var string
     */
    protected const PARAMETER_CODE_CHALLENGE_METHOD = 'code_challenge_method';

    /**
     * @var string
     */
    protected const PARAMETER_STATE = 'state';

    /**
     * @var string
     */
    protected const PARAMETER_SCOPE = 'scope';

    /**
     * @var string
     */
    protected const PARAMETER_CODE = 'code';

    /**
     * @var string
     */
    protected const PARAMETER_ERROR = 'error';

    /**
     * @var string
     */
    protected const PARAMETER_ERROR_DESCRIPTION = 'error_description';

    /**
     * @var string
     */
    protected const FIELD_EMAIL = 'email';

    /**
     * @var string
     */
    protected const FIELD_PASSWORD = 'password';

    /**
     * @var string
     */
    protected const FIELD_APPROVE = 'approve';

    /**
     * @var string
     */
    protected const APPROVE_VALUE_YES = 'yes';

    /**
     * @var string
     */
    protected const RESPONSE_TYPE_CODE = 'code';

    /**
     * @var string
     */
    protected const CODE_CHALLENGE_METHOD_S256 = 'S256';

    /**
     * @var string
     */
    protected const ERROR_CODE_INVALID_REQUEST = 'invalid_request';

    /**
     * @var string
     */
    protected const ERROR_CODE_UNAUTHORIZED_CLIENT = 'unauthorized_client';

    /**
     * @var string
     */
    protected const ERROR_CODE_UNSUPPORTED_RESPONSE_TYPE = 'unsupported_response_type';

    /**
     * @var string
     */
    protected const ERROR_CODE_ACCESS_DENIED = 'access_denied';

    /**
     * @var string
     */
    protected const ERROR_MESSAGE_UNKNOWN_CLIENT = 'The client_id is not registered.';

    /**
     * @var string
     */
    protected const ERROR_MESSAGE_REDIRECT_URI_MISMATCH = 'The redirect_uri does not match the registered redirect URI.';

    /**
     * @var string
     */
    protected const ERROR_MESSAGE_MISSING_CODE_CHALLENGE = 'A PKCE code_challenge is required.';

    /**
     * @var string
     */
    protected const ERROR_MESSAGE_UNSUPPORTED_CHALLENGE_METHOD = 'Only the S256 code_challenge_method is supported.';

    /**
     * @var string
     */
    protected const ERROR_MESSAGE_UNSUPPORTED_RESPONSE_TYPE = 'Only the code response_type is supported.';

    /**
     * @var string
     */
    protected const ERROR_MESSAGE_MISSING_STATE = 'A state parameter is required.';

    /**
     * @var string
     */
    protected const ERROR_MESSAGE_INVALID_CREDENTIALS = 'The email address or password is incorrect.';

    /**
     * @var string
     */
    protected const ERROR_MESSAGE_APPROVAL_REQUIRED = 'Access must be approved to continue.';

    /**
     * @var string
     */
    protected const AUDIT_MESSAGE_CODE_ISSUED = 'MCP authorization code issued';

    /**
     * @var string
     */
    protected const AUDIT_MESSAGE_AUTHORIZATION_REJECTED = 'MCP authorization request rejected';

    /**
     * @var string
     */
    protected const AUDIT_MESSAGE_LOGIN_FAILED = 'MCP authorization login failed';

    /**
     * @var string
     */
    protected const AUDIT_TAG_AUTHORIZATION = 'mcp_authorization';

    /**
     * @var string
     */
    protected const AUDIT_TAG_FAILED_LOGIN = 'failed_login';

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

    public function __construct(
        protected readonly McpCommerceConfig $mcpCommerceConfig,
        protected readonly McpCommerceClientInterface $mcpCommerceClient,
        protected readonly McpCustomerIdentityReaderInterface $mcpCustomerIdentityReader,
        protected readonly ConsentScreenRendererInterface $consentScreenRenderer,
        protected readonly McpAuthorizationAuditLoggerInterface $mcpAuthorizationAuditLogger,
    ) {
    }

    /**
     * Specification:
     * - Validates the authorization request and renders the login and consent screen on `GET`.
     * - Authenticates the customer and issues a single-use authorization code on `POST`.
     * - Returns 404 when the MCP Commerce Server feature is disabled.
     * - Redirects OAuth errors to the validated redirect URI, and renders them otherwise.
     *
     * @api
     */
    public function __invoke(Request $request): Response
    {
        if (!$this->mcpCommerceConfig->isEnabled()) {
            return new Response('', Response::HTTP_NOT_FOUND);
        }

        $authorizationRequestParameters = $this->readAuthorizationRequestParameters($request);
        $mcpClientTransfer = $this->findValidatedClient($authorizationRequestParameters);

        if ($mcpClientTransfer === null) {
            return $this->createErrorScreenResponse(
                static::ERROR_CODE_UNAUTHORIZED_CLIENT,
                static::ERROR_MESSAGE_UNKNOWN_CLIENT,
            );
        }

        if ($authorizationRequestParameters[static::PARAMETER_REDIRECT_URI] !== $mcpClientTransfer->getRedirectUri()) {
            return $this->createErrorScreenResponse(
                static::ERROR_CODE_INVALID_REQUEST,
                static::ERROR_MESSAGE_REDIRECT_URI_MISMATCH,
            );
        }

        $validationErrorMessage = $this->validateAuthorizationRequest($authorizationRequestParameters);

        if ($validationErrorMessage !== null) {
            return $this->createErrorRedirectResponse(
                $authorizationRequestParameters,
                $this->resolveValidationErrorCode($validationErrorMessage),
                $validationErrorMessage,
            );
        }

        if (!$request->isMethod(Request::METHOD_POST)) {
            return $this->createConsentScreenResponse($mcpClientTransfer, $authorizationRequestParameters);
        }

        return $this->handleApproval($request, $mcpClientTransfer, $authorizationRequestParameters);
    }

    /**
     * @param array<string, string> $authorizationRequestParameters
     */
    protected function handleApproval(
        Request $request,
        McpClientTransfer $mcpClientTransfer,
        array $authorizationRequestParameters,
    ): Response {
        if ($request->request->get(static::FIELD_APPROVE) !== static::APPROVE_VALUE_YES) {
            return $this->createErrorRedirectResponse(
                $authorizationRequestParameters,
                static::ERROR_CODE_ACCESS_DENIED,
                static::ERROR_MESSAGE_APPROVAL_REQUIRED,
            );
        }

        $mcpIdentityTransfer = $this->mcpCustomerIdentityReader->findIdentityByCredentials(
            (string)$request->request->get(static::FIELD_EMAIL, ''),
            (string)$request->request->get(static::FIELD_PASSWORD, ''),
        );

        if ($mcpIdentityTransfer === null) {
            $this->mcpAuthorizationAuditLogger->logAuthorizationEvent(
                static::AUDIT_MESSAGE_LOGIN_FAILED,
                [static::AUDIT_TAG_AUTHORIZATION, static::AUDIT_TAG_FAILED_LOGIN],
                [
                    static::AUDIT_CONTEXT_KEY_CLIENT_ID => $authorizationRequestParameters[static::PARAMETER_CLIENT_ID],
                ],
            );

            return $this->createConsentScreenResponse(
                $mcpClientTransfer,
                $authorizationRequestParameters,
                static::ERROR_MESSAGE_INVALID_CREDENTIALS,
            );
        }

        return $this->createCodeRedirectResponse($mcpIdentityTransfer, $authorizationRequestParameters);
    }

    /**
     * @param array<string, string> $authorizationRequestParameters
     */
    protected function createCodeRedirectResponse(
        McpIdentityTransfer $mcpIdentityTransfer,
        array $authorizationRequestParameters,
    ): RedirectResponse {
        $mcpAuthorizationCodeTransfer = $this->mcpCommerceClient->issueAuthorizationCode(
            (new McpAuthorizationCodeTransfer())
                ->setClientIdentifier($authorizationRequestParameters[static::PARAMETER_CLIENT_ID])
                ->setCustomerReference($mcpIdentityTransfer->getCustomerReferenceOrFail())
                ->setIdCustomer($mcpIdentityTransfer->getIdCustomerOrFail())
                ->setCodeChallenge($authorizationRequestParameters[static::PARAMETER_CODE_CHALLENGE])
                ->setCodeChallengeMethod($authorizationRequestParameters[static::PARAMETER_CODE_CHALLENGE_METHOD])
                ->setRedirectUri($authorizationRequestParameters[static::PARAMETER_REDIRECT_URI]),
        );

        $this->mcpAuthorizationAuditLogger->logAuthorizationEvent(
            static::AUDIT_MESSAGE_CODE_ISSUED,
            [static::AUDIT_TAG_AUTHORIZATION],
            [
                static::AUDIT_CONTEXT_KEY_CLIENT_ID => $authorizationRequestParameters[static::PARAMETER_CLIENT_ID],
                static::AUDIT_CONTEXT_KEY_CUSTOMER_REFERENCE => $mcpIdentityTransfer->getCustomerReference(),
            ],
        );

        return new RedirectResponse($this->createRedirectUrl(
            $authorizationRequestParameters[static::PARAMETER_REDIRECT_URI],
            [
                static::PARAMETER_CODE => (string)$mcpAuthorizationCodeTransfer->getCode(),
                static::PARAMETER_STATE => $authorizationRequestParameters[static::PARAMETER_STATE],
            ],
        ));
    }

    /**
     * @return array<string, string>
     */
    protected function readAuthorizationRequestParameters(Request $request): array
    {
        $parameterNames = [
            static::PARAMETER_CLIENT_ID,
            static::PARAMETER_REDIRECT_URI,
            static::PARAMETER_RESPONSE_TYPE,
            static::PARAMETER_CODE_CHALLENGE,
            static::PARAMETER_CODE_CHALLENGE_METHOD,
            static::PARAMETER_STATE,
            static::PARAMETER_SCOPE,
        ];

        $authorizationRequestParameters = [];

        foreach ($parameterNames as $parameterName) {
            $authorizationRequestParameters[$parameterName] = $this->readParameter($request, $parameterName);
        }

        return $authorizationRequestParameters;
    }

    /**
     * The authorization request arrives as a query string on `GET` and as a form body on the consent
     * screen's `POST`, so both bags are read explicitly rather than through the internal
     * `Request::get()` helper. The body wins, because it is the consent screen echoing back the
     * parameters the customer just approved.
     */
    protected function readParameter(Request $request, string $parameterName): string
    {
        $bodyValue = $request->request->get($parameterName);

        if (is_string($bodyValue) && trim($bodyValue) !== '') {
            return trim($bodyValue);
        }

        $queryValue = $request->query->get($parameterName);

        return is_string($queryValue) ? trim($queryValue) : '';
    }

    /**
     * @param array<string, string> $authorizationRequestParameters
     */
    protected function findValidatedClient(array $authorizationRequestParameters): ?McpClientTransfer
    {
        $clientIdentifier = $authorizationRequestParameters[static::PARAMETER_CLIENT_ID];

        if ($clientIdentifier === '' || $authorizationRequestParameters[static::PARAMETER_REDIRECT_URI] === '') {
            return null;
        }

        return $this->mcpCommerceClient->findClient(
            (new McpClientRequestTransfer())->setClientIdentifier($clientIdentifier),
        )->getMcpClient();
    }

    /**
     * @param array<string, string> $authorizationRequestParameters
     */
    protected function validateAuthorizationRequest(array $authorizationRequestParameters): ?string
    {
        if ($authorizationRequestParameters[static::PARAMETER_RESPONSE_TYPE] !== static::RESPONSE_TYPE_CODE) {
            return static::ERROR_MESSAGE_UNSUPPORTED_RESPONSE_TYPE;
        }

        if ($authorizationRequestParameters[static::PARAMETER_CODE_CHALLENGE] === '') {
            return static::ERROR_MESSAGE_MISSING_CODE_CHALLENGE;
        }

        if ($authorizationRequestParameters[static::PARAMETER_CODE_CHALLENGE_METHOD] !== static::CODE_CHALLENGE_METHOD_S256) {
            return static::ERROR_MESSAGE_UNSUPPORTED_CHALLENGE_METHOD;
        }

        if ($authorizationRequestParameters[static::PARAMETER_STATE] === '') {
            return static::ERROR_MESSAGE_MISSING_STATE;
        }

        return null;
    }

    protected function resolveValidationErrorCode(string $validationErrorMessage): string
    {
        if ($validationErrorMessage === static::ERROR_MESSAGE_UNSUPPORTED_RESPONSE_TYPE) {
            return static::ERROR_CODE_UNSUPPORTED_RESPONSE_TYPE;
        }

        return static::ERROR_CODE_INVALID_REQUEST;
    }

    /**
     * @param array<string, string> $authorizationRequestParameters
     */
    protected function createConsentScreenResponse(
        McpClientTransfer $mcpClientTransfer,
        array $authorizationRequestParameters,
        ?string $errorMessage = null,
    ): Response {
        return new Response(
            $this->consentScreenRenderer->renderConsentScreen(
                (string)$mcpClientTransfer->getClientName(),
                $authorizationRequestParameters,
                $errorMessage,
            ),
            $errorMessage === null ? Response::HTTP_OK : Response::HTTP_UNAUTHORIZED,
        );
    }

    protected function createErrorScreenResponse(string $errorCode, string $errorMessage): Response
    {
        $this->mcpAuthorizationAuditLogger->logAuthorizationEvent(
            static::AUDIT_MESSAGE_AUTHORIZATION_REJECTED,
            [static::AUDIT_TAG_AUTHORIZATION],
            [static::AUDIT_CONTEXT_KEY_ERROR_CODE => $errorCode],
        );

        return new Response(
            $this->consentScreenRenderer->renderErrorScreen($errorMessage),
            Response::HTTP_BAD_REQUEST,
        );
    }

    /**
     * @param array<string, string> $authorizationRequestParameters
     */
    protected function createErrorRedirectResponse(
        array $authorizationRequestParameters,
        string $errorCode,
        string $errorMessage,
    ): RedirectResponse {
        $this->mcpAuthorizationAuditLogger->logAuthorizationEvent(
            static::AUDIT_MESSAGE_AUTHORIZATION_REJECTED,
            [static::AUDIT_TAG_AUTHORIZATION],
            [
                static::AUDIT_CONTEXT_KEY_CLIENT_ID => $authorizationRequestParameters[static::PARAMETER_CLIENT_ID],
                static::AUDIT_CONTEXT_KEY_ERROR_CODE => $errorCode,
            ],
        );

        return new RedirectResponse($this->createRedirectUrl(
            $authorizationRequestParameters[static::PARAMETER_REDIRECT_URI],
            [
                static::PARAMETER_ERROR => $errorCode,
                static::PARAMETER_ERROR_DESCRIPTION => $errorMessage,
                static::PARAMETER_STATE => $authorizationRequestParameters[static::PARAMETER_STATE],
            ],
        ));
    }

    /**
     * @param array<string, string> $queryParameters
     */
    protected function createRedirectUrl(string $redirectUri, array $queryParameters): string
    {
        $separator = str_contains($redirectUri, '?') ? '&' : '?';

        return $redirectUri . $separator . http_build_query($queryParameters);
    }
}
