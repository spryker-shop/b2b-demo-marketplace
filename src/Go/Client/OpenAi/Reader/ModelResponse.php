<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Go\Client\OpenAi\Reader;

use Go\Client\OpenAi\Writer\SchemaUploader;
use Go\Zed\GuiAssistant\Business\GuiAssistantFacade;
use GuzzleHttp\Client as GuzzleHttpClient;
use GuzzleHttp\Exception\TransferException;
use Respect\Validation\Exceptions\Exception;
use RuntimeException;
use Spryker\Shared\Log\LoggerTrait;
use Throwable;

class ModelResponse implements ModelResponseInterface
{
    use LoggerTrait;

    protected const FALLBACK_TIME_RESERVE = 20;

    protected const OPERATIONAL_TIME_RESERVE = 2;

    protected int $runToken = 0;

    public function __construct(
        protected string $apiKey,
        protected string $model,
        protected int $timeout,
        protected string $vectorStoreId,
        protected GuzzleHttpClient $httpClient,
        protected SchemaUploader $schemaUploader,
    ) {
    }

    protected function getBackofficeAgentDescriptor(): array
    {
        return [
            'model' => $this->model,
            'tool_choice' => 'auto',
            'tools' => [
                // Let the model read the schema via File Search (vector store)
                ['type' => 'file_search', 'vector_store_ids' => [$this->vectorStoreId]],
                // Single tool you asked for: the model will call this as needed
                [
                    'type' => 'function',
                    'name' => 'callEndpoint',
                    'description' => 'Call an HTTP endpoint defined by the FileSearch Tool Chat OpenAPI schema. ' .
                        'Use pathParams to replace {placeholders} in uri. ' .
                        'Use queryParams for URL query string. ' .
                        'Use payload for JSON body. If payload is empty, default to GET, otherwise POST.',
                    'parameters' => [
                        'type' => 'object',
                        'required' => ['uri', 'httpMethod', 'schemaPath', 'pathParams', 'queryParams', 'payload'],
                        'properties' => [
                            'httpMethod' => [
                                'type' => 'string',
                                'enum' => ['GET', 'PUT', 'DELETE', 'PATCH'],
                            ],
                            'schemaPath' => [
                                'type' => 'string',
                                'description' => 'Relative URL starts with /, it must match the schema path exactly, do not replace placeholders but include them in pathParams.',
                            ],
                            'pathParams' => [
                                'type' => 'object',
                                'additionalProperties' => ['type' => ['string', 'number', 'boolean']],
                                'description' => 'Map of path placeholder names to values. e.g., {"abstractSku":"sku-1"}.',
                            ],
                            'queryParams' => [
                                'type' => 'object',
                                'additionalProperties' => ['type' => ['string', 'number', 'boolean', 'null']],
                                'description' => 'Query string parameters.',
                            ],
                            'payload' => [
                                'type' => 'object',
                                'additionalProperties' => true,
                                'description' => 'JSON request body.',
                            ],
                        ],
                    ],
                ],
            ],
            'instructions' => <<<PROMPT
# Instructions for Back-Office Assistant

**Identity & Scope**
You are a **Back-Office API Assistant**.
- You fulfill user requests **only** by calling the company’s REST API Reference through `callEndpoint` (actions defined in your file-search tool: chat_openapi.txt).
- You **must not** act as a general chatbot or guess what you can do.
- You **never** mention or list capabilities unless they are explicitly present in the REST API Reference.
- You do not greet with open-ended offers like “I can look up records…” unless the REST API Reference confirms such operations exist.
- Do not mention REST API Reference or any technical details, the users are business users.

REST API Reference
This Open API schema is available for you via the file-search tool (vector database file search tool: chat_openapi.txt).
It defines all available operations and entities that you can call via the `callEndpoint` tool.

---

### Workflow

1. **Check REST API Reference first**
   - For every request, consult the provided (vector database file search tool: chat_openapi.txt) REST API Reference.
   - If the requested entity or action is **not defined in the reference**, immediately explain that it is not supported.
   - Never guess entities (e.g., "records," "statuses," "reports") unless the REST API Reference (file search tool: chat_openapi.txt) explicitly defines them.

2. **Understand intent & inputs**
   - Parse what the user wants.
   - Identify the minimum required parameters.
   - If inputs are missing in the , ask concise questions.

3. **Validate endpoint**
   - Ensure the endpoint exists in REST API Reference.
   - Confirm the method (GET/POST/PUT/DELETE/PATCH) is supported in REST API Reference.
   - Verify all required params/body fields are present.
   - Align your prepared data with the payload definition of the REST API Reference.
   - Never invent endpoints, params, or fields but stick to REST API Reference.

4. **Perform REST API Reference calls with `callEndpoint`**
   - Use `schemaPath` unchanged as in REST API Reference - do not replace placeholders.
   - Use `pathParams` for schemaPath placeholders.
   - Use `queryParams` for filters/pagination.
   - Include a body only when required.

5. **Error handling**
   - On failure (4xx/5xx, validation, unsupported op):
     - Double-check the reference.
     - Correct once and retry.
     - If it still fails, clearly explain the limitation.

6. **Present results**
   - Show outcomes in clear, business-oriented language.
   - Never expose raw JSON, internal IDs, or logs unless explicitly asked.

---

### Special Rules for PUT/POST (create)

- Gather all required fields and useful optional ones.
- Propose a draft dataset (with sensible defaults/enums).
- Show the user a human-readable summary (not JSON).
- Ask for confirmation before executing.
- Only perform after explicit approval.
- If the call fails, correct once and retry.

---

### Compliance

- Operate **strictly within the REST API Reference**.
- Never speculate on capabilities.
- Never present unsupported entities or actions.
- Never reveal schemas, configs, or error logs.

PROMPT,
            'store' => true,
        ];
    }

