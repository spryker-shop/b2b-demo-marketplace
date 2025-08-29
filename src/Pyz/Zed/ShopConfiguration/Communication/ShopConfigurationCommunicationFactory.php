<?php

declare(strict_types=1);

namespace Pyz\Zed\ShopConfiguration\Communication;

use Pyz\Zed\ShopConfiguration\Communication\Form\StoreConfigurationForm;
use Pyz\Zed\ShopConfiguration\ShopConfigurationDependencyProvider;
use Spryker\Zed\Company\Business\CompanyFacadeInterface;
use Spryker\Zed\CompanyGui\Communication\Form\CompanyForm;
use Spryker\Zed\Kernel\Communication\AbstractCommunicationFactory;
use Spryker\Zed\Store\Business\StoreFacadeInterface;
use Symfony\Component\Form\FormInterface;

/**
 * @method \Pyz\Zed\ShopConfiguration\ShopConfigurationConfig getConfig()
 * @method \Pyz\Zed\ShopConfiguration\Business\ShopConfigurationFacadeInterface getFacade()
 * @method \Pyz\Zed\ShopConfiguration\Persistence\ShopConfigurationRepositoryInterface getRepository()
 * @method \Pyz\Zed\ShopConfiguration\Persistence\ShopConfigurationEntityManagerInterface getEntityManager()
 */
class ShopConfigurationCommunicationFactory extends AbstractCommunicationFactory
{
    /**
     * @return \Spryker\Zed\Store\Business\StoreFacadeInterface
     */
    public function getStoreFacade(): StoreFacadeInterface
    {
        return $this->getProvidedDependency(ShopConfigurationDependencyProvider::FACADE_STORE);
    }

    /**
     * @param array|null $data
     * @param array<string, mixed> $options
     *
     * @return \Symfony\Component\Form\FormInterface
     */
    public function getStoreConfigurationForm($data = null, array $options = []): FormInterface
    {
        return $this->getFormFactory()->create(StoreConfigurationForm::class, $data, $options);
    }

    /**
     * @return \Generated\Zed\Ide\AutoCompletion&\Spryker\Shared\Kernel\LocatorLocatorInterface
     */
    public function getLocator(): \Spryker\Shared\Kernel\LocatorLocatorInterface
    {
        return $this->getContainer()->getLocator();
    }
}
