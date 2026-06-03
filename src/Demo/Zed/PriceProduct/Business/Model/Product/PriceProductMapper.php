<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\PriceProduct\Business\Model\Product;

use Demo\Zed\PriceProduct\Dependency\Facade\PriceProductToPriceFacadeInterface;
use Spryker\Zed\PriceProduct\Business\Model\PriceType\ProductPriceTypeMapperInterface;
use Spryker\Zed\PriceProduct\Business\Model\Product\PriceProductMapper as SprykerPriceProductMapper;
use Spryker\Zed\PriceProduct\Dependency\Facade\PriceProductToCurrencyFacadeInterface;
use Spryker\Zed\PriceProduct\PriceProductConfig;

class PriceProductMapper extends SprykerPriceProductMapper implements PriceProductMapperInterface
{
    /**
     * @var \Demo\Zed\PriceProduct\Dependency\Facade\PriceProductToPriceFacadeInterface
     */
    protected $priceFacade;

    public function __construct(
        PriceProductToCurrencyFacadeInterface $currencyFacade,
        ProductPriceTypeMapperInterface $priceProductTypeMapper,
        PriceProductToPriceFacadeInterface $priceFacade,
        PriceProductConfig $config,
    ) {
        parent::__construct($currencyFacade, $priceProductTypeMapper, $priceFacade, $config);
    }

    public function getCostPriceModeIdentifier(): string
    {
        return $this->priceFacade->getCostPriceModeIdentifier();
    }
}
