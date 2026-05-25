<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\ProductOfferShipmentTypeDataImport;

use Spryker\Zed\ProductOfferShipmentTypeDataImport\ProductOfferShipmentTypeDataImportDependencyProvider as SprykerProductOfferShipmentTypeDataImportDependencyProvider;
use SprykerFeature\Zed\SelfServicePortal\Communication\Plugin\ProductOfferShipmentTypeDataImport\ProductOfferConcreteShipmentTypeValidatorStepPlugin;

class ProductOfferShipmentTypeDataImportDependencyProvider extends SprykerProductOfferShipmentTypeDataImportDependencyProvider
{
    protected function getProductOfferShipmentTypeValidatorStepPlugins(): array
    {
        return [
            new ProductOfferConcreteShipmentTypeValidatorStepPlugin(),
        ];
    }
}
