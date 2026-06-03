<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\PriceProductDataImport\Business;

use Demo\Zed\PriceProductDataImport\Business\Model\PriceProductWriterStep;
use Spryker\Zed\DataImport\Business\Model\DataImporterInterface;
use Spryker\Zed\PriceProductDataImport\Business\PriceProductDataImportBusinessFactory as SprykerPriceProductDataImportBusinessFactory;

/**
 * @method \Spryker\Zed\PriceProductDataImport\PriceProductDataImportConfig getConfig()
 */
class PriceProductDataImportBusinessFactory extends SprykerPriceProductDataImportBusinessFactory
{
    public function createPriceProductDataImport(): DataImporterInterface
    {
        $dataImporter = $this->getCsvDataImporterFromConfig($this->getConfig()->getPriceProductDataImporterConfiguration());

        $dataSetStepBroker = $this->createTransactionAwareDataSetStepBroker();
        $dataSetStepBroker->addStep($this->createAbstractSkuToIdProductAbstractStep());
        $dataSetStepBroker->addStep($this->createConcreteSkuToIdProductStep());
        $dataSetStepBroker->addStep($this->createStoreToIdStoreStep());
        $dataSetStepBroker->addStep($this->createCurrencyToIdCurrencyStep());
        $dataSetStepBroker->addStep($this->createPreparePriceDataStep());
        $dataSetStepBroker->addStep(new PriceProductWriterStep());

        $dataImporter->addDataSetStepBroker($dataSetStepBroker);

        return $dataImporter;
    }
}
