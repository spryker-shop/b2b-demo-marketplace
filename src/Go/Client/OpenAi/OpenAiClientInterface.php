<?php
namespace Go\Client\OpenAi;

interface OpenAiClientInterface
{
    /**
     * @param array<int, array{
     *      role: 'system'|'user'|'assistant'|'developer',
     *      content: string
     *  }> $messages Conversation history for the OpenAI Responses API.
     *
     * @api
     *
     *
     */
    public function createResponse(array $messages, string $instructions = null, array $tools = []): array;

    public function createResponseForAgent(array $messages): array;

    /**
     * Uploads the OpenAPI schema to the vector store and returns vector store/file IDs.
     *
     * @api
     */
    public function uploadSchema(): array;
}
