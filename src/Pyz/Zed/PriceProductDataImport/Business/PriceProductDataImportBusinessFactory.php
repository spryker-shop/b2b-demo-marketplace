<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\PriceProductDataImport\Business;

use Pyz\Zed\DataImport\Business\Model\DataSet\DataSetStepBrokerTransactionAware;
use Pyz\Zed\PriceProductDataImport\Business\Model\PriceProductWriterStep;
use Spryker\Zed\DataImport\Business\Model\DataImporterInterface;
use Spryker\Zed\DataImport\Business\Model\DataImportStep\DataImportStepInterface;
use Spryker\Zed\PriceProductDataImport\Business\PriceProductDataImportBusinessFactory as SprykerPriceProductDataImportBusinessFactory;

/**
 * Backport of spryker/suite#1644 (CC-41867): the price import commits in bulks instead of after every data set,
 * and the price type id is cached per run.
 *
 * The broker and the writer step use the Pyz data import publisher, so the events of a bulk are sent only after
 * the bulk is committed (CC-41794). With the core publisher a bulk would expose price events to that race.
 *
 * @method \Pyz\Zed\PriceProductDataImport\PriceProductDataImportConfig getConfig()
 */
class PriceProductDataImportBusinessFactory extends SprykerPriceProductDataImportBusinessFactory
{
    public function createPriceProductDataImport(): DataImporterInterface
    {
        $dataImporter = $this->getCsvDataImporterFromConfig($this->getConfig()->getPriceProductDataImporterConfiguration());

        $dataSetStepBroker = $this->createTransactionAwareDataSetStepBroker($this->getConfig()->getPriceProductDataImportBulkSize());
        $dataSetStepBroker->addStep($this->createAbstractSkuToIdProductAbstractStep());
        $dataSetStepBroker->addStep($this->createConcreteSkuToIdProductStep());
        $dataSetStepBroker->addStep($this->createStoreToIdStoreStep());
        $dataSetStepBroker->addStep($this->createCurrencyToIdCurrencyStep());
        $dataSetStepBroker->addStep($this->createPreparePriceDataStep());
        $dataSetStepBroker->addStep($this->createPriceProductWriterStep());

        $dataImporter->addDataSetStepBroker($dataSetStepBroker);

        return $dataImporter;
    }

    /**
     * @param int|null $bulkSize
     *
     * @return \Spryker\Zed\DataImport\Business\Model\DataSet\DataSetStepBrokerInterface|\Spryker\Zed\DataImport\Business\Model\DataImportStep\DataImportStepAwareInterface
     */
    public function createTransactionAwareDataSetStepBroker($bulkSize = null) // phpcs:ignore SprykerStrict.TypeHints.ParameterTypeHint.MissingNativeTypeHint -- the parent has no native type
    {
        return new DataSetStepBrokerTransactionAware($this->getPropelConnection(), $bulkSize);
    }

    public function createPriceProductWriterStep(): DataImportStepInterface
    {
        return new PriceProductWriterStep();
    }
}
