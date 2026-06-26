<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace DemoTest\Zed\PriceProductDataImport;

use Codeception\Actor;
use Demo\Zed\PriceProductDataImport\Business\Model\DataSet\PriceProductDataSet;
use Generated\Shared\Transfer\ProductAbstractTransfer;
use Orm\Zed\PriceProduct\Persistence\SpyPriceProductStoreQuery;
use Spryker\Zed\DataImport\Business\Model\DataSet\DataSet;

/**
 * Inherited Methods
 *
 * @method void wantToTest($text)
 * @method void wantTo($text)
 * @method void execute($callable)
 * @method void expectTo($prediction)
 * @method void expect($prediction)
 * @method void amGoingTo($argumentation)
 * @method void am($role)
 * @method void lookForwardTo($achieveValue)
 * @method void comment($description)
 * @method \Codeception\Lib\Friend haveFriend($name, $actorClass = NULL)
 *
 * @SuppressWarnings(\DemoTest\Zed\PriceProductDataImport\PHPMD)
 */
class PriceProductDataImportBusinessTester extends Actor
{
    use _generated\PriceProductDataImportBusinessTesterActions;

    public const string STORE_NAME = 'DE';

    public const string CURRENCY_ISO_CODE = 'EUR';

    public const string PRICE_TYPE_NAME = 'DEFAULT';

    public const string PRICE_DATA = '[]';

    public function createPriceProductDataSet(ProductAbstractTransfer $productAbstractTransfer, int $grossPrice, int $netPrice, ?int $costPrice): DataSet
    {
        $dataSet = new DataSet();
        $dataSet[PriceProductDataSet::KEY_PRICE_TYPE] = static::PRICE_TYPE_NAME;
        $dataSet[PriceProductDataSet::KEY_ABSTRACT_SKU] = $productAbstractTransfer->getSku();
        $dataSet[PriceProductDataSet::KEY_CONCRETE_SKU] = '';
        $dataSet[PriceProductDataSet::ID_PRODUCT_ABSTRACT] = $productAbstractTransfer->getIdProductAbstract();
        $dataSet[PriceProductDataSet::ID_STORE] = $this->getIdStore();
        $dataSet[PriceProductDataSet::ID_CURRENCY] = $this->getIdCurrency();
        $dataSet[PriceProductDataSet::KEY_PRICE_GROSS] = $grossPrice;
        $dataSet[PriceProductDataSet::KEY_PRICE_NET] = $netPrice;
        $dataSet[PriceProductDataSet::KEY_PRICE_COST] = $costPrice === null ? '' : (string)$costPrice;
        $dataSet[PriceProductDataSet::KEY_PRICE_DATA] = static::PRICE_DATA;
        $dataSet[PriceProductDataSet::KEY_PRICE_DATA_CHECKSUM] = md5(sprintf('%d-%d-%s', $grossPrice, $netPrice, (string)$costPrice));

        return $dataSet;
    }

    public function findStoreCostPrice(int $idProductAbstract): ?int
    {
        $priceProductStoreEntity = SpyPriceProductStoreQuery::create()
            ->usePriceProductQuery()
                ->filterByFkProductAbstract($idProductAbstract)
            ->endUse()
            ->filterByFkStore($this->getIdStore())
            ->findOne();

        return $priceProductStoreEntity?->getCostPrice();
    }

    public function getIdStore(): int
    {
        return $this->getLocator()->store()->facade()->getStoreByName(static::STORE_NAME)->getIdStore();
    }

    public function getIdCurrency(): int
    {
        return $this->getLocator()->currency()->facade()->fromIsoCode(static::CURRENCY_ISO_CODE)->getIdCurrency();
    }
}
