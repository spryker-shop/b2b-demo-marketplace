<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Pyz\Zed\ProductManagement;

use Spryker\Zed\CmsBlockProductConnector\Communication\Plugin\CmsBlockProductAbstractBlockListViewPlugin;
use Spryker\Zed\Kernel\Communication\Form\FormTypeInterface;
use Spryker\Zed\Kernel\Container;
use Spryker\Zed\MerchantGui\Communication\Plugin\ProductManagement\MerchantProductAbstractListActionViewDataExpanderPlugin;
use Spryker\Zed\MerchantProductGui\Communication\Plugin\ProductManagement\MerchantProductProductAbstractEditViewExpanderPlugin;
use Spryker\Zed\MerchantProductGui\Communication\Plugin\ProductManagement\MerchantProductProductAbstractViewActionViewDataExpanderPlugin;
use Spryker\Zed\MerchantProductGui\Communication\Plugin\ProductManagement\MerchantProductProductTableQueryCriteriaExpanderPlugin;
use Spryker\Zed\MoneyGui\Communication\Plugin\Form\MoneyFormTypePlugin;
use Spryker\Zed\PriceProductMerchantRelationshipGui\Communication\Plugin\ProductManagement\MerchantRelationshipProductAbstractFormExpanderPlugin;
use Spryker\Zed\PriceProductMerchantRelationshipGui\Communication\Plugin\ProductManagement\MerchantRelationshipProductConcreteFormExpanderPlugin;
use Spryker\Zed\PriceProductScheduleGui\Communication\Plugin\ProductManagement\ScheduledPriceProductAbstractEditViewExpanderPlugin;
use Spryker\Zed\PriceProductScheduleGui\Communication\Plugin\ProductManagement\ScheduledPriceProductAbstractFormEditTabsExpanderPlugin;
use Spryker\Zed\PriceProductScheduleGui\Communication\Plugin\ProductManagement\ScheduledPriceProductConcreteEditViewExpanderPlugin;
use Spryker\Zed\PriceProductScheduleGui\Communication\Plugin\ProductManagement\ScheduledPriceProductConcreteFormEditTabsExpanderPlugin;
use Spryker\Zed\ProductAlternativeGui\Communication\Plugin\ProductManagement\ProductConcreteEditFormExpanderPlugin;
use Spryker\Zed\ProductAlternativeGui\Communication\Plugin\ProductManagement\ProductConcreteFormEditDataProviderExpanderPlugin;
use Spryker\Zed\ProductAlternativeGui\Communication\Plugin\ProductManagement\ProductConcreteFormEditTabsExpanderPlugin;
use Spryker\Zed\ProductAlternativeGui\Communication\Plugin\ProductManagement\ProductFormTransferMapperExpanderPlugin;
use Spryker\Zed\ProductApprovalGui\Communication\Plugin\ProductManagement\ProductApprovalProductAbstractEditViewExpanderPlugin;
use Spryker\Zed\ProductApprovalGui\Communication\Plugin\ProductManagement\ProductApprovalProductTableActionExpanderPlugin;
use Spryker\Zed\ProductApprovalGui\Communication\Plugin\ProductManagement\ProductApprovalProductTableConfigurationExpanderPlugin;
use Spryker\Zed\ProductApprovalGui\Communication\Plugin\ProductManagement\ProductApprovalProductTableDataBulkExpanderPlugin;
use Spryker\Zed\ProductApprovalGui\Communication\Plugin\ProductManagement\ProductApprovalProductTableQueryCriteriaExpanderPlugin;
use Spryker\Zed\ProductConfigurationGui\Communication\Plugin\ProductManagement\ProductConfigurationProductTableDataBulkExpanderPlugin;
use Spryker\Zed\ProductDiscontinuedGui\Communication\Plugin\DiscontinuedNotesProductFormTransferMapperExpanderPlugin;
use Spryker\Zed\ProductDiscontinuedGui\Communication\Plugin\DiscontinuedProductConcreteEditFormExpanderPlugin;
use Spryker\Zed\ProductDiscontinuedGui\Communication\Plugin\DiscontinueProductConcreteFormEditDataProviderExpanderPlugin;
use Spryker\Zed\ProductDiscontinuedGui\Communication\Plugin\DiscontinueProductConcreteFormEditTabsExpanderPlugin;
use Spryker\Zed\ProductManagement\ProductManagementDependencyProvider as SprykerProductManagementDependencyProvider;
use Spryker\Zed\Store\Communication\Plugin\Form\StoreRelationToggleFormTypePlugin;
use SprykerEco\Zed\ProductManagementAi\Communication\Plugin\ProductManagement\ImageAltTextProductAbstractFormExpanderPlugin;
use SprykerEco\Zed\ProductManagementAi\Communication\Plugin\ProductManagement\ImageAltTextProductConcreteEditFormExpanderPlugin;
use SprykerEco\Zed\ProductManagementAi\Communication\Plugin\ProductManagement\ImageAltTextProductConcreteFormExpanderPlugin;
use SprykerEco\Zed\ProductManagementAi\Communication\Plugin\ProductManagement\ProductCategoryAbstractFormExpanderPlugin;

