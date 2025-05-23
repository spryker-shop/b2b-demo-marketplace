<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\ProductOfferStockDataImport\Business;

use Generated\Shared\Transfer\DataImporterConfigurationTransfer;
use Generated\Shared\Transfer\DataImporterReportTransfer;
use Spryker\Zed\ProductOfferStockDataImport\Business\ProductOfferStockDataImportFacadeInterface as SprykerProductOfferStockDataImportFacadeInterface;

interface ProductOfferStockDataImportFacadeInterface extends SprykerProductOfferStockDataImportFacadeInterface
{
    /**
     * @param \Generated\Shared\Transfer\DataImporterConfigurationTransfer|null $dataImporterConfigurationTransfer
     *
     * @return \Generated\Shared\Transfer\DataImporterReportTransfer
     */
    public function importCombinedProductOfferStock(
        ?DataImporterConfigurationTransfer $dataImporterConfigurationTransfer,
    ): DataImporterReportTransfer;
}
