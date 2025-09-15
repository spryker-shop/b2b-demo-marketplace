<?php
/**
 * PHP OpenAPI → OpenAI Responses API Agent
 * -------------------------------------------------------
 * - Parses an OpenAPI (YAML/JSON) schema
 * - Exposes each operation as an OpenAI "function" tool
 * - Lets the model discover capabilities, ask for missing params, and call endpoints
 * - Executes tool calls against your REST API and returns results
 * - Uses the Responses API with previous_response_id + function_call_output loop
 *
 * Requirements (composer):
 *   composer require guzzlehttp/guzzle:^7.9 symfony/yaml:^7.0
 *
 * Environment:
 *   putenv('OPENAI_API_KEY=sk-...'); // or pass in config
 */

declare(strict_types=1);
namespace Go\Client\OpenAi\Reader;

use Go\Client\OpenAi\Reader\ToolExecutor;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\Yaml\Yaml;

final class OpenApiAgent
{
    private Client $http;
    private Client $api;
    private string $openAiKey;
    private string $model;
    private array $opmap;
    private array $tools;
    private array $security; // e.g. ['bearer' => 'token', 'apiKeys' => ['X-API-Key' => '...']]
    private ?string $developerPrompt;
    private ?ToolExecutor $executor = null;

    public function __construct($executor)
    {
        $this->executor = $executor;
        $config = [
            'openai_api_key' => (new \Go\Client\OpenAi\OpenAiConfig())->getDefaultApiKey(),
            'model' => 'gpt-4.1-mini',
            'oas' => Yaml::parse(__DIR__.'/../../../Zed/GuiAssistant/chat_openapi.yaml'),
            'security' => [
                'bearer' => null,
                'apiKeys' => [ 'X-API-Key' => '' ],
            ],
        ];


        $this->openAiKey = $config['openai_api_key'] ?? getenv('OPENAI_API_KEY') ?: '';
        if (!$this->openAiKey) throw new InvalidArgumentException('Missing OPENAI_API_KEY');
        $this->model = $config['model'] ?? 'gpt-4.1-mini';
        $this->security = $config['security'] ?? [];
        $this->developerPrompt = $config['developer_prompt'] ?? $this->defaultDeveloperPrompt();
        $this->executor = $config['tool_executor'] ?? null;

        $oas = is_array($config['oas']) ? $config['oas'] : OpenApiToolsetBuilder::loadFile($config['oas']);
        $builder = new OpenApiToolsetBuilder($oas);
        $built = $builder->build();
        $this->tools = $built['tools'];
        $this->opmap = $built['opmap'];
        if ($this->executor && method_exists($this->executor, 'bindOperationMap')) {
            $this->executor->bindOperationMap($this->opmap);
        }

        $this->http = new Client(['http_errors' => false]);
        $this->api = new Client([
            'base_uri' => 'https://api.openai.com/v1/',
            'http_errors' => false,
            'headers' => [
                'Authorization' => 'Bearer '.$this->openAiKey,
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    /**
     * One-turn convenience: model discovers capabilities, asks for params, calls tools.
     * Returns [assistantText, stateArray]
     */
    public function converse(string $userInput, array $state = []): array
    {
        $messages = $state['messages'] ?? [];

        // Add system message if this is the first conversation
        if (empty($messages)) {
            $messages[] = [
                'role' => 'system',
                'content' => $this->developerPrompt
            ];
        }

        // Add user message
        $messages[] = [
            'role' => 'user',
            'content' => $userInput
        ];

        // 1) First call: let the model decide if it needs to call a function
        $resp = $this->openAiCreate([
            'model' => $this->model,
            'messages' => $messages,
            'tools' => $this->tools,
            'tool_choice' => 'auto',
            'store' => false,
        ]);

        $assistantMessage = $resp['choices'][0]['message'] ?? null;
        if ($assistantMessage) {
            $messages[] = $assistantMessage;
        }

        // 2) Handle function calls (can be multiple).
        $safetyCounter = 0;
        while (isset($assistantMessage['tool_calls']) && $safetyCounter++ < 8) {
            foreach ($assistantMessage['tool_calls'] as $toolCall) {
                $result = $this->executeFunctionCall([
                    'call_id' => $toolCall['id'],
                    'name' => $toolCall['function']['name'],
                    'arguments' => $toolCall['function']['arguments']
                ]);

                // Add tool result message
                $messages[] = [
                    'role' => 'tool',
                    'tool_call_id' => $toolCall['id'],
                    'content' => json_encode($result, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),
                ];
            }

            // Follow-up call with tool outputs
            $resp = $this->openAiCreate([
                'model' => $this->model,
                'messages' => $messages,
                'tools' => $this->tools,
                'tool_choice' => 'auto',
                'store' => false,
            ]);

            $assistantMessage = $resp['choices'][0]['message'] ?? null;
            if ($assistantMessage) {
                $messages[] = $assistantMessage;
            }
        }

        $assistantText = $assistantMessage['content'] ?? '[No assistant message]';
        $state['messages'] = $messages;
        return [$assistantText, $state];
    }

    /** Execute the underlying REST call */
    private function executeFunctionCall(array $call)
    {
        $name = $call['name'];
        $args = json_decode($call['arguments'] ?? '{}', true) ?: [];

        if ($this->executor) {
            // Use Symfony sub-request executor (your Option 2)
            return $this->executor->execute($name, $args);
        }

        // Fallback: original external HTTP logic (kept for completeness)
        if (!isset($this->opmap[$name])) {
            return ['error' => "Unknown tool '$name'", 'args' => $args];
        }
        $op = $this->opmap[$name];

        $method = $op['method'];
        $url = rtrim($op['baseUrl'], '/').$this->interpolatePath($op['path'], $args);

        $query = [];
        $headers = [];
        $cookies = [];
        $body = null;
        $contentType = $op['consumes'] ?? 'application/json';

        foreach (($op['argMeta'] ?? []) as $pname => $meta) {
            if (!array_key_exists($pname, $args)) continue;
            $val = $args[$pname];
            switch ($meta['in']) {
                case 'query': $query[$meta['name']] = $val; break;
                case 'header': $headers[$meta['name']] = is_scalar($val) ? (string)$val : json_encode($val); break;
                case 'cookie': $cookies[$meta['name']] = is_scalar($val) ? (string)$val : json_encode($val); break;
                case 'body': $body = $val; break;
                case 'path': /* already consumed in interpolatePath */ break;
            }
        }

        // Apply security (simple support: bearer + header api keys)
        if (!empty($this->security['bearer'])) {
            $headers['Authorization'] = 'Bearer '.$this->security['bearer'];
        }
        if (!empty($this->security['apiKeys']) && is_array($this->security['apiKeys'])) {
            foreach ($this->security['apiKeys'] as $headerName => $token) {
                $headers[$headerName] = $token;
            }
        }

        $options = ['query' => $query, 'headers' => $headers];
        if ($cookies) $options['headers']['Cookie'] = $this->buildCookieHeader($cookies);

        if ($body !== null) {
            if ($contentType === 'application/json') {
                $options['json'] = $body;
            } elseif ($contentType === 'application/x-www-form-urlencoded') {
                $options['form_params'] = $body;
            } else { // naive multipart or custom
                $options['body'] = is_string($body) ? $body : json_encode($body);
            }
            $options['headers']['Content-Type'] = $contentType;
        }

        try {
            $response = $this->http->request($method, $url, $options);
            $ct = $response->getHeaderLine('Content-Type');
            $raw = (string)$response->getBody();
            $decoded = (str_contains($ct, 'json')) ? json_decode($raw, true) : $raw;
            return [
                'status' => $response->getStatusCode(),
                'ok' => $response->getStatusCode() >= 200 && $response->getStatusCode() < 300,
                'headers' => $this->headersToAssoc($response->getHeaders()),
                'body' => $decoded ?? $raw,
            ];
        } catch (RequestException $e) {
            return [
                'status' => $e->getCode(),
                'ok' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    private function interpolatePath(string $path, array $args): string
    {
        return preg_replace_callback('/\{([^}]+)\}/', function($m) use ($args) {
            $name = $m[1];
            if (!array_key_exists($name, $args)) return $m[0];
            return rawurlencode((string)$args[$name]);
        }, $path);
    }

    private function buildCookieHeader(array $cookies): string
    {
        $parts = [];
        foreach ($cookies as $k => $v) { $parts[] = $k.'='.$v; }
        return implode('; ', $parts);
    }

    private function headersToAssoc(array $headers): array
    {
        $out = [];
        foreach ($headers as $k => $vals) { $out[$k] = implode(', ', $vals); }
        return $out;
    }

    private function extractAssistantText(array $resp): ?string
    {
        foreach (($resp['output'] ?? []) as $item) {
            if (($item['type'] ?? null) === 'message') {
                foreach (($item['content'] ?? []) as $chunk) {
                    if (($chunk['type'] ?? null) === 'output_text') return $chunk['text'] ?? null;
                }
            }
        }
        // Some SDKs expose output_text directly; try it
        return $resp['output_text'] ?? null;
    }

    /** Find all function calls emitted by the model in Responses API format. */
    private function extractFunctionCalls(array $resp): array
    {
        $calls = [];
        $usedIds = [];
        $counter = time(); // Use timestamp as base for uniqueness

        foreach (($resp['output'] ?? []) as $item) {
            if (($item['type'] ?? '') === 'function_call') {
                // Generate a unique call_id
                $callId = $item['call_id'] ?? $item['id'] ?? null;

                // Always generate a new unique ID to avoid any conflicts
                $callId = 'call_' . uniqid('', true) . '_' . (++$counter) . '_' . mt_rand(1000, 9999);

                // Ensure absolute uniqueness
                while (isset($usedIds[$callId])) {
                    $callId = 'call_' . uniqid('', true) . '_' . (++$counter) . '_' . mt_rand(1000, 9999);
                }

                $usedIds[$callId] = true;

                $calls[] = [
                    'call_id' => $callId,
                    'name' => $item['name'] ?? 'unknown',
                    'arguments' => $item['arguments'] ?? '{}',
                ];
            }
        }
        return $calls;
    }

    private function openAiCreate(array $payload): array
    {
        $res = $this->api->post('responses', ['body' => json_encode($payload)]);
        $json = json_decode((string)$res->getBody(), true);

        if (!$json || isset($json['error'])) {
            $err = $json['error']['message'] ?? 'Unknown error';
            throw new RuntimeException('OpenAI error: '.$err);
        }
        return $json;
    }

    private function defaultDeveloperPrompt(): string
    {
        return trim(<<<PROMPT
You are an API agent wired to a set of REST endpoints (exposed as function tools). Your job:
1) Briefly list what you can do based on the tool names and descriptions ("Capabilities").
2) When a user asks for something, check which tool(s) apply. Ask concise questions to collect all REQUIRED parameters and the most impactful optional ones. Avoid overwhelming the user—ask only for what is necessary to proceed.
3) When you have the required args, call the tool. If a call fails (non-2xx), explain the error plainly and ask for fixes.
4) Never invent parameters. For dates, currencies, and IDs, confirm if uncertain.
5) Prefer simple, flat JSON for arguments.
6) If multiple tools could work, choose the minimal one to satisfy the request.
7) Do not expose access tokens or secrets.
PROMPT);
    }
}