class ProductManagementDependencyProvider extends SprykerProductManagementDependencyProvider
{
    /**
     * @return array<\Spryker\Zed\ProductManagement\Communication\Plugin\ProductAbstractViewPluginInterface>
     */
    protected function getProductAbstractViewPlugins(): array
    {
        return [
            new CmsBlockProductAbstractBlockListViewPlugin(),
        ];
    }

    /**
     * @return \Spryker\Zed\Kernel\Communication\Form\FormTypeInterface
     */
    protected function getStoreRelationFormTypePlugin(): FormTypeInterface
    {
        return new StoreRelationToggleFormTypePlugin();
    }

    /**
     * @param \Spryker\Zed\Kernel\Container $container
     *
     * @return \Spryker\Zed\Kernel\Communication\Form\FormTypeInterface
     */
    protected function createMoneyFormTypePlugin(Container $container): FormTypeInterface
    {
        return new MoneyFormTypePlugin();
    }

    /**
     * @return array<\Spryker\Zed\ProductManagementExtension\Dependency\Plugin\ProductConcreteFormEditTabsExpanderPluginInterface>
     */
    protected function getProductConcreteFormEditTabsExpanderPlugins(): array
    {
        return [
            new DiscontinueProductConcreteFormEditTabsExpanderPlugin(), #ProductDiscontinuedFeature
            new ProductConcreteFormEditTabsExpanderPlugin(), #ProductAlternativeFeature
            new ScheduledPriceProductConcreteFormEditTabsExpanderPlugin(),
        ];
    }

    /**
     * @return array<\Spryker\Zed\ProductManagementExtension\Dependency\Plugin\ProductConcreteEditFormExpanderPluginInterface>
     */
    protected function getProductConcreteEditFormExpanderPlugins(): array
    {
        return [
            new DiscontinuedProductConcreteEditFormExpanderPlugin(), #ProductDiscontinuedFeature
            new ProductConcreteEditFormExpanderPlugin(), #ProductAlternativeFeature
            new ImageAltTextProductConcreteEditFormExpanderPlugin(),
        ];
    }

    /**
     * @return array<\Spryker\Zed\ProductManagementExtension\Dependency\Plugin\ProductConcreteFormEditDataProviderExpanderPluginInterface>
     */
    protected function getProductConcreteFormEditDataProviderExpanderPlugins(): array
    {
        return [
            new DiscontinueProductConcreteFormEditDataProviderExpanderPlugin(), #ProductDiscontinuedFeature
            new ProductConcreteFormEditDataProviderExpanderPlugin(), #ProductAlternativeFeature
        ];
    }

    /**
     * @return array<\Spryker\Zed\ProductManagementExtension\Dependency\Plugin\ProductFormTransferMapperExpanderPluginInterface>
     */
    protected function getProductFormTransferMapperExpanderPlugins(): array
    {
        return [
            new ProductFormTransferMapperExpanderPlugin(), #ProductAlternativeFeature
            new DiscontinuedNotesProductFormTransferMapperExpanderPlugin(), #ProductDiscontinuedFeature
        ];
    }

