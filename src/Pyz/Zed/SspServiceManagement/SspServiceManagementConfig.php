<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\SspServiceManagement;

use Generated\Shared\Transfer\DataImporterDataSourceConfigurationTransfer;
use SprykerFeature\Zed\SspServiceManagement\SspServiceManagementConfig as SprykerSspServiceManagementConfig;

class SspServiceManagementConfig extends SprykerSspServiceManagementConfig
{
    /**
     * @return string
     */
    public function getDefaultMerchantReference(): string
    {
        return 'MER000001';
    }

    /**
     * @return \Generated\Shared\Transfer\DataImporterDataSourceConfigurationTransfer
     */
    public function getProductShipmentTypeDataImporterConfiguration(): DataImporterDataSourceConfigurationTransfer
    {
        return (new DataImporterDataSourceConfigurationTransfer())
            ->setImportType(static::IMPORT_TYPE_PRODUCT_SHIPMENT_TYPE)
            ->setFileName('product_shipment_type.csv')
            ->setModuleName('ssp-service-management')
            ->setDirectory('/data/data/import/common/common/');
    }

    /**
     * @return \Generated\Shared\Transfer\DataImporterDataSourceConfigurationTransfer
     */
    public function getProductAbstractTypeDataImporterConfiguration(): DataImporterDataSourceConfigurationTransfer
    {
        return (new DataImporterDataSourceConfigurationTransfer())
            ->setImportType(static::IMPORT_TYPE_PRODUCT_ABSTRACT_TYPE)
            ->setFileName('product_abstract_type.csv')
            ->setModuleName('ssp-service-management')
            ->setDirectory('/data/import/common/common');
    }

    /**
     * @return \Generated\Shared\Transfer\DataImporterDataSourceConfigurationTransfer
     */
    public function getProductAbstractToProductAbstractTypeDataImporterConfiguration(): DataImporterDataSourceConfigurationTransfer
    {
        return (new DataImporterDataSourceConfigurationTransfer())
            ->setImportType(static::IMPORT_TYPE_PRODUCT_ABSTRACT_TO_PRODUCT_ABSTRACT_TYPE)
            ->setFileName('product_abstract_product_abstract_type.csv')
            ->setModuleName('ssp-service-management')
            ->setDirectory('/data/data/import/common/common/');
    }
}
