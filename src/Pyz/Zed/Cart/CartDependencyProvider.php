<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\Cart;

use Spryker\Zed\AvailabilityCartConnector\Communication\Plugin\Cart\CheckAvailabilityPlugin;
use Spryker\Zed\Cart\CartDependencyProvider as SprykerCartDependencyProvider;
use Spryker\Zed\Cart\Communication\Plugin\Cart\GroupKeyWithCartIdentifierItemExpanderPlugin;
use Spryker\Zed\Cart\Communication\Plugin\CleanUpItemsPreReloadPlugin;
use Spryker\Zed\Cart\Communication\Plugin\SkuGroupKeyPlugin;
use Spryker\Zed\ConfigurableBundle\Communication\Plugin\Cart\CartConfigurableBundlePreReloadPlugin;
use Spryker\Zed\ConfigurableBundleCart\Communication\Plugin\Cart\ConfiguredBundleGroupKeyItemExpanderPlugin;
use Spryker\Zed\ConfigurableBundleCart\Communication\Plugin\Cart\ConfiguredBundleQuantityCartTerminationPlugin;
use Spryker\Zed\ConfigurableBundleCart\Communication\Plugin\Cart\ConfiguredBundleQuantityPerSlotItemExpanderPlugin;
use Spryker\Zed\ConfigurableBundleCart\Communication\Plugin\Cart\ConfiguredBundleQuantityPerSlotPreReloadItemsPlugin;
use Spryker\Zed\ConfigurableBundleCart\Communication\Plugin\Cart\ConfiguredBundleQuantityPostSavePlugin;
use Spryker\Zed\ConfigurableBundleCart\Communication\Plugin\Cart\ConfiguredBundleTemplateSlotCombinationPreCheckPlugin;
use Spryker\Zed\Discount\Communication\Plugin\Cart\DiscountQuoteChangeObserverPlugin;
use Spryker\Zed\DiscountPromotion\Communication\Plugin\Cart\CartGroupPromotionItems;
use Spryker\Zed\DiscountPromotion\Communication\Plugin\Cart\DiscountPromotionCartPreCheckPlugin;
use Spryker\Zed\Kernel\Container;
use Spryker\Zed\Merchant\Communication\Plugin\Cart\MerchantCartPreCheckPlugin;
use Spryker\Zed\MerchantProduct\Communication\Plugin\Cart\MerchantProductCartPreCheckPlugin;
use Spryker\Zed\MerchantProductOption\Communication\Plugin\Cart\MerchantProductOptionCartPreCheckPlugin;
use Spryker\Zed\MerchantShipment\Communication\Plugin\Cart\MerchantShipmentItemExpanderPlugin;
use Spryker\Zed\PaymentCartConnector\Communication\Plugin\Cart\RemovePaymentCartPostSavePlugin;
use Spryker\Zed\PaymentCartConnector\Communication\Plugin\Cart\RemoveQuotePaymentCartItemExpanderPlugin;
use Spryker\Zed\PriceCartConnector\Communication\Plugin\Cart\PriceItemExpanderPlugin;
use Spryker\Zed\PriceCartConnector\Communication\Plugin\Cart\SanitizeSourcePricesQuoteLockPreResetPlugin;
use Spryker\Zed\PriceCartConnector\Communication\Plugin\CartItemPricePlugin;
use Spryker\Zed\PriceCartConnector\Communication\Plugin\CartItemPricePreCheckPlugin;
use Spryker\Zed\PriceCartConnector\Communication\Plugin\FilterItemsWithoutPricePlugin;
use Spryker\Zed\PriceProductSalesOrderAmendment\Communication\Plugin\Cart\OriginalSalesOrderItemPriceItemExpanderPlugin;
use Spryker\Zed\PriceProductSalesOrderAmendment\Communication\Plugin\Cart\ResetOriginalSalesOrderItemUnitPricesPreReloadItemsPlugin;
use Spryker\Zed\ProductApproval\Communication\Plugin\Cart\OrderAmendmentProductApprovalCartPreCheckPlugin;
use Spryker\Zed\ProductApproval\Communication\Plugin\Cart\OrderAmendmentProductApprovalPreReloadItemsPlugin;
use Spryker\Zed\ProductApproval\Communication\Plugin\Cart\ProductApprovalCartPreCheckPlugin;
use Spryker\Zed\ProductApproval\Communication\Plugin\Cart\ProductApprovalPreReloadItemsPlugin;
use Spryker\Zed\ProductBundle\Communication\Plugin\Cart\BundleItemPriceQuoteChangeObserverPlugin;
use Spryker\Zed\ProductBundle\Communication\Plugin\Cart\CartBundleActivePreCheckPlugin;
use Spryker\Zed\ProductBundle\Communication\Plugin\Cart\CartBundleAvailabilityPreCheckPlugin;
use Spryker\Zed\ProductBundle\Communication\Plugin\Cart\CartBundleItemsPreReloadPlugin;
use Spryker\Zed\ProductBundle\Communication\Plugin\Cart\CartBundlePricesPreCheckPlugin;
use Spryker\Zed\ProductBundle\Communication\Plugin\Cart\CartItemWithBundleGroupKeyExpanderPlugin;
use Spryker\Zed\ProductBundle\Communication\Plugin\Cart\CartPostSaveUpdateBundlesPlugin;
use Spryker\Zed\ProductBundle\Communication\Plugin\Cart\ExpandBundleItemsPlugin;
use Spryker\Zed\ProductBundle\Communication\Plugin\Cart\ExpandBundleItemsWithImagesPlugin;
use Spryker\Zed\ProductBundle\Communication\Plugin\Cart\OrderAmendmentProductBundleAvailabilityCartPreCheckPlugin;
use Spryker\Zed\ProductBundle\Communication\Plugin\Cart\OrderAmendmentProductBundleStatusCartPreCheckPlugin;
use Spryker\Zed\ProductCartConnector\Communication\Plugin\Cart\OrderAmendmentProductExistsCartPreCheckPlugin;
use Spryker\Zed\ProductCartConnector\Communication\Plugin\Cart\OrderAmendmentRemoveInactiveItemsPreReloadPlugin;
use Spryker\Zed\ProductCartConnector\Communication\Plugin\ProductCartPlugin;
use Spryker\Zed\ProductCartConnector\Communication\Plugin\ProductExistsCartPreCheckPlugin;
use Spryker\Zed\ProductCartConnector\Communication\Plugin\ProductUrlItemExpanderPlugin;
use Spryker\Zed\ProductCartConnector\Communication\Plugin\RemoveInactiveItemsPreReloadPlugin;
use Spryker\Zed\ProductConfigurationCart\Communication\Plugin\Cart\ProductConfigurationGroupKeyItemExpanderPlugin;
use Spryker\Zed\ProductDiscontinued\Communication\Plugin\Cart\OrderAmendmentProductDiscontinuedCartPreCheckPlugin;
use Spryker\Zed\ProductDiscontinued\Communication\Plugin\Cart\ProductDiscontinuedCartPreCheckPlugin;
use Spryker\Zed\ProductImageCartConnector\Communication\Plugin\Cart\ProductImageItemExpanderPlugin;
use Spryker\Zed\ProductList\Communication\Plugin\CartExtension\ProductListRestrictedItemsPreCheckPlugin;
use Spryker\Zed\ProductList\Communication\Plugin\CartExtension\RemoveRestrictedItemsPreReloadPlugin;
use Spryker\Zed\ProductMeasurementUnit\Communication\Plugin\Cart\DefaultQuantitySalesUnitItemExpanderPlugin;
use Spryker\Zed\ProductMeasurementUnit\Communication\Plugin\Cart\ProductMeasurementSalesUnitCartPreCheckPlugin;
use Spryker\Zed\ProductMeasurementUnit\Communication\Plugin\Cart\QuantitySalesUnitGroupKeyItemExpanderPlugin;
use Spryker\Zed\ProductMeasurementUnit\Communication\Plugin\Cart\QuantitySalesUnitItemExpanderPlugin;
use Spryker\Zed\ProductMeasurementUnit\Communication\Plugin\Cart\QuantitySalesUnitValuePostSavePlugin;
use Spryker\Zed\ProductOffer\Communication\Plugin\Cart\FilterInactiveProductOfferPreReloadItemsPlugin;
use Spryker\Zed\ProductOffer\Communication\Plugin\Cart\OrderAmendmentFilterInactiveProductOfferPreReloadItemsPlugin;
use Spryker\Zed\ProductOffer\Communication\Plugin\Cart\OrderAmendmentProductOfferCartPreCheckPlugin;
use Spryker\Zed\ProductOffer\Communication\Plugin\Cart\ProductOfferCartPreCheckPlugin;
use Spryker\Zed\ProductOffer\Communication\Plugin\Cart\ProductOfferGroupKeyItemExpanderPlugin;
use Spryker\Zed\ProductOptionCartConnector\Communication\Plugin\Cart\CartItemOptionPreCheckPlugin;
use Spryker\Zed\ProductOptionCartConnector\Communication\Plugin\CartItemGroupKeyOptionPlugin;
use Spryker\Zed\ProductOptionCartConnector\Communication\Plugin\CartItemProductOptionPlugin;
use Spryker\Zed\ProductOptionCartConnector\Communication\Plugin\ChangeProductOptionQuantityPlugin;
use Spryker\Zed\ProductOptionCartConnector\Communication\Plugin\ProductOptionValuePriceExistsCartPreCheckPlugin;
use Spryker\Zed\ProductPackagingUnit\Communication\Plugin\Cart\AmountAvailabilityCartPreCheckPlugin;
use Spryker\Zed\ProductPackagingUnit\Communication\Plugin\Cart\AmountGroupKeyItemExpanderPlugin;
use Spryker\Zed\ProductPackagingUnit\Communication\Plugin\Cart\AmountRestrictionCartPreCheckPlugin;
use Spryker\Zed\ProductPackagingUnit\Communication\Plugin\Cart\AmountSalesUnitItemExpanderPlugin;
use Spryker\Zed\ProductPackagingUnit\Communication\Plugin\Cart\AmountSalesUnitValuePostSavePlugin;
use Spryker\Zed\ProductPackagingUnit\Communication\Plugin\Cart\CustomAmountPriceItemExpanderPlugin;
use Spryker\Zed\ProductPackagingUnit\Communication\Plugin\Cart\ProductPackagingUnitCartAddItemStrategyPlugin;
use Spryker\Zed\ProductPackagingUnit\Communication\Plugin\Cart\ProductPackagingUnitCartPreCheckPlugin;
use Spryker\Zed\ProductPackagingUnit\Communication\Plugin\Cart\ProductPackagingUnitCartRemoveItemStrategyPlugin;
use Spryker\Zed\ProductPackagingUnit\Communication\Plugin\Cart\ProductPackagingUnitItemExpanderPlugin;
use Spryker\Zed\ProductQuantity\Communication\Plugin\Cart\CartChangeTransferQuantityNormalizerPlugin;
use Spryker\Zed\ProductQuantity\Communication\Plugin\Cart\ProductQuantityRestrictionCartPreCheckPlugin;
use Spryker\Zed\ProductQuantity\Communication\Plugin\CartExtension\ProductQuantityRestrictionCartRemovalPreCheckPlugin;
use Spryker\Zed\QuoteApproval\Communication\Plugin\Cart\SanitizeQuoteApprovalQuoteLockPreResetPlugin;
use Spryker\Zed\QuoteRequest\Communication\Plugin\Cart\SanitizeQuoteRequestQuoteLockPreResetPlugin;
use Spryker\Zed\SalesMerchantCommission\Communication\Plugin\Cart\SanitizeMerchantCommissionPreReloadPlugin;
use Spryker\Zed\SalesOrderAmendment\Communication\Plugin\Cart\OrderAmendmentCartPreCheckPlugin;
use Spryker\Zed\SalesOrderAmendment\Communication\Plugin\Cart\ResetAmendmentOrderReferencePreReloadItemsPlugin;
use Spryker\Zed\SalesOrderThreshold\Communication\Plugin\Cart\AddThresholdMessagesCartPostReloadItemsPlugin;
use Spryker\Zed\SalesOrderThreshold\Communication\Plugin\Cart\SalesOrderThresholdCartTerminationPlugin;
use Spryker\Zed\SalesQuantity\Communication\Plugin\Cart\IsQuantitySplittableItemExpanderPlugin;
use Spryker\Zed\ShipmentCartConnector\Communication\Plugin\Cart\CartShipmentCartOperationPostSavePlugin;
use Spryker\Zed\ShipmentCartConnector\Communication\Plugin\Cart\CartShipmentPreCheckPlugin;
use Spryker\Zed\ShipmentCartConnector\Communication\Plugin\Cart\SanitizeCartShipmentItemExpanderPlugin;

