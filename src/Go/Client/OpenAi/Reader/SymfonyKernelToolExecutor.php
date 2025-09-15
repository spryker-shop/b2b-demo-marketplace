<?php

namespace Go\Client\OpenAi\Reader;


final class SymfonyKernelToolExecutor implements ToolExecutor
{
    private array $opmap = [];

    public function __construct(
        private \Symfony\Component\HttpKernel\HttpKernelInterface $kernel,
    ) {
        $this->routeMap = [
            'GET /products/abstracts' => '/gui-assistant/product-abstracts/get',
            'GET /products/abstracts/{abstractSku}' => '/gui-assistant/product-abstracts/get',
        ];
    }

    public function bindOperationMap(array $opmap): void
    {
        $this->opmap = $opmap;
    }

    public function execute(string $toolName, array $args): array
    {
        if (!isset($this->opmap[$toolName])) {
            return ['status' => 400, 'ok' => false, 'error' => "Unknown tool '$toolName'", 'body' => null];
        }
        $op = $this->opmap[$toolName];

        $method = $op['method'] ?? 'GET';
        $pathTemplate = $this->resolveTargetPathTemplate($op['path'] ?? '', $method);

        // Interpolate {path} and copy each path param to BOTH attributes and query
        $path = preg_replace_callback('/\{([^}]+)\}/', fn($m) => rawurlencode((string)($args[$m[1]] ?? $m[0])), $pathTemplate);

        $query = [];
        $attributes = [];
        $cookies = [];
        $server = [];
        $bodyContent = null;
        $contentType = $op['consumes'] ?? 'application/json';

        foreach (($op['argMeta'] ?? []) as $pname => $meta) {
            if (!array_key_exists($pname, $args)) continue;
            $val = $args[$pname];
            switch ($meta['in']) {
                case 'path':
                    $attributes[$pname] = $val;
                    $query[$pname] = $val;
                    break;
                case 'query': $query[$pname] = $val; break;
                case 'cookie': $cookies[$meta['name']] = is_scalar($val) ? (string)$val : json_encode($val); break;
                case 'header':
                    $key = 'HTTP_' . strtoupper(str_replace('-', '_', (string)$meta['name']));
                    $server[$key] = is_scalar($val) ? (string)$val : json_encode($val);
                    break;
                case 'body':  $bodyContent = $val; break;
            }
        }

        $rawBody = null;
        if ($bodyContent !== null) {
            if ($contentType === 'application/json') {
                $rawBody = json_encode($bodyContent, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
            } elseif ($contentType === 'application/x-www-form-urlencoded') {
                $rawBody = http_build_query($bodyContent, '', '&');
            } else {
                $rawBody = is_string($bodyContent) ? $bodyContent : json_encode($bodyContent);
            }
            $server['CONTENT_TYPE'] = $contentType;
        }
        if ($cookies) {
            $server['HTTP_COOKIE'] = implode('; ', array_map(fn($k,$v)=>"$k=$v", array_keys($cookies), $cookies));
        }

        $sub = \Symfony\Component\HttpFoundation\Request::create($path, $method, $query, [], [], $server, $rawBody);
        if ($attributes) { $sub->attributes->add($attributes); }

        /** @var \Symfony\Component\HttpFoundation\Response $res */
        $res = $this->kernel->handle($sub, \Symfony\Component\HttpKernel\HttpKernelInterface::SUB_REQUEST);

        $raw = $res->getContent();
        $ct  = $res->headers->get('Content-Type') ?? '';
        $decoded = str_contains($ct, 'json') ? json_decode($raw, true) : $raw;

        return [
            'status'  => $res->getStatusCode(),
            'ok'      => $res->isSuccessful(),
            'headers' => $res->headers->all(),
            'body'    => $decoded ?? $raw,
        ];
    }

    private function resolveTargetPathTemplate(string $oasPath, string $method): string
    {
        // If you want to override paths per method, e.g. "GET /products/abstracts" => "/internal/abstracts/{abstrctSku}"
        $key = strtoupper($method).' '.rtrim(preg_replace('/\{[^}]+\}/', '', $oasPath), '/');
        return $this->routeMap[$key] ?? $oasPath;
    }
}
