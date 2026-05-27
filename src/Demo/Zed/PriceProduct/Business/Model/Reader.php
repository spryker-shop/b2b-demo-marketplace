<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\PriceProduct\Business\Model;

use Demo\Zed\PriceProduct\Business\Model\Product\PriceProductMapperInterface;
use Generated\Shared\Transfer\MoneyValueTransfer;
use Generated\Shared\Transfer\PriceProductTransfer;
use Spryker\Service\PriceProduct\PriceProductServiceInterface;
use Spryker\Zed\PriceProduct\Business\Model\PriceProductCriteriaBuilderInterface;
use Spryker\Zed\PriceProduct\Business\Model\PriceType\PriceProductTypeReaderInterface;
use Spryker\Zed\PriceProduct\Business\Model\Product\PriceProductAbstractReaderInterface;
use Spryker\Zed\PriceProduct\Business\Model\Product\PriceProductConcreteReaderInterface;
use Spryker\Zed\PriceProduct\Business\Model\Product\PriceProductExpanderInterface;
use Spryker\Zed\PriceProduct\Business\Model\Reader as SprykerReader;
use Spryker\Zed\PriceProduct\Dependency\Facade\PriceProductToProductFacadeInterface;
use Spryker\Zed\PriceProduct\Persistence\PriceProductRepositoryInterface;
use Spryker\Zed\PriceProduct\PriceProductConfig;

class Reader extends SprykerReader
{
    public function __construct(
        PriceProductToProductFacadeInterface $productFacade,
        PriceProductTypeReaderInterface $priceProductTypeReader,
        PriceProductConcreteReaderInterface $priceProductConcreteReader,
        PriceProductAbstractReaderInterface $priceProductAbstractReader,
        PriceProductCriteriaBuilderInterface $priceProductCriteriaBuilder,
        protected PriceProductMapperInterface $projectPriceProductMapper,
        PriceProductConfig $config,
        PriceProductServiceInterface $priceProductService,
        PriceProductRepositoryInterface $priceProductRepository,
        PriceProductExpanderInterface $priceProductExpander,
    ) {
        parent::__construct(
            $productFacade,
            $priceProductTypeReader,
            $priceProductConcreteReader,
            $priceProductAbstractReader,
            $priceProductCriteriaBuilder,
            $projectPriceProductMapper,
            $config,
            $priceProductService,
            $priceProductRepository,
            $priceProductExpander,
        );
    }

    protected function resolveConcreteProductPrice(
        PriceProductTransfer $priceProductAbstractTransfer,
        PriceProductTransfer $priceProductConcreteTransfer,
    ): PriceProductTransfer {
        $abstractMoneyValueTransfer = $priceProductAbstractTransfer->getMoneyValueOrFail();
        $concreteMoneyValueTransfer = parent::resolveConcreteProductPrice($priceProductAbstractTransfer, $priceProductConcreteTransfer)->getMoneyValueOrFail();

        if ($concreteMoneyValueTransfer->getCostAmount() === null) {
            $concreteMoneyValueTransfer->setCostAmount($abstractMoneyValueTransfer->getCostAmount());
        }

        return $priceProductConcreteTransfer;
    }

    protected function getPriceByPriceMode(MoneyValueTransfer $moneyValueTransfer, string $priceMode): ?int
    {
        return match ($priceMode) {
            $this->projectPriceProductMapper->getNetPriceModeIdentifier() => $moneyValueTransfer->getNetAmount(),
            $this->projectPriceProductMapper->getCostPriceModeIdentifier() => $moneyValueTransfer->getCostAmount(),
            default => $moneyValueTransfer->getGrossAmount(),
        };
    }
}
