<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\CmsBlockCustomerGroup\Persistence;

interface CmsBlockCustomerGroupEntityManagerInterface
{
    /**
     * @param array<int> $customerGroupIds
     * @param int $idCmsBlock
     *
     * @return void
     */
    public function createCmsBlockCustomerGroups(array $customerGroupIds, int $idCmsBlock): void;

    /**
     * @param array<int> $customerGroupIds
     * @param int $idCmsBlock
     *
     * @return void
     */
    public function deleteCmsBlockCustomerGroups(array $customerGroupIds, int $idCmsBlock): void;
}
