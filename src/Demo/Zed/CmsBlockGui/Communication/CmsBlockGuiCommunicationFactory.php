<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\CmsBlockGui\Communication;

use Demo\Zed\CmsBlockCustomerGroup\Business\CmsBlockCustomerGroupFacadeInterface;
use Demo\Zed\CmsBlockGui\CmsBlockGuiDependencyProvider;
use Demo\Zed\CmsBlockGui\Communication\Form\Block\CmsBlockForm;
use Demo\Zed\CmsBlockGui\Communication\Form\DataProvider\CmsBlockFormDataProvider;
use Demo\Zed\CmsBlockGui\Communication\Tabs\CmsBlockFormAddTabs;
use Demo\Zed\CmsBlockGui\Communication\Tabs\CmsBlockFormEditTabs;
use Demo\Zed\CustomerGroup\Business\CustomerGroupFacadeInterface;
use Spryker\Zed\CmsBlockGui\Communication\CmsBlockGuiCommunicationFactory as SprykerCmsBlockGuiCommunicationFactory;
use Spryker\Zed\CmsBlockGui\Communication\Form\DataProvider\CmsBlockFormDataProvider as SprykerCmsBlockFormDataProvider;
use Spryker\Zed\Gui\Communication\Tabs\TabsInterface;
use Symfony\Component\Form\FormInterface;

/**
 * @method \Spryker\Zed\CmsBlockGui\CmsBlockGuiConfig getConfig()
 */
class CmsBlockGuiCommunicationFactory extends SprykerCmsBlockGuiCommunicationFactory
{
    public function createCmsBlockFormDataProvider(): CmsBlockFormDataProvider
    {
        return new CmsBlockFormDataProvider(
            $this->getCmsBlockQueryContainer(),
            $this->getCmsBlockFacade(),
            $this->getLocaleFacade(),
            $this->getCustomerGroupFacade(),
            $this->getCmsBlockCustomerGroupFacade(),
        );
    }

    /**
     * @phpcsSuppress SprykerStrict.TypeHints.ParameterTypeHint.MissingNativeTypeHint
     *
     * @param \Spryker\Zed\CmsBlockGui\Communication\Form\DataProvider\CmsBlockFormDataProvider $cmsBlockFormDataProvider
     * @param mixed $idCmsBlock
     *
     * @return \Symfony\Component\Form\FormInterface
     */
    public function createCmsBlockForm(SprykerCmsBlockFormDataProvider $cmsBlockFormDataProvider, $idCmsBlock = null): FormInterface
    {
        return $this->getFormFactory()->create(
            CmsBlockForm::class,
            $cmsBlockFormDataProvider->getData($idCmsBlock),
            $cmsBlockFormDataProvider->getOptions(),
        );
    }

    public function createCmsBlockFormAddTabs(): TabsInterface
    {
        return new CmsBlockFormAddTabs();
    }

    public function createCmsBlockFormEditTabs(): TabsInterface
    {
        return new CmsBlockFormEditTabs();
    }

    public function getCustomerGroupFacade(): CustomerGroupFacadeInterface
    {
        return $this->getProvidedDependency(CmsBlockGuiDependencyProvider::FACADE_CUSTOMER_GROUP);
    }

    public function getCmsBlockCustomerGroupFacade(): CmsBlockCustomerGroupFacadeInterface
    {
        return $this->getProvidedDependency(CmsBlockGuiDependencyProvider::FACADE_CMS_BLOCK_CUSTOMER_GROUP);
    }
}
