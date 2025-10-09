<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Go\Client\OpenAi;

interface OpenAiClientInterface
{
    /**
     * @api
     *
     * @param array<int, array{
     *      role: 'system'|'user'|'assistant'|'developer',
     *      content: string
     *  }> $messages Conversation history for the OpenAI Responses API.
     */
    public function createResponse(array $messages, ?string $instructions = null, array $tools = []): array;

    public function createResponseForAgent(array $messages): array;

    public function deleteVectorStoreFiles(): void;

    /**
     * Uploads the OpenAPI schema to the vector store and returns vector store/file IDs.
     *
     * @api
     */
    public function uploadSchema(): array;

    public function listVectorStoreFiles(): array;
}
