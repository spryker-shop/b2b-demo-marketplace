<?php

namespace Go\Zed\GuiAssistant\Communication\Controller;

use Go\Zed\GuiAssistant\Communication\Resource\EndpointRequest;
use Spryker\Zed\Kernel\Communication\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

abstract class AbstractResourceController extends AbstractController
{
    protected const OPENAPI_HTTP_MAP = [
        Request::METHOD_GET => 'get',
        Request::METHOD_POST => 'post',
        Request::METHOD_PUT => 'put',
        Request::METHOD_DELETE => 'delete',
        Request::METHOD_PATCH => 'patch'
    ];

    abstract protected function getYmlLocation(): string;
    abstract protected function getResourcePath(): string;

    protected function getPayload(Request $request): array
    {
        if (in_array($request->getMethod(), [Request::METHOD_POST, Request::METHOD_PUT, Request::METHOD_PATCH])) {
            return $request->toArray();
        }

        return [];
    }

    protected function getOptions(Request $request): array
    {
        return $request->query->all();
    }

    protected function getPathParameters(Request $request): array
    {
        $resourcePath = $this->getResourcePath();
        $parameters = [];

        // Extract all placeholders from the resource path using regex
        if (preg_match_all('/\{([^}]+)\}/', $resourcePath, $matches)) {
            $placeholders = $matches[1]; // Get the parameter names without braces

            foreach ($placeholders as $placeholder) {
                $parameters[$placeholder] = $request->query->get($placeholder, null);
            }
        }

        return $parameters;
    }

    protected function validateResourceRequest(Request $request): void
    {
        $pathParameters = $this->getPathParameters($request);
        $resourcePath = $this->getResourcePath();

        // Split the resource path into segments
        $segments = explode('/', trim($resourcePath, '/'));
        $endpoint = '';

        foreach ($segments as $segment) {
            // If this segment is a placeholder (e.g., {abstractSku})
            if (preg_match('/\{([^}]+)\}/', $segment, $matches)) {
                $parameterName = $matches[1];

                // Only add this segment if we have the parameter value
                if (!empty($pathParameters[$parameterName])) {
                    $endpoint .= '/' . $pathParameters[$parameterName];
                } else {
                    // Stop building the path if we don't have this parameter
                    break;
                }
            } else {
                // Regular segment, always add it
                $endpoint .= '/' . $segment;
            }
        }

        // Ensure we have at least the base path
        if (empty($endpoint)) {
            $endpoint = '/';
        }

        $validator = (new \League\OpenAPIValidation\PSR7\ValidatorBuilder)->fromYamlFile($this->getYmlLocation())->getRequestValidator();
        $validator->validate(new EndpointRequest($request, $endpoint));
    }

//    private function deprecatedAction(Request $request)
//    {
//        $resource = $request->get('resource');
//        $abstractSku = null;
//        $sku = null;
//        // Only extract abstractSku and sku if the resource matches the expected pattern
//        if (preg_match('#^/products/abstracts/([^/]+)(?:/concretes(?:/([^/]+))?)?$#', $resource, $matches)) {
//            $abstractSku = $matches[1] ?? null;
//            $sku = $matches[2] ?? null;
//            if ($abstractSku && $sku) {
//                $resource = "/products/abstracts/{abstractSku}/concretes/{sku}";
//            } elseif ($abstractSku && strpos($resource, '/concretes') !== false) {
//                $resource = "/products/abstracts/{abstractSku}/concretes";
//            } elseif ($abstractSku) {
//                $resource = "/products/abstracts/{abstractSku}";
//            }
//        } else {
//            $resource = "/products/abstracts";
//        }
//
//        $httpMethod = $request->getMethod();
//        if ($httpMethod === Request::METHOD_GET) {
//            $data = $request->query->all();
//        } elseif (in_array($httpMethod, [Request::METHOD_POST, Request::METHOD_PUT, Request::METHOD_PATCH])) {
//            $data = $request->request->all();
//        } elseif ($httpMethod === Request::METHOD_DELETE) {
//            $data = $request->getContent();
//        } else {
//            $data = [];
//        }
//        unset($data['resource']);
//
//        $validator = (new \League\OpenAPIValidation\PSR7\ValidatorBuilder)->fromYamlFile(__DIR__ . '/../../chat_openapi.yaml')->getRequestValidator();
//        $validator->validate(new EndpointRequest($request));
//
//        return $this->jsonResponse(['status' => 'ok', 'data' => $resource]);
//    }
}
