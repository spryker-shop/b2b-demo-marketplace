<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Glue\McpCommerce\Controller;

use Demo\Client\McpCommerce\McpCommerceClientInterface;
use Demo\Glue\McpCommerce\JsonRpc\JsonRpcRequest;
use Demo\Glue\McpCommerce\JsonRpc\JsonRpcRequestParser;
use Demo\Glue\McpCommerce\JsonRpc\JsonRpcResponder;
use Demo\Glue\McpCommerce\McpCommerceConfig;
use Demo\Glue\McpCommerce\Tool\ToolRegistryInterface;
use Demo\Glue\McpCommerce\Tool\ToolResult;
use Demo\Shared\McpCommerce\McpCommerceConstants;
use Generated\Shared\Transfer\McpAccessTokenTransfer;
use Generated\Shared\Transfer\McpIdentityTransfer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Serves the MCP Streamable HTTP transport at `POST /mcp` as a JSON-RPC 2.0 endpoint.
 *
 * The endpoint emits its own `401` with a `WWW-Authenticate` header rather than delegating to the
 * Storefront API firewall entry point, which answers `403` without a discovery pointer. MCP clients
 * rely on that header to find the protected-resource metadata document and start the OAuth flow.
 *
 * This is a plain Symfony invokable controller, not a Spryker `AbstractController`, so the `*Action`
 * method-suffix convention does not apply. `__invoke` is required: the Spryker controller resolver
 * only returns a controller *object* (rather than a `[$instance, $action]` pair) for an invokable
 * service, and only an object keeps `GlueRestControllerListenerEventDispatcherPlugin` from wrapping
 * the call as a legacy Glue REST action — which would fail on the `AbstractController` argument type.
 *
 * @SuppressWarnings(PHPMD.CommunicationControllerRule)
 */
class McpController
{
    /**
     * @var string
     */
    protected const METHOD_INITIALIZE = 'initialize';

    /**
     * @var string
     */
    protected const METHOD_TOOLS_LIST = 'tools/list';

    /**
     * @var string
     */
    protected const METHOD_TOOLS_CALL = 'tools/call';

    /**
     * @var string
     */
    protected const METHOD_PING = 'ping';

    /**
     * @var string
     */
    protected const PARAM_KEY_NAME = 'name';

    /**
     * @var string
     */
    protected const PARAM_KEY_ARGUMENTS = 'arguments';

    /**
     * @var string
     */
    protected const RESULT_KEY_CONTENT = 'content';

    /**
     * @var string
     */
    protected const RESULT_KEY_STRUCTURED_CONTENT = 'structuredContent';

    /**
     * @var string
     */
    protected const RESULT_KEY_IS_ERROR = 'isError';

    /**
     * @var string
     */
    protected const RESULT_KEY_TYPE = 'type';

    /**
     * @var string
     */
    protected const CONTENT_KEY_TEXT = 'text';

    /**
     * @var string
     */
    protected const CONTENT_TYPE_TEXT = 'text';

    /**
     * @var string
     */
    protected const ERROR_MESSAGE_UNKNOWN_TOOL = 'Unknown tool "%s".';

    /**
     * @var string
     */
    protected const HEADER_AUTHORIZATION = 'Authorization';

    /**
     * @var string
     */
    protected const HEADER_WWW_AUTHENTICATE = 'WWW-Authenticate';

    /**
     * @var string
     */
    protected const BEARER_PREFIX = 'Bearer ';

    /**
     * @var string
     */
    protected const WWW_AUTHENTICATE_FORMAT = 'Bearer resource_metadata="%s"';

    /**
     * @var string
     */
    protected const ERROR_MESSAGE_UNAUTHORIZED = 'Unauthorized';

    /**
     * @var string
     */
    protected const ERROR_MESSAGE_PARSE = 'Parse error';

    /**
     * @var string
     */
    protected const ERROR_MESSAGE_METHOD_NOT_FOUND = 'Method not found';

    /**
     * @var string
     */
    protected const RESULT_KEY_PROTOCOL_VERSION = 'protocolVersion';

    /**
     * @var string
     */
    protected const RESULT_KEY_CAPABILITIES = 'capabilities';

