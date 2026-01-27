<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\AmazonQuicksight;

use SprykerEco\Zed\AmazonQuicksight\AmazonQuicksightConfig as SprykerAmazonQuicksightConfig;

class AmazonQuicksightConfig extends SprykerAmazonQuicksightConfig
{
    protected const string ASSET_BUNDLE_IMPORT_FILE_PATH = '%s/src/Pyz/Zed/AmazonQuicksight/data/asset-bundle.zip';

    /**
     * @var list<string>
     */
    protected const array ASSET_BUNDLE_IMPORT_DELETE_DATA_SET_IDS = [
        'SprykerB2BMPDefaultDatasetCategoryLocalizedProductAbstract',
        'SprykerB2BMPDefaultDatasetCompany',
        'SprykerB2BMPDefaultDatasetCustomer',
        'SprykerB2BMPDefaultDatasetCustomerAddress',
        'SprykerB2BMPDefaultDatasetMerchantCommission',
        'SprykerB2BMPDefaultDatasetMerchantOrder',
        'SprykerB2BMPDefaultDatasetMerchantOrderCategory',
        'SprykerB2BMPDefaultDatasetMerchantOrderItems',
        'SprykerB2BMPDefaultDatasetMerchantProductOffer',
        'SprykerB2BMPDefaultDatasetMerchantProductProductAbstract',
        'SprykerB2BMPDefaultDatasetMerchantStore',
        'SprykerB2BMPDefaultDatasetOrderDiscounts',
        'SprykerB2BMPDefaultDatasetOrderItemCategoryProductBrand',
        'SprykerB2BMPDefaultDatasetOrderItemLocalizedProductConcrete',
        'SprykerB2BMPDefaultDatasetOrderItemProductCategory',
        'SprykerB2BMPDefaultDatasetOrderItemsReturnDate',
        'SprykerB2BMPDefaultDatasetOrderItemState',
        'SprykerB2BMPDefaultDatasetOrderItemStateCustomers',
        'SprykerB2BMPDefaultDatasetOrderItemStateHistory',
        'SprykerB2BMPDefaultDatasetOrderPaymentMethods',
        'SprykerB2BMPDefaultDatasetOrderReturns',
        'SprykerB2BMPDefaultDatasetOrderReturnsProductConcrete',
        'SprykerB2BMPDefaultDatasetOrderShipmentMethods',
        'SprykerB2BMPDefaultDatasetOrderTotalsCustomerCompany',
        'SprykerB2BMPDefaultDatasetOrderTotalsCustomSQL',
        'SprykerB2BMPDefaultDatasetProductConcreteAvailability',
        'SprykerB2BMPDefaultDatasetProductConcreteStore',
        'SprykerB2BMPDefaultDatasetQuoteProducts',
        'SprykerB2BMPDefaultDatasetShoppingListProducts',
    ];

    protected const string DEFAULT_DATA_SOURCE_ID = 'SprykerB2BMPDefaultDataSource';

    /**
     * @return string
     */
    public function getAssetBundleImportFilePath(): string
    {
        return sprintf(static::ASSET_BUNDLE_IMPORT_FILE_PATH, APPLICATION_ROOT_DIR);
    }
}
