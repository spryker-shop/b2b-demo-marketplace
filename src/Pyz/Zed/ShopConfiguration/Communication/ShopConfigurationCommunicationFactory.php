<?php

declare(strict_types=1);

namespace Pyz\Zed\ShopConfiguration\Communication;

use Pyz\Zed\ShopConfiguration\ShopConfigurationDependencyProvider;
use Spryker\Zed\Kernel\Communication\AbstractCommunicationFactory;
use Spryker\Zed\Store\Business\StoreFacadeInterface;

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
}
