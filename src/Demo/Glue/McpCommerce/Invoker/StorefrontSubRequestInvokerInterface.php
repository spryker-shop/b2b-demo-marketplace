<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Glue\McpCommerce\Invoker;

interface StorefrontSubRequestInvokerInterface
{
    /**
     * Specification:
     * - Dispatches an internal Symfony sub-request against an existing Storefront API route.
     * - Injects the given OAuth identity claims onto the sub-request so the Storefront identity
     *   subscribers resolve the acting customer without an `Authorization` header.
     * - Pre-populates the security token storage with an authenticated customer token, because the
     *   Symfony firewall does not run on sub-requests, and restores the previous token afterwards.
     * - Never sets an `Authorization` header, so the customer's shop token is structurally absent.
     *
     * @api
     *
     * @param string $path Storefront API path, e.g. `/carts`.
     * @param string $method HTTP method, e.g. `GET` or `POST`.
     * @param array<string, mixed> $identityClaims OAuth identity claims, e.g. `customer_reference`, `id_customer`.
     * @param array<string, mixed>|null $body JSON:API request body, or null for a body-less request.
     * @param array<string, mixed> $queryParameters Query parameters to append to the sub-request.
     */
    public function invoke(
        string $path,
        string $method,
        array $identityClaims,
        ?array $body = null,
        array $queryParameters = [],
    ): StorefrontSubRequestResult;
}
