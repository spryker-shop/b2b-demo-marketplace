<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\PriceProductOffer\Persistence;

use Demo\Zed\PriceProductOffer\Persistence\Propel\Mapper\PriceProductOfferMapper;
use Spryker\Zed\PriceProductOffer\Persistence\PriceProductOfferPersistenceFactory as SprykerPriceProductOfferPersistenceFactory;

/**
 * @method \Spryker\Zed\PriceProductOffer\Persistence\PriceProductOfferRepositoryInterface getRepository()
 * @method \Spryker\Zed\PriceProductOffer\Persistence\PriceProductOfferEntityManagerInterface getEntityManager()
 * @method \Spryker\Zed\PriceProductOffer\PriceProductOfferConfig getConfig()
 */
class PriceProductOfferPersistenceFactory extends SprykerPriceProductOfferPersistenceFactory
{
    public function createPriceProductOfferMapper(): PriceProductOfferMapper
    {
        return new PriceProductOfferMapper();
    }
}