class CartDependencyProvider extends SprykerCartDependencyProvider
{
    /**
     * @param \Spryker\Zed\Kernel\Container $container
     *
     * @return array<\Spryker\Zed\CartExtension\Dependency\Plugin\ItemExpanderPluginInterface>
     */
    protected function getExpanderPlugins(Container $container): array // phpcs:ignore SlevomatCodingStandard.Functions.UnusedParameter
    {
        return [
            new ProductCartPlugin(),
            new IsQuantitySplittableItemExpanderPlugin(),
            new CartItemPricePlugin(),
            new CartItemProductOptionPlugin(),
            new ExpandBundleItemsPlugin(),
            new ExpandBundleItemsWithImagesPlugin(),
            new SkuGroupKeyPlugin(),
            new DefaultQuantitySalesUnitItemExpanderPlugin(),
            new QuantitySalesUnitGroupKeyItemExpanderPlugin(),
            new CartItemGroupKeyOptionPlugin(),
            new CartItemWithBundleGroupKeyExpanderPlugin(),
            new ProductImageItemExpanderPlugin(),
            new CartGroupPromotionItems(),
            new QuantitySalesUnitItemExpanderPlugin(),
            new ProductPackagingUnitItemExpanderPlugin(), #ProductPackagingUnit
            new AmountGroupKeyItemExpanderPlugin(), #ProductPackagingUnit
            new AmountSalesUnitItemExpanderPlugin(), #ProductPackagingUnit
            new CustomAmountPriceItemExpanderPlugin(), #ProductPackagingUnit
            new ConfiguredBundleQuantityPerSlotItemExpanderPlugin(),
            new ConfiguredBundleGroupKeyItemExpanderPlugin(),
            new ProductUrlItemExpanderPlugin(),
            new SanitizeCartShipmentItemExpanderPlugin(),
            new ProductOfferGroupKeyItemExpanderPlugin(),
            new MerchantShipmentItemExpanderPlugin(),
            new GroupKeyWithCartIdentifierItemExpanderPlugin(),
            new ProductConfigurationGroupKeyItemExpanderPlugin(),
            new RemoveQuotePaymentCartItemExpanderPlugin(),
        ];
    }

