<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Glue\McpCommerce\JsonRpc;

use Demo\Shared\McpCommerce\McpCommerceConstants;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Shapes JSON-RPC 2.0 result and error envelopes into HTTP responses.
 */
class JsonRpcResponder
{
    /**
     * @var int
     */
    public const ERROR_PARSE = -32700;

    /**
     * @var int
     */
    public const ERROR_INVALID_REQUEST = -32600;

    /**
     * @var int
     */
    public const ERROR_METHOD_NOT_FOUND = -32601;

    /**
     * @var int
     */
    public const ERROR_INVALID_PARAMS = -32602;

    /**
     * @var int
     */
    public const ERROR_INTERNAL = -32603;

    /**
     * @var string
     */
    protected const KEY_JSON_RPC = 'jsonrpc';

    /**
     * @var string
     */
    protected const KEY_ID = 'id';

    /**
     * @var string
     */
    protected const KEY_RESULT = 'result';

    /**
     * @var string
     */
    protected const KEY_ERROR = 'error';

    /**
     * @var string
     */
    protected const KEY_CODE = 'code';

    /**
     * @var string
     */
    protected const KEY_MESSAGE = 'message';

    /**
     * Specification:
     * - Returns a JSON-RPC 2.0 success envelope carrying the given result payload.
     *
     * @api
     *
     * @param array<string, mixed> $result
     */
    public function createResultResponse(array $result, string|int|null $id): JsonResponse
    {
        return new JsonResponse([
            static::KEY_JSON_RPC => McpCommerceConstants::JSON_RPC_VERSION,
            static::KEY_ID => $id,
            static::KEY_RESULT => $result,
        ]);
    }

    /**
     * Specification:
     * - Returns a JSON-RPC 2.0 error envelope with the given error code and message.
     * - Keeps the HTTP status at 200 as required by the JSON-RPC transport binding, unless overridden.
     *
     * @api
     */
    public function createErrorResponse(
        int $code,
        string $message,
        string|int|null $id = null,
        int $httpStatusCode = Response::HTTP_OK,
    ): JsonResponse {
        return new JsonResponse(
            [
                static::KEY_JSON_RPC => McpCommerceConstants::JSON_RPC_VERSION,
                static::KEY_ID => $id,
                static::KEY_ERROR => [
                    static::KEY_CODE => $code,
                    static::KEY_MESSAGE => $message,
                ],
            ],
            $httpStatusCode,
        );
    }

    /**
     * Specification:
     * - Returns an empty 202 response acknowledging a JSON-RPC notification.
     *
     * @api
     */
    public function createNotificationAcknowledgement(): Response
    {
        return new Response('', Response::HTTP_ACCEPTED);
    }
}
