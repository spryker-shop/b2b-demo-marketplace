<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Go\Zed\GuiAssistant\Business;

interface GuiAssistantFacadeInterface
{
    /**
     * Get product abstracts collection or single item based on resource path and parameters
     *
     * @param string $httpMethod
     * @param string $resourcePath
     * @param array $queryParams
     * @param array $pathParams
     * @param array $payload
     *
     * @return array
     */
    public function getProductAbstracts(string $httpMethod, string $resourcePath, array $queryParams, array $pathParams, array $payload): array;

    /**
     * Update existing product abstract
     *
     * @param string $httpMethod
     * @param string $resourcePath
     * @param array $queryParams
     * @param array $pathParams
     * @param array $payload
     *
     * @return array
     */
    public function patchProductAbstract(string $httpMethod, string $resourcePath, array $queryParams, array $pathParams, array $payload): array;

    /**
     * Create new product abstract with concretes
     *
     * @param string $httpMethod
     * @param string $resourcePath
     * @param array $queryParams
     * @param array $pathParams
     * @param array $payload
     *
     * @return array
     */
    public function putProductAbstract(string $httpMethod, string $resourcePath, array $queryParams, array $pathParams, array $payload): array;
}