    /**
     * @param \Spryker\Zed\Kernel\Container $container
     *
     * @return list<\Spryker\Zed\CartExtension\Dependency\Plugin\ItemExpanderPluginInterface>
     */
    protected function getExpanderPluginsForOrderAmendment(Container $container): array // phpcs:ignore SlevomatCodingStandard.Functions.UnusedParameter
    {
        return [
            new ProductCartPlugin(),
            new IsQuantitySplittableItemExpanderPlugin(),
            new PriceItemExpanderPlugin(),
            new OriginalSalesOrderItemPriceItemExpanderPlugin(),
            new CartItemProductOptionPlugin(),
            new ExpandBundleItemsPlugin(),
            new ExpandBundleItemsWithImagesPlugin(),
            new SkuGroupKeyPlugin(),
            new DefaultQuantitySalesUnitItemExpanderPlugin(),
            new QuantitySalesUnitGroupKeyItemExpanderPlugin(),
            new CartItemGroupKeyOptionPlugin(),
            new CartItemWithBundleGroupKeyExpanderPlugin(),
            new ProductImageItemExpanderPlugin(),
            new CartGroupPromotionItems(),
            new QuantitySalesUnitItemExpanderPlugin(),
            new ProductPackagingUnitItemExpanderPlugin(), #ProductPackagingUnit
            new AmountGroupKeyItemExpanderPlugin(), #ProductPackagingUnit
            new AmountSalesUnitItemExpanderPlugin(), #ProductPackagingUnit
            new CustomAmountPriceItemExpanderPlugin(), #ProductPackagingUnitFeature
            new ConfiguredBundleQuantityPerSlotItemExpanderPlugin(),
            new ConfiguredBundleGroupKeyItemExpanderPlugin(),
            new ProductOfferGroupKeyItemExpanderPlugin(),
            new ProductUrlItemExpanderPlugin(),
            new SanitizeCartShipmentItemExpanderPlugin(),
            new ProductConfigurationGroupKeyItemExpanderPlugin(),
            new MerchantShipmentItemExpanderPlugin(),
            new GroupKeyWithCartIdentifierItemExpanderPlugin(),
            new RemoveQuotePaymentCartItemExpanderPlugin(),
        ];
    }