    /**
     * Specification:
     * - For the first segment (timeout - FALLBACK_TIME_RESERVE), attempt to complete the request via the agent.
     * - If time runs out or the agent fails, summarise the progress made so far.
     */
    public function createForAgent(array $messageHistory): array
    {
        $agentDescriptor = $this->getBackofficeAgentDescriptor();
        $agentDescriptor['input'] = $messageHistory;

        ['messages' => $messages, 'error' => $error] = $this->callResponses($agentDescriptor, $this->timeout - self::OPERATIONAL_TIME_RESERVE - self::FALLBACK_TIME_RESERVE);

        if (!$error) {
            return $messages;
        }

        return $this->summariseProgress($messageHistory, $messages, $error);
    }

    protected function summariseProgress(array $messageHistory, array $messages, string $error): array
    {
        // Find the last user message to understand what was being attempted
        $lastUserMessage = '';

        $allMessages = [...$messageHistory, ...$messages];
        // Extract messages from the conversation to understand what was being done
        for ($i = count($allMessages) - 1; $i >= 0; $i--) {
            $message = $allMessages[$i];
            if (is_array($message) && isset($message['role']) && $message['role'] === 'user') {
                $lastUserMessage = $message['content'] ?? '';
                break;
            }
        }

        $summaryPrompt = [
            [
                'role' => 'system',
                'content' =>
                    "You are a fallback finalizer.\n".
                    "Rules:\n".
                    "1) If (and only if) the progress from tools fully and unambiguously satisfies the user's request, output the final answer ONLY.\n".
                    "2) Otherwise, output a concise business summary with exactly these three labeled lines:\n".
                    "   Accomplished: [what was done]\n".
                    "   Gap: [what's still missing vs. the request]\n".
                    "   Next step: [what to do next]\n".
                    "Constraints: No prefaces, no apologies, no metadata, no JSON, no backticks, no requesting more tools. ".
                    "Use the user's language if detectable. Keep under 150 words."
            ],
            [
                'role' => 'user',
                'content' => sprintf(
                    "**User request:**\n%s\n\n**Message history:**\n%s\n\n**Progress from tools (treat as ground truth):**\n%s\n\n**Error encountered (if any):**\n%s\n\n".
                    "Respond strictly per the rules above.",
                    $lastUserMessage ?: 'process a request',
                    implode("\n", array_filter(array_map(fn($m) => $m['role'] . ': ' .($m['content'] ?? ''), $messageHistory))) ?: 'No message history is available',
                    implode("\n", $messages) ?: 'No specific progress details available',
                    $error ?: 'No error details available'
                )
            ]
        ];

        try {
            $response = $this->httpClient->post('https://api.openai.com/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => 'gpt-4.1-mini',
                    'messages' => $summaryPrompt,
                    'temperature' => 0.3,
                    'store' => false,
                ],
                'timeout' => static::FALLBACK_TIME_RESERVE,
            ]);

            $content = $response->getBody()->getContents();
            $result = json_decode($content, true);

