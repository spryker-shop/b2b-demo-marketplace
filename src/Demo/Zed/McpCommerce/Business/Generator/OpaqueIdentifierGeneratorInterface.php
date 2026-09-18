<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\McpCommerce\Business\Generator;

interface OpaqueIdentifierGeneratorInterface
{
    /**
     * Specification:
     * - Returns a cryptographically random, url-safe, opaque identifier.
     * - Used for both authorization codes and MCP access tokens.
     *
     * @return string
     */
    public function generate(): string;
}
