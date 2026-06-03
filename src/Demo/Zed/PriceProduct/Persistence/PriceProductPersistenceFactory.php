<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\PriceProduct\Persistence;

use Demo\Zed\PriceProduct\Persistence\Propel\Mapper\PriceProductMapper;
use Spryker\Zed\PriceProduct\Persistence\PriceProductPersistenceFactory as SprykerPriceProductPersistenceFactory;

/**
 * @method \Spryker\Zed\PriceProduct\Persistence\PriceProductRepositoryInterface getRepository()
 * @method \Spryker\Zed\PriceProduct\Persistence\PriceProductEntityManagerInterface getEntityManager()
 * @method \Demo\Zed\PriceProduct\PriceProductConfig getConfig()
 * @method \Spryker\Zed\PriceProduct\Persistence\PriceProductQueryContainerInterface getQueryContainer()
 */
class PriceProductPersistenceFactory extends SprykerPriceProductPersistenceFactory
{
    public function createPriceProductMapper(): PriceProductMapper
    {
        return new PriceProductMapper();
    }
}