    /**
     * @param \Spryker\Zed\Kernel\Container $container
     *
     * @return array<\Spryker\Zed\CartExtension\Dependency\Plugin\CartChangeTransferNormalizerPluginInterface>
     */
    protected function getCartBeforePreCheckNormalizerPlugins(Container $container): array // phpcs:ignore SlevomatCodingStandard.Functions.UnusedParameter
    {
        return [
            new CartChangeTransferQuantityNormalizerPlugin(),
        ];
    }

    /**
     * @param \Spryker\Zed\Kernel\Container $container
     *
     * @return array<\Spryker\Zed\CartExtension\Dependency\Plugin\CartPreCheckPluginInterface>
     */
    protected function getCartPreCheckPlugins(Container $container): array // phpcs:ignore SlevomatCodingStandard.Functions.UnusedParameter
    {
        return [
            new ProductExistsCartPreCheckPlugin(),
            new CheckAvailabilityPlugin(),
            new CartItemPricePreCheckPlugin(),
            new CartBundlePricesPreCheckPlugin(),
            new CartItemOptionPreCheckPlugin(),
            new ProductOptionValuePriceExistsCartPreCheckPlugin(),
            new CartBundleAvailabilityPreCheckPlugin(),
            new CartBundleActivePreCheckPlugin(),
            new CartShipmentPreCheckPlugin(),
            new ProductQuantityRestrictionCartPreCheckPlugin(),
            new ProductListRestrictedItemsPreCheckPlugin(),
            new ProductDiscontinuedCartPreCheckPlugin(), #ProductDiscontinuedFeature
            new ProductPackagingUnitCartPreCheckPlugin(),
            new AmountAvailabilityCartPreCheckPlugin(), #ProductPackagingUnit
            new AmountRestrictionCartPreCheckPlugin(), #ProductPackagingUnit
            new ConfiguredBundleTemplateSlotCombinationPreCheckPlugin(),
            new ProductMeasurementSalesUnitCartPreCheckPlugin(),
            new DiscountPromotionCartPreCheckPlugin(),
            new MerchantProductCartPreCheckPlugin(),
            new ProductOfferCartPreCheckPlugin(),
            new MerchantProductOptionCartPreCheckPlugin(),
            new ProductApprovalCartPreCheckPlugin(),
        ];
    }

