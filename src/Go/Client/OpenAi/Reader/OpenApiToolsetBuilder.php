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

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\Yaml\Yaml;

final class OpenApiToolsetBuilder
{
    private array $oas;

    public function __construct(array $oas)
    {
        $this->oas = $oas;
    }

    /**
     * Build function tools + an operation map that knows how to call each tool.
     * @return array{tools: list<array>, opmap: array<string, array>}
     */
    public function build(): array
    {
        $tools = [];
        $opmap = [];

        $servers = $this->oas['servers'] ?? [];
        $baseUrl = $servers[0]['url'] ?? '';

        foreach (($this->oas['paths'] ?? []) as $path => $methods) {
            foreach ($methods as $http => $op) {
                if (!in_array(strtolower($http), ['get','post','put','patch','delete','options','head'])) {
                    continue;
                }

                $operation = $this->resolveRefs($op);
                $operationId = $operation['operationId'] ?? $this->deriveOperationId($http, $path);
                $name = $this->sanitizeToolName($operationId);
                $summary = $operation['summary'] ?? '';
                $description = $operation['description'] ?? '';
                $fnDescription = trim($summary.' '.preg_replace('/\s+/', ' ', $description));
                if ($fnDescription === '') {
                    $fnDescription = strtoupper($http) . " " . $path;
                }

                // Collect parameters (path/query/header/cookie)
                $params = $operation['parameters'] ?? [];
                $paramProps = [];
                $required = [];
                $argMeta = [];

                foreach ($params as $p) {
                    $p = $this->resolveRefs($p);
                    $pname = $p['name'];
                    $pin   = $p['in'];
                    $isReq = (bool)($p['required'] ?? false) || ($pin === 'path');
                    $schema = $this->resolveRefs($p['schema'] ?? ['type' => 'string']);

                    $paramProps[$pname] = $this->oasSchemaToJsonSchema($schema, $p['description'] ?? (strtoupper($pin)." param"));
                    if ($isReq) { $required[] = $pname; }

                    $argMeta[$pname] = [
                        'in' => $pin,
                        'style' => $p['style'] ?? null,
                        'explode' => $p['explode'] ?? null,
                        'name' => $pname,
                    ];
                }

                // requestBody (json + form)
                $bodySchema = null; $bodyContentType = null; $bodyName = 'body';
                if (isset($operation['requestBody'])) {
                    $rb = $this->resolveRefs($operation['requestBody']);
                    $content = $rb['content'] ?? [];
                    // Prefer application/json, then x-www-form-urlencoded, then multipart
                    foreach ([
                                 'application/json',
                                 'application/x-www-form-urlencoded',
                                 'multipart/form-data',
                             ] as $ct) {
                        if (isset($content[$ct]['schema'])) {
                            $bodySchema = $this->resolveRefs($content[$ct]['schema']);
                            $bodyContentType = $ct;
                            break;
                        }
                    }
                    if ($bodySchema) {
                        $paramProps[$bodyName] = $this->oasSchemaToJsonSchema($bodySchema, 'HTTP request body');
                        if (($rb['required'] ?? false) === true) { $required[] = $bodyName; }
                        $argMeta[$bodyName] = ['in' => 'body', 'contentType' => $bodyContentType, 'name' => $bodyName];
                    }
                }

                $parametersSchema = [
                    'type' => 'object',
                    'properties' => (object)$paramProps,
                    'required' => array_keys($paramProps), // OpenAI requires ALL properties to be in required array
                    'additionalProperties' => false,
                ];

                $tools[] = [
                    'type' => 'function',
                    'name' => $name,
                    'description' => $fnDescription,
                    'parameters' => $parametersSchema,
                    'strict' => true,
                ];

                $opmap[$name] = [
                    'method' => strtoupper($http),
                    'path' => $path,
                    'baseUrl' => $baseUrl,
                    'argMeta' => $argMeta,
                    'consumes' => $bodyContentType,
                    'produces' => $this->detectProduces($operation),
                ];
            }
        }

        return ['tools' => $tools, 'opmap' => $opmap];
    }

    private function deriveOperationId(string $http, string $path): string
    {
        $slug = preg_replace('/[^a-zA-Z0-9]+/', '_', trim($path, '/'));
        return strtolower($http.'_'.$slug ?: 'root');
    }