    /**
     * @var string
     */
    protected const RESULT_KEY_SERVER_INFO = 'serverInfo';

    /**
     * @var string
     */
    protected const RESULT_KEY_TOOLS = 'tools';

    /**
     * @var string
     */
    protected const RESULT_KEY_LIST_CHANGED = 'listChanged';

    /**
     * @var string
     */
    protected const RESULT_KEY_NAME = 'name';

    /**
     * @var string
     */
    protected const RESULT_KEY_VERSION = 'version';

    public function __construct(
        protected readonly McpCommerceConfig $mcpCommerceConfig,
        protected readonly JsonRpcRequestParser $jsonRpcRequestParser,
        protected readonly JsonRpcResponder $jsonRpcResponder,
        protected readonly McpCommerceClientInterface $mcpCommerceClient,
        protected readonly ToolRegistryInterface $toolRegistry,
    ) {
    }

    /**
     * Specification:
     * - Handles a single JSON-RPC 2.0 MCP request over Streamable HTTP.
     * - Returns 404 when the MCP Commerce Server feature is disabled.
     * - Returns 401 with a `WWW-Authenticate` discovery pointer when no MCP access token is presented,
     *   or when the presented token is unknown, expired or revoked.
     *
     * @api
     */
    public function __invoke(Request $request): Response
    {
        if (!$this->mcpCommerceConfig->isEnabled()) {
            return new Response('', Response::HTTP_NOT_FOUND);
        }

        $accessTokenIdentifier = $this->findAccessTokenIdentifier($request);

        if ($accessTokenIdentifier === null) {
            return $this->createUnauthorizedResponse($request);
        }

        $mcpAccessTokenValidationResponseTransfer = $this->mcpCommerceClient->validateAccessToken(
            (new McpAccessTokenTransfer())->setIdentifier($accessTokenIdentifier),
        );

        if ($mcpAccessTokenValidationResponseTransfer->getIsValid() !== true) {
            return $this->createUnauthorizedResponse($request);
        }

        $jsonRpcRequest = $this->jsonRpcRequestParser->parse($request);

        if (!$jsonRpcRequest->isValid()) {
            return $this->jsonRpcResponder->createErrorResponse(
                JsonRpcResponder::ERROR_PARSE,
                static::ERROR_MESSAGE_PARSE,
            );
        }

        return $this->dispatch(
            $jsonRpcRequest,
            $mcpAccessTokenValidationResponseTransfer->getMcpIdentityOrFail(),
        );
    }

    protected function dispatch(
        JsonRpcRequest $jsonRpcRequest,
        McpIdentityTransfer $mcpIdentityTransfer,
    ): Response {
        if ($jsonRpcRequest->getMethod() === static::METHOD_INITIALIZE) {
            return $this->jsonRpcResponder->createResultResponse(
                $this->createInitializeResult(),
                $jsonRpcRequest->getId(),
            );
        }

        if ($jsonRpcRequest->getMethod() === static::METHOD_TOOLS_LIST) {
            return $this->jsonRpcResponder->createResultResponse(
                [static::RESULT_KEY_TOOLS => $this->toolRegistry->getToolDescriptors()],
                $jsonRpcRequest->getId(),
            );
        }

        if ($jsonRpcRequest->getMethod() === static::METHOD_TOOLS_CALL) {
            return $this->callTool($jsonRpcRequest, $mcpIdentityTransfer);
        }

        if ($jsonRpcRequest->getMethod() === static::METHOD_PING) {
            return $this->jsonRpcResponder->createResultResponse([], $jsonRpcRequest->getId());
        }

        if ($jsonRpcRequest->isNotification()) {
            return $this->jsonRpcResponder->createNotificationAcknowledgement();
        }

        return $this->jsonRpcResponder->createErrorResponse(
            JsonRpcResponder::ERROR_METHOD_NOT_FOUND,
            static::ERROR_MESSAGE_METHOD_NOT_FOUND,
            $jsonRpcRequest->getId(),
        );
    }