    /**
     * @return list<\Spryker\Zed\CartExtension\Dependency\Plugin\CartPreCheckPluginInterface>
     */
    protected function getCartPreCheckPluginsForOrderAmendment(): array
    {
        return [
            new CheckAvailabilityPlugin(),
            new CartBundlePricesPreCheckPlugin(),
            new CartItemOptionPreCheckPlugin(),
            new ProductOptionValuePriceExistsCartPreCheckPlugin(),
            new CartShipmentPreCheckPlugin(),
            new ProductQuantityRestrictionCartPreCheckPlugin(),
            new ProductListRestrictedItemsPreCheckPlugin(),
            new ProductPackagingUnitCartPreCheckPlugin(),
            new AmountAvailabilityCartPreCheckPlugin(), #ProductPackagingUnit
            new AmountRestrictionCartPreCheckPlugin(), #ProductPackagingUnit
            new ConfiguredBundleTemplateSlotCombinationPreCheckPlugin(),
            new MerchantCartPreCheckPlugin(),
            new ProductMeasurementSalesUnitCartPreCheckPlugin(),
            new DiscountPromotionCartPreCheckPlugin(),
            new MerchantProductCartPreCheckPlugin(),
            new MerchantProductOptionCartPreCheckPlugin(),
            new OrderAmendmentProductExistsCartPreCheckPlugin(),
            new OrderAmendmentProductBundleAvailabilityCartPreCheckPlugin(),
            new OrderAmendmentProductBundleStatusCartPreCheckPlugin(),
            new OrderAmendmentProductDiscontinuedCartPreCheckPlugin(), #ProductDiscontinuedFeature
            new OrderAmendmentProductOfferCartPreCheckPlugin(),
            new OrderAmendmentProductApprovalCartPreCheckPlugin(),
            new OrderAmendmentCartPreCheckPlugin(),
        ];
    }

