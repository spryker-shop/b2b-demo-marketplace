<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Go\Zed\TenantAssigner\Business\Service;

interface TableDiscoveryServiceInterface
{
    /**
     * @return array<\Generated\Shared\Transfer\TenantTableTransfer>
     */
    public function getTablesWithTenantColumn(): array;
}