    private function sanitizeToolName(string $name): string
    {
        $name = preg_replace('/[^a-zA-Z0-9_]/', '_', $name);
        if (preg_match('/^[0-9]/', $name)) $name = 'op_'.$name;
        return substr($name, 0, 64);
    }

    private function detectProduces(array $operation): string
    {
        $responses = $operation['responses'] ?? [];
        foreach (['200','201','202','204','default'] as $code) {
            if (isset($responses[$code]['content'])) {
                $content = $responses[$code]['content'];
                foreach (['application/json','text/plain'] as $ct) {
                    if (isset($content[$ct])) return $ct;
                }
                // fallback: first key
                $keys = array_keys($content);
                if ($keys) return $keys[0];
            }
        }
        return 'application/json';
    }

    private function oasSchemaToJsonSchema(array $schema, ?string $desc): array
    {
        $out = $schema;
        unset($out['nullable']); // not part of JSON Schema used by OpenAI
        if ($desc && !isset($out['description'])) $out['description'] = $desc;
        if (!isset($out['type'])) $out['type'] = 'object';

        // Remove OpenAPI features not supported by OpenAI strict mode
        unset($out['propertyNames']);
        unset($out['patternProperties']);

        // OpenAI strict mode requires additionalProperties: false for all objects
        if (($out['type'] ?? 'object') === 'object' && !isset($out['additionalProperties'])) {
            $out['additionalProperties'] = false;
        }

        // Recursively apply to nested objects first
        if (isset($out['properties']) && is_array($out['properties'])) {
            foreach ($out['properties'] as $key => $prop) {
                if (is_array($prop)) {
                    $out['properties'][$key] = $this->oasSchemaToJsonSchema($prop, null);
                }
            }
        }

        // Handle array items
        if (isset($out['items']) && is_array($out['items'])) {
            $out['items'] = $this->oasSchemaToJsonSchema($out['items'], null);
        }

        // Handle oneOf, anyOf, allOf
        foreach (['oneOf', 'anyOf', 'allOf'] as $combiner) {
            if (isset($out[$combiner]) && is_array($out[$combiner])) {
                foreach ($out[$combiner] as $index => $subSchema) {
                    if (is_array($subSchema)) {
                        $out[$combiner][$index] = $this->oasSchemaToJsonSchema($subSchema, null);
                    }
                }
            }
        }

        // After processing nested schemas, handle required arrays
        // Check if any properties became pure additionalProperties objects (no explicit properties)
        if (isset($out['properties']) && is_array($out['properties'])) {
            $validProperties = [];
            foreach ($out['properties'] as $key => $prop) {
                // If a property is an object with additionalProperties but no explicit properties,
                // it should not be in the required array for OpenAI strict mode
                if (is_array($prop) &&
                    ($prop['type'] ?? 'object') === 'object' &&
                    isset($prop['additionalProperties']) &&
                    !isset($prop['properties'])) {
                    // This is a pure additionalProperties object, don't include in required
                    continue;
                }
                $validProperties[] = $key;
            }
            $out['required'] = $validProperties;
        } elseif (($out['type'] ?? 'object') === 'object' && isset($out['additionalProperties']) && !isset($out['properties'])) {
            // For objects with additionalProperties but no explicit properties, remove required array
            unset($out['required']);
        }

        return $out;
    }

    private function resolveRefs($node)
    {
        if (!is_array($node)) return $node;
        if (isset($node['$ref'])) {
            $ref = $node['$ref'];
            if (str_starts_with($ref, '#/')) {
                $parts = explode('/', substr($ref, 2));
                $cursor = $this->oas;
                foreach ($parts as $p) {
                    if (!is_array($cursor) || !array_key_exists($p, $cursor)) break;
                    $cursor = $cursor[$p];
                }
                if (is_array($cursor)) return $this->resolveRefs($cursor);
            }
        }
        // Deep resolve
        $resolved = [];
        foreach ($node as $k => $v) {
            $resolved[$k] = is_array($v) ? $this->resolveRefs($v) : $v;
        }
        return $resolved;
    }

    public static function loadFile(string $path): array
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $raw = file_get_contents($path);
        if ($raw === false) throw new RuntimeException("Cannot read OAS file: $path");
        if (in_array($ext, ['yaml','yml'])) return Yaml::parse($raw);
        $json = json_decode($raw, true);
        if ($json === null) throw new RuntimeException('Invalid JSON in OAS file.');
        return $json;
    }
}
