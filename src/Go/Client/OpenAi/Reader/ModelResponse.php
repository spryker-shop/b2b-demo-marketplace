<?php

namespace Go\Client\OpenAi\Reader;

use cebe\openapi\spec\Schema;
use Go\Client\OpenAi\Writer\SchemaUploader;
use Go\Zed\GuiAssistant\Business\GuiAssistantFacade;
use \GuzzleHttp\Client as GuzzleHttpClient;
class ModelResponse implements ModelResponseInterface
{
    public const OPEN_AI_RESULT_SIMPLE_TEXT = 'simple-text';

    protected const TEMPERATURE = 0.8;

    public function __construct(
        protected string $apiKey,
        protected string $model,
        protected int $timeout,
        protected GuzzleHttpClient $httpClient,
        protected SchemaUploader $schemaUploader
    ) {}

    public function create(array $messages, string $instructions = null, array $tools = []): array
    {
        $body = [
            'model' => $this->model,
            'instructions' => $instructions,
            'input' => $messages,
            'store' => false,
            'tool_choice' => count($tools) ? 'auto' : 'none',
        ];

        // @doc https://platform.openai.com/docs/api-reference/responses/create
        $response = $this->httpClient->post('https://api.openai.com/v1/responses', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ],
            'json' => $body,
            'timeout' => $this->timeout,
        ]);

        $result = json_decode($response->getBody()->getContents(), true);

        $result[static::OPEN_AI_RESULT_SIMPLE_TEXT] = null;
        if (isset($result['output'][0]['content'][0]['text']) && is_string($result['output'][0]['content'][0]['text'])) {
            $result[static::OPEN_AI_RESULT_SIMPLE_TEXT] = $result['output'][0]['content'][0]['text'];
        }

        return $result;
    }

    public function createForAgent(array $messages): array
    {
        $startedAt = microtime(true);
        $deadline  = $startedAt + $this->timeout - 2; // hard cap
        $vectorStoreId = $this->schemaUploader->getDetails()['vector_store_id'];

        // 1) Build the first response request
        $body = [
            'model'        => $this->model,
            'input'        => $messages,        // <-- full history provided by caller
            'tool_choice'  => 'auto',
          //  'temperature'  => static::TEMPERATURE,
            'tools'        => [
                // Let the model read the schema via File Search (vector store)
                ['type' => 'file_search',  "vector_store_ids" => [$vectorStoreId]],
                // Single tool you asked for: the model will call this as needed
                [
                    'type' => 'function',
                    'name' => 'callEndpoint',
                    'description' => 'Call an HTTP endpoint defined by the FileSearch Tool Chat OpenAPI schema. ' .
                        'Use pathParams to replace {placeholders} in uri. ' .
                        'Use queryParams for URL query string. ' .
                        'Use payload for JSON body. If payload is empty, default to GET, otherwise POST.',
                    'parameters' => [
                        'type'       => 'object',
                        'required'   => ['uri', 'httpMethod', 'schemaPath', 'pathParams', 'queryParams', 'payload'],
                        'properties' => [
                            'httpMethod' => [
                                'type' => 'string',
                                'enum' => ['GET', 'PUT', 'DELETE', 'PATCH'],
                            ],
                            'schemaPath' => [
                                'type' => 'string',
                                'description' => 'Relative URL starts with /, it must match the schema path exactly.',
                            ],
                            'pathParams' => [
                                'type' => 'object',
                                'additionalProperties' => ['type' => ['string','number','boolean']],
                                'description' => 'Map of path placeholder names to values. e.g., {"abstractSku":"sku-1"}.',
                            ],
                            'queryParams' => [
                                'type' => 'object',
                                'additionalProperties' => ['type' => ['string','number','boolean','null']],
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
            'instructions' => implode("\n", [<<<PROMPT
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
   - Use `pathParams` for placeholders.
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

PROMPT
            ]),
            'store' => true,
        ];

        // 2) Kick off the response
        $resp = $this->httpClient->post('https://api.openai.com/v1/responses', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type'  => 'application/json',
            ],
            'json'    => $body,
            'timeout' => $this->timeout,
        ]);

        $result = json_decode($resp->getBody()->getContents(), true);

        $storeResult = $result;
        // Helper to stay within time budget
        $ensureTimeLeft = function () use ($deadline) {
            if (microtime(true) >= $deadline) {
                throw new \RuntimeException('Agent timed out on budget.');
            }
        };

        $returnMessages = [];
        // 3) Tool loop: handle tool calls until the run is completed or we hit the deadline
        while (true) {
            $ensureTimeLeft();

            // Collect any tool calls from the latest result
            $toolCalls = [];
            if (!empty($result['output']) && is_array($result['output'])) {
                foreach ($result['output'] as $chunk) {
                    if (($chunk['type'] ?? null) === 'function_call') {
                        $toolCalls[] = $chunk;
                    }
                }
            }

            // If there are tool calls, fulfill them and submit outputs
            if (!empty($toolCalls)) {
                $toolOutputs = [];

                foreach ($toolCalls as $call) {
                    $ensureTimeLeft();

                    $toolName = $call['tool_name'] ?? $call['name'] ?? null;
                    $callId   = $call['call_id'] ?? null;
                    $argumentsJson = $call['arguments'] ?? '{}';
                    $args = is_string($argumentsJson) ? json_decode($argumentsJson, true) : (array)$argumentsJson;

                    if ($toolName !== 'callEndpoint') {
                        // Unknown tool => respond with an error payload so model can adjust
                        $toolOutputs[] = [
                            'tool_call_id' => $callId,
                            'output'       => json_encode(['error' => 'Unknown tool: ' . (string)$toolName]),
                        ];
                        continue;
                    }

                    // ---- Implement the callEndpoint tool ----
                    $httpMethod  = (string)($args['httpMethod']  ?? '');
                    $schemaPath  = (string)($args['schemaPath']  ?? '');
                    $pathParams  = (array) ($args['pathParams']  ?? []);
                    $queryParams = (array) ($args['queryParams'] ?? []);
                    $payload     = (array) ($args['payload']     ?? []);

                    if ($schemaPath === '') {
                        $toolOutputs[] = [
                            'tool_call_id' => $callId,
                            'output'       => json_encode(['error' => 'Missing schema path']),
                        ];
                        continue;
                    }

                    try {
                        $toolOutputs[] = [
                            'tool_call_id' => $callId,
                            'output'       => json_encode([
                                'status' => 200,
                                'data'   => $this->callEndpoint(
                                    $httpMethod,
                                    $schemaPath,
                                    $pathParams,
                                    $queryParams,
                                    $payload
                                ),
                            ])
                        ];
                    } catch (\Throwable $e) {
                        $toolOutputs[] = [
                            'tool_call_id' => $callId,
                            'output'       => json_encode(['error' => $e->getMessage()]),
                        ];
                    }
                }

                $newMessages = [];
                foreach($toolCalls as $call) {
                    $messages[] = [
                        "type" => "function_call",
                        "call_id" => $call['call_id'] ?? null,
                        "name" => $call['tool_name'] ?? $call['name'] ?? null,
                        "arguments" => $call['arguments'] ?? null,
                    ];
                    $newMessages[] = [
                        "type" => "function_call",
                        "call_id" => $call['call_id'] ?? null,
                        "name" => $call['tool_name'] ?? $call['name'] ?? null,
                        "arguments" => $call['arguments'] ?? null,
                    ];
                    $returnMessages[] ='Calling Endpoint: ' . ($call['arguments'] ?? 'unknown');
                }

                foreach($toolOutputs as $toolOutput) {
                    $messages[] = [
                        "type" => "function_call_output",
                        "call_id" => $toolOutput['tool_call_id'],
                        "output" => $toolOutput['output']
                    ];
                    $newMessages[] = [
                        "type" => "function_call_output",
                        "call_id" => $toolOutput['tool_call_id'],
                        "output" => $toolOutput['output']
                    ];
                    $returnMessages[] =  sprintf('Endpoint answered: %s' , $toolOutput['output']);
                }

                // Submit tool outputs back to OpenAI
                $ensureTimeLeft();

                $body['previous_response_id'] = $result['id'];
                $body['input'] = $newMessages;

                try {
                $submit = $this->httpClient->post('https://api.openai.com/v1/responses', [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $this->apiKey,
                        'Content-Type'  => 'application/json',
                    ],
                    'json'    => $body,
                    'timeout' => $this->timeout,
                ]);
                } catch (\Exception $e) {
                    dd($messages, $body, $e->getMessage(), $storeResult);
                }


                $result = json_decode($submit->getBody()->getContents(), true);
                // loop again: model may produce more tool calls or finalize
                continue;
            }

            // If finished, return; otherwise poll briefly for more output (streamless polling)
            $status = $result['status'] ?? null;
            $lastOutputIndex = count($result['output'] ?? []) - 1;
            if ($status === 'completed' || isset($result['output'][$lastOutputIndex]['content'][0]['text'])) {
                // mirror your simple text extractor for convenience
                $result[static::OPEN_AI_RESULT_SIMPLE_TEXT] = $returnMessages;
                if (isset($result['output'][$lastOutputIndex]['content'][0]['text']) && is_string($result['output'][$lastOutputIndex]['content'][0]['text'])) {
                    $result[static::OPEN_AI_RESULT_SIMPLE_TEXT][] = $result['output'][$lastOutputIndex]['content'][0]['text'];
                }

                return $result;
            }

            // Poll the response by refetching it (very short backoff), staying inside the 25s limit
            usleep(150000); // 150ms
            $ensureTimeLeft();

            // GET the latest state
            $poll = $this->httpClient->get('https://api.openai.com/v1/responses/' . $result['id'], [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type'  => 'application/json',
                ],
                'timeout' => $this->timeout,
            ]);
            $result = json_decode($poll->getBody()->getContents(), true);
        }
    }

    private function callEndpoint(string $httpMethod, string $schemaPath, array $pathParams, array $queryParams, array $payload): array
    {
        return (new GuiAssistantFacade())->routeEndpoint($httpMethod, $schemaPath, $pathParams, $queryParams, $payload);
    }

}
