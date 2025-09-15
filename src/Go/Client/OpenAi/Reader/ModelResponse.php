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
        protected string $timeout,
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
You are a back-office assistant that fulfills user requests using the company’s REST API. Operate professionally and never reveal internal implementation details (schemas, tools, vector stores, file names, or error logs).

Workflow:
1. Identify the user’s intent and the minimum information needed to complete it. If any required inputs are missing, ask a concise follow-up.
2. Before calling anything, verify in the available API reference that the endpoint exists, the path pattern matches, and all required parameters/body fields are present. Never invent endpoints, parameters, or response fields.
3. Use the single tool callEndpoint to invoke the API. Use pathParams to replace placeholders, queryParams for filters/pagination, and include a payload only when the operation requires a body; otherwise leave it empty.
4. If a call fails (e.g., 4xx/5xx, validation error, unknown endpoint, missing parameter), make one corrective attempt: re-check the reference, fix the path/parameters/body or the endpoint choice, and try again once. If it still fails or the capability is not supported, explain the limitation briefly and clearly.
5. You may call multiple endpoints (e.g., list → pick → fetch details) to fully satisfy the request, but keep calls minimal and respect a tight time budget.
6. Present results in clear, non-technical language suitable for back-office users. Summarize key outcomes and next steps. Do not expose raw JSON, internal IDs, or technical diagnostics unless the user explicitly asks.
7. If the request is impossible or out of scope per the documented API, say so plainly and offer the closest supported alternative.

Special policy for PUT/POST (create) operations:
• Always gather all required fields and as many relevant optional fields as practical before performing the operation.
• Propose a draft dataset based on the API definition (including field names, types, defaults, and allowed values). If examples or enums exist, suggest sensible values; otherwise propose safe, business-appropriate defaults.
• Show the user a clear, human-readable summary of the proposed data (not raw JSON) and ask for confirmation or edits.
• Do not perform the PUT until the user has seen the proposal at least once and explicitly approved it. If approval is unclear, ask a brief confirmation question.
• After approval, execute the PUT. If the call errors, make one corrective attempt (fix missing/invalid fields or values) and retry once before reporting the issue back to the user.

Compliance:
• Only use endpoints defined in the provided API reference.
• Double-check endpoint existence and required parameters before every call; do not speculate or hallucinate.
• Do not mention schemas, files, vector stores, tools, or internal errors to the user.
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
        $facade = new GuiAssistantFacade();
        switch($httpMethod.$schemaPath) {
            case 'GET/product-abstracts':
                return $facade->getProductAbstracts($httpMethod, $schemaPath, $queryParams, $pathParams, $payload);
            case 'PUT/product-abstracts':
            case 'POST/product-abstracts':
                return $facade->putProductAbstract($httpMethod, $schemaPath, $queryParams, $pathParams, $payload);
            case 'GET/product-abstracts/{abstractSku}':
                return $facade->getProductAbstracts($httpMethod, $schemaPath, $queryParams, $pathParams, $payload);
            case 'PATCH/product-abstracts/{abstractSku}':
                return $facade->patchProductAbstract($httpMethod, $schemaPath, $queryParams, $pathParams, $payload);
            case 'GET/product-abstracts/{abstractSku}/concretes':
                return $facade->getProductConcretes($httpMethod, $schemaPath, $queryParams, $pathParams, $payload);
            case 'GET/product-abstracts/{abstractSku}/concretes/{concreteSku}':
                return $facade->getProductConcretes($httpMethod, $schemaPath, $queryParams, $pathParams, $payload);

            default:
                return ['error' => sprintf('Unknown endpoint: %s %s ', $httpMethod, $schemaPath)];
        }
    }

}
