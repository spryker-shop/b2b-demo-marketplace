<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\CmsBlockCustomerGroup\Business\Writer;

use Demo\Zed\CmsBlockCustomerGroup\Persistence\CmsBlockCustomerGroupEntityManagerInterface;
use Demo\Zed\CmsBlockCustomerGroup\Persistence\CmsBlockCustomerGroupRepositoryInterface;
use Generated\Shared\Transfer\CmsBlockTransfer;

class CmsBlockCustomerGroupWriter
{
    public function __construct(
        protected CmsBlockCustomerGroupEntityManagerInterface $entityManager,
        protected CmsBlockCustomerGroupRepositoryInterface $cmsBlockCustomerGroupRepository,
    ) {
    }

    /**
     * @param \Generated\Shared\Transfer\CmsBlockTransfer $cmsBlockTransfer
     *
     * @return void
     */
    public function saveCmsBlockCustomerGroups(CmsBlockTransfer $cmsBlockTransfer): void
    {
        $idCmsBlock = (int)$cmsBlockTransfer->getIdCmsBlockOrFail();

        $currentCustomerGroupIds = $this->cmsBlockCustomerGroupRepository->getCmsBlockCustomerGroupIds($idCmsBlock);
        $requestedCustomerGroupIds = $this->getRequestedCmsBlockCustomerGroupIds($cmsBlockTransfer);

        $customerGroupIdsToSave = array_diff($requestedCustomerGroupIds, $currentCustomerGroupIds);
        $customerGroupIdsToDelete = array_diff($currentCustomerGroupIds, $requestedCustomerGroupIds);

        $this->entityManager->createCmsBlockCustomerGroups($customerGroupIdsToSave, $idCmsBlock);
        $this->entityManager->deleteCmsBlockCustomerGroups($customerGroupIdsToDelete, $idCmsBlock);
    }

    /**
     * @param \Generated\Shared\Transfer\CmsBlockTransfer $cmsBlockTransfer
     *
     * @return array<int>
     */
    protected function getRequestedCmsBlockCustomerGroupIds(CmsBlockTransfer $cmsBlockTransfer): array
    {
        $customerGroupCollectionTransfer = $cmsBlockTransfer->getCustomerGroups();
        if ($customerGroupCollectionTransfer === null) {
            return [];
        }

        $customerGroupIds = [];
        foreach ($customerGroupCollectionTransfer->getGroups() as $customerGroupTransfer) {
            $idCustomerGroup = $customerGroupTransfer->getIdCustomerGroup();
            if ($idCustomerGroup === null) {
                continue;
            }

            $customerGroupIds[] = $idCustomerGroup;
        }

        return $customerGroupIds;
    }
}
