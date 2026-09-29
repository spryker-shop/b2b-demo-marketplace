<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace DemoTest\Glue\McpCommerce;

use Codeception\Test\Unit;
use Demo\Glue\McpCommerce\JsonRpc\JsonRpcRequestParser;
use Demo\Glue\McpCommerce\JsonRpc\JsonRpcResponder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @group DemoTest
 * @group Glue
 * @group McpCommerce
 * @group JsonRpcEnvelopeTest
 */
class JsonRpcEnvelopeTest extends Unit
{
    protected McpCommerceGlueTester $tester;

    public function testParseReturnsValidEnvelopeForWellFormedJsonRpcRequest(): void
    {
        // Arrange
        $request = $this->createJsonRequest('{"jsonrpc":"2.0","id":7,"method":"tools/list","params":{"cursor":"abc"}}');

        // Act
        $jsonRpcRequest = (new JsonRpcRequestParser())->parse($request);

        // Assert
        $this->assertTrue($jsonRpcRequest->isValid());
        $this->assertSame('tools/list', $jsonRpcRequest->getMethod());
        $this->assertSame(7, $jsonRpcRequest->getId());
        $this->assertSame(['cursor' => 'abc'], $jsonRpcRequest->getParams());
        $this->assertFalse($jsonRpcRequest->isNotification());
    }

    public function testParseRejectsBodyThatIsNotJson(): void
    {
        // Arrange
        $request = $this->createJsonRequest('not json at all');

        // Act
        $jsonRpcRequest = (new JsonRpcRequestParser())->parse($request);

        // Assert
        $this->assertFalse($jsonRpcRequest->isValid());
    }

    public function testParseRejectsUnsupportedJsonRpcVersion(): void
    {
        // Arrange
        $request = $this->createJsonRequest('{"jsonrpc":"1.0","id":1,"method":"initialize"}');

        // Act
        $jsonRpcRequest = (new JsonRpcRequestParser())->parse($request);

        // Assert
        $this->assertFalse($jsonRpcRequest->isValid());
    }

    public function testParseRejectsMissingMethod(): void
    {
        // Arrange
        $request = $this->createJsonRequest('{"jsonrpc":"2.0","id":1}');

        // Act
        $jsonRpcRequest = (new JsonRpcRequestParser())->parse($request);

        // Assert
        $this->assertFalse($jsonRpcRequest->isValid());
    }

    public function testParseTreatsRequestWithoutIdAsNotification(): void
    {
        // Arrange
        $request = $this->createJsonRequest('{"jsonrpc":"2.0","method":"notifications/initialized"}');

        // Act
        $jsonRpcRequest = (new JsonRpcRequestParser())->parse($request);

        // Assert
        $this->assertTrue($jsonRpcRequest->isValid());
        $this->assertTrue($jsonRpcRequest->isNotification());
    }

    public function testCreateResultResponseWrapsPayloadInJsonRpcEnvelope(): void
    {
        // Act
        $response = (new JsonRpcResponder())->createResultResponse(['tools' => []], 4);

        // Assert
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame(
            ['jsonrpc' => '2.0', 'id' => 4, 'result' => ['tools' => []]],
            json_decode((string)$response->getContent(), true),
        );
    }

    public function testCreateErrorResponseCarriesCodeMessageAndHttpStatus(): void
    {
        // Act
        $response = (new JsonRpcResponder())->createErrorResponse(
            JsonRpcResponder::ERROR_INVALID_REQUEST,
            'Unauthorized',
            null,
            Response::HTTP_UNAUTHORIZED,
        );

        // Assert
        $this->assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        $this->assertSame(
            [
                'jsonrpc' => '2.0',
                'id' => null,
                'error' => ['code' => JsonRpcResponder::ERROR_INVALID_REQUEST, 'message' => 'Unauthorized'],
            ],
            json_decode((string)$response->getContent(), true),
        );
    }

    protected function createJsonRequest(string $content): Request
    {
        return Request::create('/mcp', 'POST', [], [], [], [], $content);
    }
}