    /**
     * Executes a `tools/call` request and shapes the outcome as an MCP tool result.
     *
     * A tool that fails answers with a JSON-RPC *result* carrying `isError: true`, per the MCP
     * specification: the protocol call itself succeeded, only the tool did not. Tools never throw, so
     * no stack trace can surface here.
     */
    protected function callTool(
        JsonRpcRequest $jsonRpcRequest,
        McpIdentityTransfer $mcpIdentityTransfer,
    ): Response {
        $params = $jsonRpcRequest->getParams();
        $toolName = is_string($params[static::PARAM_KEY_NAME] ?? null) ? $params[static::PARAM_KEY_NAME] : '';
        $tool = $this->toolRegistry->findToolByName($toolName);

        if ($tool === null) {
            return $this->jsonRpcResponder->createErrorResponse(
                JsonRpcResponder::ERROR_INVALID_PARAMS,
                sprintf(static::ERROR_MESSAGE_UNKNOWN_TOOL, $toolName),
                $jsonRpcRequest->getId(),
            );
        }

        $arguments = $params[static::PARAM_KEY_ARGUMENTS] ?? [];
        $toolResult = $tool->execute($mcpIdentityTransfer, is_array($arguments) ? $arguments : []);

        return $this->jsonRpcResponder->createResultResponse(
            $this->createToolCallResult($toolResult),
            $jsonRpcRequest->getId(),
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected function createToolCallResult(ToolResult $toolResult): array
    {
        if (!$toolResult->isSuccessful()) {
            return [
                static::RESULT_KEY_CONTENT => [$this->createTextContent($toolResult->getMessage())],
                static::RESULT_KEY_IS_ERROR => true,
            ];
        }

        $encodedData = json_encode($toolResult->getData());

        return [
            static::RESULT_KEY_CONTENT => [
                $this->createTextContent($encodedData === false ? '' : $encodedData),
            ],
            static::RESULT_KEY_STRUCTURED_CONTENT => $toolResult->getData(),
            static::RESULT_KEY_IS_ERROR => false,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function createTextContent(string $text): array
    {
        return [
            static::RESULT_KEY_TYPE => static::CONTENT_TYPE_TEXT,
            static::CONTENT_KEY_TEXT => $text,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function createInitializeResult(): array
    {
        return [
            static::RESULT_KEY_PROTOCOL_VERSION => $this->mcpCommerceConfig->getProtocolVersion(),
            static::RESULT_KEY_CAPABILITIES => [
                static::RESULT_KEY_TOOLS => [static::RESULT_KEY_LIST_CHANGED => false],
            ],
            static::RESULT_KEY_SERVER_INFO => [
                static::RESULT_KEY_NAME => $this->mcpCommerceConfig->getServerName(),
                static::RESULT_KEY_VERSION => $this->mcpCommerceConfig->getServerVersion(),
            ],
        ];
    }

    /**
     * Returns the presented bearer credential, or null when the request carries no usable one. A
     * missing, malformed or blank `Authorization` header is indistinguishable to the caller from an
     * invalid token: both answer 401 with the same discovery pointer.
     */
    protected function findAccessTokenIdentifier(Request $request): ?string
    {
        $authorizationHeader = $request->headers->get(static::HEADER_AUTHORIZATION);

        if ($authorizationHeader === null || !str_starts_with($authorizationHeader, static::BEARER_PREFIX)) {
            return null;
        }

        $accessTokenIdentifier = trim(substr($authorizationHeader, strlen(static::BEARER_PREFIX)));

        return $accessTokenIdentifier === '' ? null : $accessTokenIdentifier;
    }

    protected function createUnauthorizedResponse(Request $request): Response
    {
        $response = $this->jsonRpcResponder->createErrorResponse(
            JsonRpcResponder::ERROR_INVALID_REQUEST,
            static::ERROR_MESSAGE_UNAUTHORIZED,
            null,
            Response::HTTP_UNAUTHORIZED,
        );

        $response->headers->set(
            static::HEADER_WWW_AUTHENTICATE,
            sprintf(static::WWW_AUTHENTICATE_FORMAT, $this->createProtectedResourceMetadataUrl($request)),
        );

        return $response;
    }

    protected function createProtectedResourceMetadataUrl(Request $request): string
    {
        return $request->getSchemeAndHttpHost() . McpCommerceConstants::PATH_OAUTH_PROTECTED_RESOURCE_METADATA;
    }
}
