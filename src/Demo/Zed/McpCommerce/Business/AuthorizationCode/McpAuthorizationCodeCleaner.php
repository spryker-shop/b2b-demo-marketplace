<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\McpCommerce\Business\AuthorizationCode;

use DateTimeImmutable;
use Demo\Zed\McpCommerce\Persistence\McpCommerceEntityManagerInterface;

class McpAuthorizationCodeCleaner implements McpAuthorizationCodeCleanerInterface
{
    /**
     * @var string
     */
    protected const DATE_TIME_FORMAT = 'Y-m-d H:i:s';

    /**
     * @var \Demo\Zed\McpCommerce\Persistence\McpCommerceEntityManagerInterface
     */
    protected McpCommerceEntityManagerInterface $mcpCommerceEntityManager;

    /**
     * @param \Demo\Zed\McpCommerce\Persistence\McpCommerceEntityManagerInterface $mcpCommerceEntityManager
     */
    public function __construct(McpCommerceEntityManagerInterface $mcpCommerceEntityManager)
    {
        $this->mcpCommerceEntityManager = $mcpCommerceEntityManager;
    }

    /**
     * @return int
     */
    public function deleteExpired(): int
    {
        return $this->mcpCommerceEntityManager->deleteExpiredMcpAuthorizationCodes(
            (new DateTimeImmutable())->format(static::DATE_TIME_FORMAT),
        );
    }
}
