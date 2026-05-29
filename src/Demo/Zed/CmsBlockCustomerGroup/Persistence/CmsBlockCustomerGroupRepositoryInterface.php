<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\CmsBlockCustomerGroup\Persistence;

use Generated\Shared\Transfer\CustomerGroupCollectionTransfer;

interface CmsBlockCustomerGroupRepositoryInterface
{
    /**
     * @param int $idCmsBlock
     *
     * @return array<int>
     */
    public function getCmsBlockCustomerGroupIds(int $idCmsBlock): array;

    public function getCmsBlockCustomerGroups(int $idCmsBlock): CustomerGroupCollectionTransfer;

    public function hasCustomerInCmsBlockCustomerGroups(int $idCmsBlock, int $idCustomer): bool;
}