    /**
     * @param \Spryker\Zed\Kernel\Container $container
     *
     * @return array<\Spryker\Zed\CartExtension\Dependency\Plugin\CartOperationPostSavePluginInterface>
     */
    protected function getPostSavePlugins(Container $container): array // phpcs:ignore SlevomatCodingStandard.Functions.UnusedParameter
    {
        return [
            new ChangeProductOptionQuantityPlugin(),
            new CartPostSaveUpdateBundlesPlugin(),
            new RemovePaymentCartPostSavePlugin(),
            new QuantitySalesUnitValuePostSavePlugin(),
            new AmountSalesUnitValuePostSavePlugin(), #ProductPackagingUnit
            new ConfiguredBundleQuantityPostSavePlugin(),
            new CartShipmentCartOperationPostSavePlugin(),
        ];
    }

    /**
     * @param \Spryker\Zed\Kernel\Container $container
     *
     * @return array<\Spryker\Zed\CartExtension\Dependency\Plugin\PreReloadItemsPluginInterface>
     */
    protected function getPreReloadPlugins(Container $container): array // phpcs:ignore SlevomatCodingStandard.Functions.UnusedParameter
    {
        return [
            new CartConfigurableBundlePreReloadPlugin(),
            new CartBundleItemsPreReloadPlugin(),
            new RemoveInactiveItemsPreReloadPlugin(),
            new RemoveRestrictedItemsPreReloadPlugin(),
            new CleanUpItemsPreReloadPlugin(),
            new FilterItemsWithoutPricePlugin(),
            new ConfiguredBundleQuantityPerSlotPreReloadItemsPlugin(),
            new FilterInactiveProductOfferPreReloadItemsPlugin(),
            new ProductApprovalPreReloadItemsPlugin(),
            new SanitizeMerchantCommissionPreReloadPlugin(),
            new ResetAmendmentOrderReferencePreReloadItemsPlugin(),
            new ResetOriginalSalesOrderItemUnitPricesPreReloadItemsPlugin(),
        ];
    }

