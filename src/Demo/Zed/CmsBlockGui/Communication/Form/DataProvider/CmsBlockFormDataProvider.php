<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\CmsBlockGui\Communication\Form\DataProvider;

use Demo\Zed\CmsBlockCustomerGroup\Business\CmsBlockCustomerGroupFacadeInterface;
use Demo\Zed\CmsBlockGui\Communication\Form\Block\CmsBlockForm;
use Demo\Zed\CmsBlockGui\Dependency\Facade\CmsBlockGuiToCustomerGroupFacadeInterface;
use Generated\Shared\Transfer\CustomerGroupCollectionTransfer;
use Spryker\Zed\CmsBlockGui\Communication\Form\DataProvider\CmsBlockFormDataProvider as SprykerCmsBlockFormDataProvider;
use Spryker\Zed\CmsBlockGui\Dependency\Facade\CmsBlockGuiToCmsBlockInterface;
use Spryker\Zed\CmsBlockGui\Dependency\Facade\CmsBlockGuiToLocaleInterface;
use Spryker\Zed\CmsBlockGui\Dependency\QueryContainer\CmsBlockGuiToCmsBlockQueryContainerInterface;

class CmsBlockFormDataProvider extends SprykerCmsBlockFormDataProvider
{
    public function __construct(
        CmsBlockGuiToCmsBlockQueryContainerInterface $cmsBlockQueryContainer,
        CmsBlockGuiToCmsBlockInterface $cmsBlockFacade,
        CmsBlockGuiToLocaleInterface $localeFacade,
        protected CmsBlockGuiToCustomerGroupFacadeInterface $customerGroupFacade,
        protected CmsBlockCustomerGroupFacadeInterface $cmsBlockCustomerGroupFacade,
    ) {
        parent::__construct($cmsBlockQueryContainer, $cmsBlockFacade, $localeFacade);
    }

    /**
     * @return array<string, mixed>
     */
    public function getOptions(): array
    {
        return array_merge(
            parent::getOptions(),
            [
                CmsBlockForm::OPTION_CUSTOMER_GROUP => $this->getCustomerGroupNamesIndexedById(),
            ],
        );
    }

    /**
     * @phpcsSuppress SprykerStrict.TypeHints.ParameterTypeHint.MissingNativeTypeHint
     * @phpcsSuppress SprykerStrict.TypeHints.ReturnTypeHint.MissingNativeTypeHint
     *
     * @phpstan-return \Generated\Shared\Transfer\CmsBlockTransfer
     *
     * @param mixed $idCmsBlock
     */
    public function getData($idCmsBlock = null)
    {
        $cmsBlockTransfer = parent::getData($idCmsBlock);
        $cmsBlockTransfer->setCustomerGroups(new CustomerGroupCollectionTransfer());

        if (!$idCmsBlock) {
            return $cmsBlockTransfer;
        }

        $cmsBlockTransfer->setCustomerGroups(
            $this->cmsBlockCustomerGroupFacade->getCmsBlockCustomerGroups($cmsBlockTransfer),
        );

        return $cmsBlockTransfer;
    }

    /**
     * @return array<int, string>
     */
    protected function getCustomerGroupNamesIndexedById(): array
    {
        $customerGroupCollectionTransfer = $this->customerGroupFacade->getCustomerGroupCollection();

        $result = [];
        foreach ($customerGroupCollectionTransfer->getGroups() as $customerGroupTransfer) {
            $idCustomerGroup = $customerGroupTransfer->getIdCustomerGroup();
            if ($idCustomerGroup === null) {
                continue;
            }
            $result[$idCustomerGroup] = (string)$customerGroupTransfer->getName();
        }

        return $result;
    }
}
