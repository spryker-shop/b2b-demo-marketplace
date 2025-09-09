<?php

declare(strict_types=1);

namespace Go\Zed\ShopConfiguration\Persistence;

use Spryker\Zed\Kernel\Persistence\AbstractPersistenceFactory;

/**
 * @method \Go\Zed\ShopConfiguration\ShopConfigurationConfig getConfig()
 * @method \Go\Zed\ShopConfiguration\Persistence\ShopConfigurationQueryContainerInterface getQueryContainer()
 */
class ShopConfigurationPersistenceFactory extends AbstractPersistenceFactory
{
    /**
     * @return \Orm\Zed\ShopConfiguration\Persistence\SpyShopConfigurationQuery
     */
    public function createShopConfigurationQuery()
    {
        return \Orm\Zed\ShopConfiguration\Persistence\SpyShopConfigurationQuery::create();
    }
}