    /**
     * @param \Spryker\Zed\Kernel\Container $container
     *
     * @return list<\Spryker\Zed\CartExtension\Dependency\Plugin\PreReloadItemsPluginInterface>
     */
    protected function getPreReloadPluginsForOrderAmendment(Container $container): array // phpcs:ignore SlevomatCodingStandard.Functions.UnusedParameter
    {
        return [
            new CartConfigurableBundlePreReloadPlugin(),
            new CartBundleItemsPreReloadPlugin(),
            new RemoveRestrictedItemsPreReloadPlugin(),
            new CleanUpItemsPreReloadPlugin(),
            new ConfiguredBundleQuantityPerSlotPreReloadItemsPlugin(),
            new SanitizeMerchantCommissionPreReloadPlugin(),
            new OrderAmendmentRemoveInactiveItemsPreReloadPlugin(),
            new OrderAmendmentFilterInactiveProductOfferPreReloadItemsPlugin(),
            new OrderAmendmentProductApprovalPreReloadItemsPlugin(),
        ];
    }

    /**
     * @param \Spryker\Zed\Kernel\Container $container
     *
     * @return array<\Spryker\Zed\CartExtension\Dependency\Plugin\PostReloadItemsPluginInterface>
     */
    protected function getPostReloadItemsPlugins(Container $container): array // phpcs:ignore SlevomatCodingStandard.Functions.UnusedParameter
    {
        return [
            new AddThresholdMessagesCartPostReloadItemsPlugin(), #SalesOrderThresholdFeature
        ];
    }

    /**
     * @param \Spryker\Zed\Kernel\Container $container
     *
     * @return array<\Spryker\Zed\CartExtension\Dependency\Plugin\CartTerminationPluginInterface>
     */
    protected function getTerminationPlugins(Container $container): array // phpcs:ignore SlevomatCodingStandard.Functions.UnusedParameter
    {
        return [
            new ConfiguredBundleQuantityCartTerminationPlugin(),
            new SalesOrderThresholdCartTerminationPlugin(),
        ];
    }

    /**
     * @param \Spryker\Zed\Kernel\Container $container
     *
     * @return array<\Spryker\Zed\CartExtension\Dependency\Plugin\CartRemovalPreCheckPluginInterface>
     */
    protected function getCartRemovalPreCheckPlugins(Container $container): array // phpcs:ignore SlevomatCodingStandard.Functions.UnusedParameter
    {
        return [
            new ProductQuantityRestrictionCartRemovalPreCheckPlugin(),
        ];
    }

    /**
     * @param \Spryker\Zed\Kernel\Container $container
     *
     * @return array<\Spryker\Zed\CartExtension\Dependency\Plugin\QuoteChangeObserverPluginInterface>
     */
    protected function getQuoteChangeObserverPlugins(Container $container): array // phpcs:ignore SlevomatCodingStandard.Functions.UnusedParameter
    {
        return [
            new DiscountQuoteChangeObserverPlugin(),
            new BundleItemPriceQuoteChangeObserverPlugin(),
        ];
    }

    /**
     * @param \Spryker\Zed\Kernel\Container $container
     *
     * @return array<\Spryker\Zed\CartExtension\Dependency\Plugin\CartOperationStrategyPluginInterface>
     */
    protected function getCartAddItemStrategyPlugins(Container $container): array // phpcs:ignore SlevomatCodingStandard.Functions.UnusedParameter
    {
        return [
            new ProductPackagingUnitCartAddItemStrategyPlugin(),
        ];
    }

    /**
     * @param \Spryker\Zed\Kernel\Container $container
     *
     * @return array<\Spryker\Zed\CartExtension\Dependency\Plugin\CartOperationStrategyPluginInterface>
     */
    protected function getCartRemoveItemStrategyPlugins(Container $container): array // phpcs:ignore SlevomatCodingStandard.Functions.UnusedParameter
    {
        return [
            new ProductPackagingUnitCartRemoveItemStrategyPlugin(),
        ];
    }

    /**
     * @return array<\Spryker\Zed\CartExtension\Dependency\Plugin\QuoteLockPreResetPluginInterface>
     */
    protected function getQuoteLockPreResetPlugins(): array
    {
        return [
            new SanitizeQuoteRequestQuoteLockPreResetPlugin(),
            new SanitizeQuoteApprovalQuoteLockPreResetPlugin(),
            new SanitizeSourcePricesQuoteLockPreResetPlugin(),
        ];
    }
}
