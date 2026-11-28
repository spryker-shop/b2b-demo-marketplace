<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\PriceProductDataImport;

use Spryker\Zed\PriceProductDataImport\PriceProductDataImportConfig as SprykerPriceProductDataImportConfig;

/**
 * Backport of spryker/suite#1644 (CC-41867). Remove when spryker/price-product-data-import ships it.
 */
class PriceProductDataImportConfig extends SprykerPriceProductDataImportConfig
{
    /**
     * @var int
     */
    protected const PRICE_PRODUCT_DATA_IMPORT_BULK_SIZE = 1000;

    /**
     * Specification:
     * - Returns the number of data sets the price product import writes in one database transaction.
     * - Return `null` to commit after every data set.
     *
     * @api
     */
    public function getPriceProductDataImportBulkSize(): ?int
    {
        return static::PRICE_PRODUCT_DATA_IMPORT_BULK_SIZE;
    }
}
