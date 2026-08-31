<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Glue\McpCommerce\JsonRpc;

use Demo\Shared\McpCommerce\McpCommerceConstants;
use Symfony\Component\HttpFoundation\Request;

/**
 * Parses raw HTTP request bodies into {@see \Demo\Glue\McpCommerce\JsonRpc\JsonRpcRequest} envelopes.
 */
class JsonRpcRequestParser
{
    /**
     * @var string
     */
    protected const KEY_JSON_RPC = 'jsonrpc';

    /**
     * @var string
     */
    protected const KEY_METHOD = 'method';

    /**
     * @var string
     */
    protected const KEY_PARAMS = 'params';

    /**
     * @var string
     */
    protected const KEY_ID = 'id';

    /**
     * Specification:
     * - Parses the request body into a JSON-RPC 2.0 envelope.
     * - Returns an invalid envelope when the body is not valid JSON or violates the JSON-RPC 2.0 shape.
     *
     * @api
     */
    public function parse(Request $request): JsonRpcRequest
    {
        $payload = json_decode($request->getContent(), true);

        if (!is_array($payload)) {
            return new JsonRpcRequest();
        }

        if (($payload[static::KEY_JSON_RPC] ?? null) !== McpCommerceConstants::JSON_RPC_VERSION) {
            return new JsonRpcRequest();
        }

        $method = $payload[static::KEY_METHOD] ?? null;

        if (!is_string($method) || $method === '') {
            return new JsonRpcRequest();
        }

        return new JsonRpcRequest(
            $method,
            $this->extractParams($payload),
            $this->extractId($payload),
            true,
        );
    }

    /**
     * @param array<mixed> $payload
     *
     * @return array<string, mixed>
     */
    protected function extractParams(array $payload): array
    {
        $params = $payload[static::KEY_PARAMS] ?? [];

        return is_array($params) ? $params : [];
    }

    /**
     * @param array<mixed> $payload
     */
    protected function extractId(array $payload): string|int|null
    {
        $id = $payload[static::KEY_ID] ?? null;

        if (is_int($id) || is_string($id)) {
            return $id;
        }

        return null;
    }
}
