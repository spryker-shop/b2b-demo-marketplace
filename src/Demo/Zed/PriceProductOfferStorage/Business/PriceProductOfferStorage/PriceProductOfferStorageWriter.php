<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\PriceProductOfferStorage\Business\PriceProductOfferStorage;

use Generated\Shared\Transfer\PriceProductOfferStorageTransfer;
use Orm\Zed\Currency\Persistence\Map\SpyCurrencyTableMap;
use Orm\Zed\PriceProduct\Persistence\Map\SpyPriceProductStoreTableMap;
use Orm\Zed\PriceProduct\Persistence\Map\SpyPriceTypeTableMap;
use Orm\Zed\PriceProductOffer\Persistence\Map\SpyPriceProductOfferTableMap;
use Orm\Zed\Product\Persistence\Map\SpyProductTableMap;
use Orm\Zed\ProductOffer\Persistence\Map\SpyProductOfferTableMap;
use Orm\Zed\ProductOffer\Persistence\SpyProductOfferQuery;
use Orm\Zed\Store\Persistence\Map\SpyStoreTableMap;
use Propel\Runtime\ActiveQuery\Criteria;
use Spryker\Zed\PriceProductOfferStorage\Business\PriceProductOfferStorage\PriceProductOfferStorageWriter as SprykerPriceProductOfferStorageWriter;

class PriceProductOfferStorageWriter extends SprykerPriceProductOfferStorageWriter
{
    /**
     * @param array<string> $productSkus
     *
     * @return array<mixed>
     */
    protected function getProductOfferDataByProductSkus(array $productSkus): array
    {
        /** @var \Propel\Runtime\Collection\ObjectCollection $productOfferData */
        $productOfferData = SpyProductOfferQuery::create()
            ->useSpyPriceProductOfferQuery()
                ->useSpyPriceProductStoreQuery()
                    ->joinWithCurrency()
                    ->joinWithStore()
                    ->usePriceProductQuery()
                        ->joinWithPriceType()
                    ->endUse()
                ->endUse()
            ->endUse()
            ->addJoin(
                SpyProductOfferTableMap::COL_CONCRETE_SKU,
                SpyProductTableMap::COL_SKU,
                Criteria::INNER_JOIN,
            )
            ->filterByConcreteSku_In($productSkus)
            ->addAnd(
                SpyProductTableMap::COL_IS_ACTIVE,
                1,
                Criteria::EQUAL,
            )
            ->select([
                SpyPriceProductOfferTableMap::COL_ID_PRICE_PRODUCT_OFFER,
                SpyProductOfferTableMap::COL_CONCRETE_SKU,
                SpyProductOfferTableMap::COL_PRODUCT_OFFER_REFERENCE,
                SpyCurrencyTableMap::COL_CODE,
                SpyStoreTableMap::COL_NAME,
                SpyPriceTypeTableMap::COL_NAME,
                SpyPriceProductStoreTableMap::COL_GROSS_PRICE,
                SpyPriceProductStoreTableMap::COL_NET_PRICE,
                SpyPriceProductStoreTableMap::COL_COST_PRICE,
                SpyPriceProductStoreTableMap::COL_PRICE_DATA,
            ])
            ->withColumn(SpyProductTableMap::COL_ID_PRODUCT, static::COL_ID_PRODUCT_NAME)
            ->find();

        return $productOfferData->toArray();
    }

    /**
     * @param array<mixed> $productOffer
     */
    protected function createPriceProductOfferStorageTransfer(array $productOffer): PriceProductOfferStorageTransfer
    {
        $priceProductOfferStorageTransfer = parent::createPriceProductOfferStorageTransfer($productOffer);

        return $priceProductOfferStorageTransfer->setCostPrice(
            $productOffer[SpyPriceProductStoreTableMap::COL_COST_PRICE] ?? null,
        );
    }
}