    /**
     * @return array<\Spryker\Zed\ProductManagementExtension\Dependency\Plugin\ProductAbstractFormExpanderPluginInterface>
     */
    protected function getProductAbstractFormExpanderPlugins(): array
    {
        return [
            new MerchantRelationshipProductAbstractFormExpanderPlugin(),
            new ProductCategoryAbstractFormExpanderPlugin(),
            new ImageAltTextProductAbstractFormExpanderPlugin(),
        ];
    }

    /**
     * @return array<\Spryker\Zed\ProductManagementExtension\Dependency\Plugin\ProductConcreteFormExpanderPluginInterface>
     */
    protected function getProductConcreteFormExpanderPlugins(): array
    {
        return [
            new MerchantRelationshipProductConcreteFormExpanderPlugin(),
            new ImageAltTextProductConcreteFormExpanderPlugin(),
        ];
    }

    /**
     * @return array<\Spryker\Zed\ProductManagementExtension\Dependency\Plugin\ProductAbstractFormEditTabsExpanderPluginInterface>
     */
    protected function getProductAbstractFormEditTabsExpanderPlugins(): array
    {
        return [
            new ScheduledPriceProductAbstractFormEditTabsExpanderPlugin(),
        ];
    }

    /**
     * @return array<\Spryker\Zed\ProductManagementExtension\Dependency\Plugin\ProductAbstractEditViewExpanderPluginInterface>
     */
    protected function getProductAbstractEditViewExpanderPlugins(): array
    {
        return [
            new ScheduledPriceProductAbstractEditViewExpanderPlugin(),
            new ProductApprovalProductAbstractEditViewExpanderPlugin(),
            new MerchantProductProductAbstractEditViewExpanderPlugin(),
        ];
    }

    /**
     * @return array<\Spryker\Zed\ProductManagementExtension\Dependency\Plugin\ProductConcreteEditViewExpanderPluginInterface>
     */
    protected function getProductConcreteEditViewExpanderPlugins(): array
    {
        return [
            new ScheduledPriceProductConcreteEditViewExpanderPlugin(),
        ];
    }

    /**
     * @return array<\Spryker\Zed\ProductManagementExtension\Dependency\Plugin\ProductAbstractViewActionViewDataExpanderPluginInterface>
     */
    protected function getProductAbstractViewActionViewDataExpanderPlugins(): array
    {
        return [
            new MerchantProductProductAbstractViewActionViewDataExpanderPlugin(),
        ];
    }

    /**
     * @return array<\Spryker\Zed\ProductManagementExtension\Dependency\Plugin\ProductTableQueryCriteriaExpanderPluginInterface>
     */
    protected function getProductTableQueryCriteriaExpanderPluginInterfaces(): array
    {
        return [
            new MerchantProductProductTableQueryCriteriaExpanderPlugin(),
            new ProductApprovalProductTableQueryCriteriaExpanderPlugin(),
        ];
    }

    /**
     * @return array<\Spryker\Zed\ProductManagementExtension\Dependency\Plugin\ProductAbstractListActionViewDataExpanderPluginInterface>
     */
    protected function getProductAbstractListActionViewDataExpanderPlugins(): array
    {
        return [
            new MerchantProductAbstractListActionViewDataExpanderPlugin(),
        ];
    }

    /**
     * @return array<\Spryker\Zed\ProductManagementExtension\Dependency\Plugin\ProductTableConfigurationExpanderPluginInterface>
     */
    protected function getProductTableConfigurationExpanderPlugins(): array
    {
        return [
            new ProductApprovalProductTableConfigurationExpanderPlugin(),
        ];
    }

    /**
     * @return array<\Spryker\Zed\ProductManagementExtension\Dependency\Plugin\ProductTableDataBulkExpanderPluginInterface>
     */
    protected function getProductTableDataBulkExpanderPlugins(): array
    {
        return [
            new ProductApprovalProductTableDataBulkExpanderPlugin(),
            new ProductConfigurationProductTableDataBulkExpanderPlugin(),
        ];
    }

    /**
     * @return array<\Spryker\Zed\ProductManagementExtension\Dependency\Plugin\ProductTableActionExpanderPluginInterface>
     */
    protected function getProductTableActionExpanderPlugins(): array
    {
        return [
            new ProductApprovalProductTableActionExpanderPlugin(),
        ];
    }
}
