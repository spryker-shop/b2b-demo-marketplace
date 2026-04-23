<?php

namespace Pyz\Zed\Configuration;

use Generated\Shared\Transfer\DataImporterDataSourceConfigurationTransfer;

class ConfigurationConfig extends \Spryker\Zed\Configuration\ConfigurationConfig
{
    /**
     * Specification:
     * - Returns the data importer data source configuration for configuration value import.
     * - Used by `DataImportFactoryTrait::getCsvDataImporterFromConfig()` to create the CSV reader.
     *
     * @api
     */
    public function getConfigurationValueDataImporterDataSourceConfiguration(): DataImporterDataSourceConfigurationTransfer
    {
        return (new DataImporterDataSourceConfigurationTransfer())
            ->setImportType(static::IMPORT_TYPE_CONFIGURATION_VALUE)
            ->setModuleName('configuration')
            ->setFileName('configuration_value.csv');
    }
}