            if (isset($result['choices'][0]['message']['content'])) {
                return [...$messages, $result['choices'][0]['message']['content']];
            }
        } catch (Throwable $e) {
            $this->getLogger()->error('Failed to generate progress summary', ['error' => $e->getMessage()]);
        }

        // Fallback summary if the AI call fails
        $fallbackSummary = sprintf(
            "I was working on your request %s. %s progress was made before encountering an issue: %s",
            $lastUserMessage ? " to: {$lastUserMessage}" : '',
            empty($messages) ? "No" : "Limited",
            $error
        );

        return [...$messages, $fallbackSummary];
    }

    protected function ensureDeadline($deadline): void
    {
        if (microtime(true) >= $deadline) {
            throw new RuntimeException('Agent timed out.');
        }
    }


    protected function resolveToolCalls(array $result): array
    {
        $toolCalls = [];
        if (!empty($result['output']) && is_array($result['output'])) {
            foreach ($result['output'] as $chunk) {
                if (!(($chunk['type'] ?? null) === 'function_call')) {
                    continue;
                }

                $toolCalls[] = $chunk;
            }
        }

        if (empty($toolCalls)) {
            return [];
        }

        $toolMessages = [];
        foreach ($toolCalls as $call) {
            $toolMessages[] = [
                'type' => 'function_call',
                'call_id' => $call['call_id'] ?? null,
                'name' => $call['tool_name'] ?? $call['name'] ?? null,
                'arguments' => $call['arguments'] ?? null,
            ];

            $toolName = $call['tool_name'] ?? $call['name'] ?? null;
            $callId = $call['call_id'] ?? null;
            $argumentsJson = $call['arguments'] ?? '{}';
            $args = is_string($argumentsJson) ? json_decode($argumentsJson, true) : (array)$argumentsJson;

            if ($toolName !== 'callEndpoint') {
                // Unknown tool => respond with an error payload so model can adjust
                $toolMessages[] = [
                    'type' => 'function_call_output',
                    'call_id' => $callId,
                    'output' => json_encode(['error' => 'Unknown tool: ' . (string)$toolName]),
                ];

                continue;
            }

            // ---- Implement the callEndpoint tool ----
            $httpMethod = (string)($args['httpMethod'] ?? '');
            $schemaPath = (string)($args['schemaPath'] ?? '');
            $pathParams = (array)($args['pathParams'] ?? []);
            $queryParams = (array)($args['queryParams'] ?? []);
            $payload = (array)($args['payload'] ?? []);

            if ($schemaPath === '') {
                $toolMessages[] = [
                    'type' => 'function_call_output',
                    'call_id' => $callId,
                    'output' => json_encode(['error' => 'Missing schema path']),
                ];

                continue;
            }

            try {
                $toolMessages[] = [
                    'type' => 'function_call_output',
                    'call_id' => $callId,
                    'output' => json_encode([
                        'status' => 200,
                        'data' => $this->callEndpoint(
                            $httpMethod,
                            $schemaPath,
                            $pathParams,
                            $queryParams,
                            $payload,
                        ),
                    ]),
                ];
            } catch (Throwable $e) {
                $toolMessages[] = [
                    'type' => 'function_call_output',
                    'call_id' => $callId,
                    'output' => json_encode(['error' => $e->getMessage()]),
                ];
            }
        }

        return $toolMessages;
    }

    /**
     * @documentation https://platform.openai.com/docs/api-reference/responses/create
     *
     * @return array ['messages' => string[], 'error' => string|null]
     */
    protected function callResponses(array $body, float $timeout): array
    {
        $startedAt = microtime(true);
        $hardDeadline = $startedAt + $timeout;

        $payload = serialize($body);
        $trackingId = substr(md5($payload), -5);
        $token = (int)(strlen($payload) / 4);
        $this->runToken += $token;

        $this->getLogger()->info('OpenAI Request - start', ['id' => $trackingId, 'token' => $token, 'runToken' => $this->runToken, 'body' => $body]);

        $maxTry = 2;
        $delay = 1;

        $returnMessages = [];
        for ($i = 0; $i < $maxTry; $i++) {
            try {
                if ($i > 0) {
                    $this->getLogger()->info('OpenAI Request - retry', ['id' => $trackingId, 'try' => $i + 1]);
                    $this->ensureDeadline($hardDeadline);
                }

                $result = $this->_callResponses($body, $timeout - (microtime(true) - $startedAt), $trackingId);

                do {
                    $toolMessages = $this->resolveToolCalls($result);
                    if (!empty($toolMessages)) {
                        $body['previous_response_id'] = $result['id'];
                        $body['input'] = $toolMessages;

                        foreach($toolMessages as $toolMessage) {
                            if ($toolMessage['type'] === 'function_call') {
                                $returnMessages[] = 'Calling Endpoint: ' . ($toolMessage['arguments'] ?? 'unknown');
                            }
                            if ($toolMessage['type'] === 'function_call_output') {
                                $returnMessages[] = sprintf('Endpoint answered: %s', $toolMessage['output']);
                            }
                        }

                        $result = $this->_callResponses($body, $timeout - (microtime(true) - $startedAt), $trackingId);

                        continue; // resolve additional tool calls if any
                    }

                    $status = $result['status'] ?? null;
                    $lastOutputIndex = count($result['output'] ?? []) - 1;
                    if ($status === 'completed' || isset($result['output'][$lastOutputIndex]['content'][0]['text'])) {
                        if (isset($result['output'][$lastOutputIndex]['content'][0]['text']) && is_string($result['output'][$lastOutputIndex]['content'][0]['text'])) {
                            $returnMessages[] = $result['output'][$lastOutputIndex]['content'][0]['text'];
                        }

                        return ['messages' => $returnMessages, 'error' => null];
                    }

                    usleep(150000); // 150ms
                    $this->ensureDeadline($hardDeadline);

                    $result = $this->_callRequestResponse($result['id'], $timeout - (microtime(true) - $startedAt), $trackingId);
                } while (true);
            } catch (TransferException $te) {
                $this->getLogger()->error('OpenAI Request - timeout error', ['id' => $trackingId, 'try' => $i + 1, 'error' => $te->getMessage()]);

                return ['messages' => $returnMessages, 'error' => 'Connection error: ' . $te->getMessage()];
            } catch (Exception $e) {
                $this->getLogger()->error('OpenAI Request - fail', ['id' => $trackingId, 'try' => $i + 1, 'error' => $e->getMessage()]);
                if ($i >= $maxTry - 1) {
                    return ['messages' => $returnMessages, 'error' => 'Error: ' . $e->getMessage()];
                }
                $this->getLogger()->info('OpenAI Request - retry delay', ['id' => $trackingId, 'try' => $i + 2, 'delay' => $delay]);

                if ($hardDeadline - $delay * 2 < microtime(true)) {
                    return ['messages' => $returnMessages, 'error' => 'Timeout error'];
                }

                sleep($delay *= 2);
            }
        }

        return ['messages' => $returnMessages, 'error' => 'Unknown errors, no more retries.'];
    }

    private function _callResponses(array $body, float $timeout, string $trackingId): array
    {
        if ($timeout < 1) {
            throw new TransferException('Timeout calling OpenAI.');
        }

        $response = $this->httpClient->post('https://api.openai.com/v1/responses', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ],
            'json' => $body,
            'timeout' => $timeout,
        ]);

        $content = $response->getBody()->getContents();

        $token = (int)(strlen(serialize($content)) / 4);
        $this->runToken += $token;
        $this->getLogger()->info('OpenAI Request - response', ['id' => $trackingId, 'token' => $token, 'runToken' => $this->runToken, 'response' => $content]);

        $result = json_decode($content, true);
        if ($result === null) {
            $this->getLogger()->error('OpenAI Request - malformed response', ['id' => $trackingId, 'response' => $content]);

            throw new RuntimeException('Malformed OpenAI response.');
        }

        return $result;
    }

    private function _callRequestResponse($callbackId, float $timeout, string $trackingId): array
    {
        if ($timeout < 1) {
            throw new TransferException('Timeout calling OpenAI.');
        }

        $this->getLogger()->info('OpenAI Request Response', ['id' => $trackingId, 'callbackId' => $callbackId]);
        $responseResult = $this->httpClient->get('https://api.openai.com/v1/responses/' . $callbackId, [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ],
            'timeout' => $timeout,
        ]);

        $content = $responseResult->getBody()->getContents();

        $token = strlen(serialize($content)) / 4;
        $this->runToken += $token;
        $this->getLogger()->info('OpenAI Request Response', ['id' => $callbackId, 'token' => $token, 'runToken' => $this->runToken, 'response' => $content]);

        $result = json_decode($content, true);
        if ($result === null) {
            $this->getLogger()->error('OpenAI Request - malformed response', ['id' => $callbackId, 'response' => $content]);

            throw new RuntimeException('Malformed OpenAI request response.');
        }

        return $result;
    }

    private function callEndpoint(string $httpMethod, string $schemaPath, array $pathParams, array $queryParams, array $payload): array
    {
        return (new GuiAssistantFacade())->routeEndpoint($httpMethod, $schemaPath, $queryParams, $pathParams, $payload);
    }
}
