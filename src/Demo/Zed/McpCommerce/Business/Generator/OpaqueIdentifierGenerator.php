<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\McpCommerce\Business\Generator;

use Demo\Zed\McpCommerce\McpCommerceConfig;

class OpaqueIdentifierGenerator implements OpaqueIdentifierGeneratorInterface
{
    /**
     * @var \Demo\Zed\McpCommerce\McpCommerceConfig
     */
    protected McpCommerceConfig $mcpCommerceConfig;

    /**
     * @param \Demo\Zed\McpCommerce\McpCommerceConfig $mcpCommerceConfig
     */
    public function __construct(McpCommerceConfig $mcpCommerceConfig)
    {
        $this->mcpCommerceConfig = $mcpCommerceConfig;
    }

    /**
     * @return string
     */
    public function generate(): string
    {
        $randomBytes = random_bytes($this->mcpCommerceConfig->getRandomIdentifierByteLength());

        return rtrim(strtr(base64_encode($randomBytes), '+/', '-_'), '=');
    }
}
