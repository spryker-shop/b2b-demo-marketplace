<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\Product;

use Spryker\Zed\Category\Communication\Plugin\Product\CategoryExistsProductAbstractCollectionCreateValidatorPlugin;
use Spryker\Zed\Category\Communication\Plugin\Product\CategoryExistsProductAbstractCollectionUpdateValidatorPlugin;
use Spryker\Zed\Kernel\Container;
use Spryker\Zed\MerchantProduct\Communication\Plugin\Product\MerchantProductProductAbstractAfterUpdatePlugin;
use Spryker\Zed\MerchantProduct\Communication\Plugin\Product\MerchantProductProductAbstractExpanderPlugin;
use Spryker\Zed\MerchantProduct\Communication\Plugin\Product\MerchantProductProductAbstractPostCreatePlugin;
use Spryker\Zed\MerchantProductApproval\Communication\Plugin\Product\MerchantProductApprovalProductAbstractPreCreatePlugin;
use Spryker\Zed\MerchantProductOffer\Communication\Plugin\Product\MerchantProductOfferProductConcreteExpanderPlugin;
use Spryker\Zed\PriceProduct\Communication\Plugin\Product\PriceProductAbstractCollectionCreateValidatorPlugin;
use Spryker\Zed\PriceProduct\Communication\Plugin\Product\PriceProductAbstractPostCreatePlugin;
use Spryker\Zed\PriceProduct\Communication\Plugin\Product\PriceProductConcreteCollectionCreateValidatorPlugin;
use Spryker\Zed\PriceProduct\Communication\Plugin\Product\PriceProductConcreteCollectionUpdateValidatorPlugin;
use Spryker\Zed\PriceProduct\Communication\Plugin\Product\PriceProductConcreteMergerPlugin;
use Spryker\Zed\PriceProduct\Communication\Plugin\Product\PriceProductProductAbstractExpanderPlugin;
use Spryker\Zed\PriceProduct\Communication\Plugin\Product\PriceProductProductConcreteExpanderPlugin;
use Spryker\Zed\PriceProduct\Communication\Plugin\ProductAbstract\PriceProductAbstractAfterUpdatePlugin;
use Spryker\Zed\PriceProduct\Communication\Plugin\ProductConcrete\PriceProductConcreteAfterCreatePlugin;
use Spryker\Zed\PriceProduct\Communication\Plugin\ProductConcrete\PriceProductConcreteAfterUpdatePlugin;
use Spryker\Zed\Product\ProductDependencyProvider as SprykerProductDependencyProvider;
use Spryker\Zed\ProductAlternativeGui\Communication\Plugin\Product\ProductConcretePluginUpdate as ProductAlternativeGuiProductConcretePluginUpdate;
use Spryker\Zed\ProductApproval\Communication\Plugin\Product\ApprovalStatusProductConcreteMergerPlugin;
use Spryker\Zed\ProductApproval\Communication\Plugin\Product\ProductApprovalProductAbstractPreCreatePlugin;
use Spryker\Zed\ProductAttachment\Communication\Plugin\Product\ProductAttachmentProductAbstractAfterUpdatePlugin;
use Spryker\Zed\ProductAttachment\Communication\Plugin\Product\ProductAttachmentProductAbstractPostCreatePlugin;
use Spryker\Zed\ProductAttribute\Communication\Plugin\Product\SuperAttributeProductConcreteExpanderPlugin;
use Spryker\Zed\ProductBundle\Communication\Plugin\Product\ProductBundleDeactivatorProductConcreteAfterUpdatePlugin;
use Spryker\Zed\ProductBundle\Communication\Plugin\Product\ProductBundleProductConcreteAfterCreatePlugin;
use Spryker\Zed\ProductBundle\Communication\Plugin\Product\ProductBundleProductConcreteAfterUpdatePlugin;
use Spryker\Zed\ProductBundle\Communication\Plugin\Product\ProductBundleProductConcreteCollectionCreateValidatorPlugin;
use Spryker\Zed\ProductBundle\Communication\Plugin\Product\ProductBundleProductConcreteCollectionUpdateValidatorPlugin;
use Spryker\Zed\ProductBundle\Communication\Plugin\Product\ProductBundleProductConcreteExpanderPlugin;
use Spryker\Zed\ProductCategory\Communication\Plugin\Product\ProductCategoryAbstractCollectionExpanderPlugin;
use Spryker\Zed\ProductCategory\Communication\Plugin\Product\ProductCategoryProductAbstractAfterUpdatePlugin;
use Spryker\Zed\ProductCategory\Communication\Plugin\Product\ProductCategoryProductAbstractPostCreatePlugin;
use Spryker\Zed\ProductCategory\Communication\Plugin\Product\ProductConcreteCategoriesExpanderPlugin;
use Spryker\Zed\ProductDiscontinued\Communication\Plugin\SaveDiscontinuedNotesProductConcretePluginUpdate;
use Spryker\Zed\ProductDiscontinuedProductBundleConnector\Communication\Plugin\Product\DiscontinuedProductConcreteAfterCreatePlugin;
use Spryker\Zed\ProductDiscontinuedProductBundleConnector\Communication\Plugin\Product\DiscontinuedProductConcreteAfterUpdatePlugin;
use Spryker\Zed\ProductImage\Communication\Plugin\Product\ImageSetProductAbstractPostCreatePlugin;
use Spryker\Zed\ProductImage\Communication\Plugin\Product\ImageSetProductConcreteMergerPlugin;
use Spryker\Zed\ProductImage\Communication\Plugin\Product\ProductImageAbstractCollectionExpanderPlugin;
use Spryker\Zed\ProductImage\Communication\Plugin\Product\ProductImageProductAbstractExpanderPlugin;
use Spryker\Zed\ProductImage\Communication\Plugin\Product\ProductImageProductConcreteExpanderPlugin;
use Spryker\Zed\ProductImage\Communication\Plugin\Product\ProductImageSetExistsProductConcreteCollectionCreateValidatorPlugin;
use Spryker\Zed\ProductImage\Communication\Plugin\Product\ProductImageSetExistsProductConcreteCollectionUpdateValidatorPlugin;
use Spryker\Zed\ProductImage\Communication\Plugin\ProductAbstractAfterUpdatePlugin as ImageSetProductAbstractAfterUpdatePlugin;
use Spryker\Zed\ProductImage\Communication\Plugin\ProductConcreteAfterCreatePlugin as ImageSetProductConcreteAfterCreatePlugin;
use Spryker\Zed\ProductImage\Communication\Plugin\ProductConcreteAfterUpdatePlugin as ImageSetProductConcreteAfterUpdatePlugin;
use Spryker\Zed\ProductLabel\Communication\Plugin\Product\ProductLabelProductConcreteExpanderPlugin;
use Spryker\Zed\ProductReview\Communication\Plugin\Product\ProductReviewProductConcreteExpanderPlugin;
use Spryker\Zed\ProductSearch\Communication\Plugin\Product\ProductSearchProductConcreteExpanderPlugin;
use Spryker\Zed\ProductSearch\Communication\Plugin\ProductConcrete\ProductSearchProductConcreteAfterCreatePlugin;
use Spryker\Zed\ProductSearch\Communication\Plugin\ProductConcrete\ProductSearchProductConcreteAfterUpdatePlugin;
use Spryker\Zed\ProductValidity\Communication\Plugin\Product\ProductValidityProductConcreteExpanderPlugin;
use Spryker\Zed\ProductValidity\Communication\Plugin\ProductValidityCreatePlugin;
use Spryker\Zed\ProductValidity\Communication\Plugin\ProductValidityUpdatePlugin;
use Spryker\Zed\ShipmentType\Communication\Plugin\Product\ShipmentTypeExistsProductConcreteCollectionCreateValidatorPlugin;
use Spryker\Zed\ShipmentType\Communication\Plugin\Product\ShipmentTypeExistsProductConcreteCollectionUpdateValidatorPlugin;
use Spryker\Zed\Stock\Communication\Plugin\Product\StockExistsProductConcreteCollectionCreateValidatorPlugin;
use Spryker\Zed\Stock\Communication\Plugin\Product\StockExistsProductConcreteCollectionUpdateValidatorPlugin;
use Spryker\Zed\Stock\Communication\Plugin\Product\StockProductConcreteCollectionAfterUpdatePlugin;
use Spryker\Zed\Stock\Communication\Plugin\Product\StockProductConcreteExpanderPlugin;
use Spryker\Zed\Stock\Communication\Plugin\ProductConcreteAfterCreatePlugin as StockProductConcreteAfterCreatePlugin;
use Spryker\Zed\Stock\Communication\Plugin\ProductConcreteAfterUpdatePlugin as StockProductConcreteAfterUpdatePlugin;
use Spryker\Zed\TaxProductConnector\Communication\Plugin\Product\TaxSetExistsProductAbstractCollectionCreateValidatorPlugin;
use Spryker\Zed\TaxProductConnector\Communication\Plugin\Product\TaxSetExistsProductAbstractCollectionUpdateValidatorPlugin;
use Spryker\Zed\TaxProductConnector\Communication\Plugin\Product\TaxSetProductAbstractCollectionExpanderPlugin;
use Spryker\Zed\TaxProductConnector\Communication\Plugin\Product\TaxSetProductAbstractExpanderPlugin;
use Spryker\Zed\TaxProductConnector\Communication\Plugin\Product\TaxSetProductAbstractPostCreatePlugin;
use Spryker\Zed\TaxProductConnector\Communication\Plugin\TaxSetProductAbstractAfterUpdatePlugin;
use SprykerFeature\Zed\SelfServicePortal\Communication\Plugin\Product\ProductClassesProductConcreteExpanderPlugin;
use SprykerFeature\Zed\SelfServicePortal\Communication\Plugin\Product\ProductClassExistsProductConcreteCollectionCreateValidatorPlugin;
use SprykerFeature\Zed\SelfServicePortal\Communication\Plugin\Product\ProductClassExistsProductConcreteCollectionUpdateValidatorPlugin;
use SprykerFeature\Zed\SelfServicePortal\Communication\Plugin\Product\ProductClassProductConcreteAfterUpdatePlugin;
use SprykerFeature\Zed\SelfServicePortal\Communication\Plugin\Product\ProductClassProductConcretePostCreatePlugin;
use SprykerFeature\Zed\SelfServicePortal\Communication\Plugin\Product\ShipmentTypeProductConcreteExpanderPlugin;
use SprykerFeature\Zed\SelfServicePortal\Communication\Plugin\Product\ShipmentTypeProductConcretePostCreatePlugin;
use SprykerFeature\Zed\SelfServicePortal\Communication\Plugin\Product\ShipmentTypeProductConcretePostUpdatePlugin;

class ProductDependencyProvider extends SprykerProductDependencyProvider
{
    /**
     * @return array<\Spryker\Zed\ProductExtension\Dependency\Plugin\ProductAbstractPostCreatePluginInterface>
     */
    protected function getProductAbstractPostCreatePlugins(): array
    {
        return [
            new MerchantProductProductAbstractPostCreatePlugin(),
            new ImageSetProductAbstractPostCreatePlugin(),
            new TaxSetProductAbstractPostCreatePlugin(),
            new PriceProductAbstractPostCreatePlugin(),
            new ProductAttachmentProductAbstractPostCreatePlugin(),
            new ProductCategoryProductAbstractPostCreatePlugin(),
        ];
    }

    /**
     * @return array<\Spryker\Zed\Product\Dependency\Plugin\ProductAbstractPluginCreateInterface>
     */
    protected function getProductAbstractBeforeCreatePlugins(Container $container): array // phpcs:ignore SlevomatCodingStandard.Functions.UnusedParameter
    {
        return [];
    }

    /**
     * @return array<\Spryker\Zed\Product\Dependency\Plugin\ProductAbstractPluginUpdateInterface>
     */
    protected function getProductAbstractBeforeUpdatePlugins(Container $container): array // phpcs:ignore SlevomatCodingStandard.Functions.UnusedParameter
    {
        return [];
    }

    /**
     * @return array<\Spryker\Zed\Product\Dependency\Plugin\ProductAbstractPluginUpdateInterface>
     */
    protected function getProductAbstractAfterUpdatePlugins(Container $container): array // phpcs:ignore SlevomatCodingStandard.Functions.UnusedParameter
    {
        return [
            new ImageSetProductAbstractAfterUpdatePlugin(),
            new TaxSetProductAbstractAfterUpdatePlugin(),
            new PriceProductAbstractAfterUpdatePlugin(),
            new ProductAttachmentProductAbstractAfterUpdatePlugin(),
            new MerchantProductProductAbstractAfterUpdatePlugin(),
            new ProductCategoryProductAbstractAfterUpdatePlugin(),
        ];
    }

    /**
     * @return array<\Spryker\Zed\ProductExtension\Dependency\Plugin\ProductConcreteCreatePluginInterface>
     */
    protected function getProductConcreteAfterCreatePlugins(Container $container): array // phpcs:ignore SlevomatCodingStandard.Functions.UnusedParameter
    {
        return [
            new ImageSetProductConcreteAfterCreatePlugin(),
            new StockProductConcreteAfterCreatePlugin(),
            new PriceProductConcreteAfterCreatePlugin(),
            new ProductSearchProductConcreteAfterCreatePlugin(),
            new ProductBundleProductConcreteAfterCreatePlugin(),
            new ProductValidityCreatePlugin(),
            new DiscontinuedProductConcreteAfterCreatePlugin(),
            new ShipmentTypeProductConcretePostCreatePlugin(),
            new ProductClassProductConcretePostCreatePlugin(),
        ];
    }

    /**
     * @return array<\Spryker\Zed\Product\Dependency\Plugin\ProductConcretePluginUpdateInterface>
     */
    protected function getProductConcreteBeforeUpdatePlugins(Container $container): array // phpcs:ignore SlevomatCodingStandard.Functions.UnusedParameter
    {
        return [
            new ProductAlternativeGuiProductConcretePluginUpdate(), #ProductAlternativeFeature
        ];
    }

    /**
     * @return array<\Spryker\Zed\Product\Dependency\Plugin\ProductConcretePluginUpdateInterface>
     */
    protected function getProductConcreteAfterUpdatePlugins(Container $container): array // phpcs:ignore SlevomatCodingStandard.Functions.UnusedParameter
    {
        return [
            new ImageSetProductConcreteAfterUpdatePlugin(),
            new StockProductConcreteAfterUpdatePlugin(),
            new PriceProductConcreteAfterUpdatePlugin(),
            new ProductSearchProductConcreteAfterUpdatePlugin(),
            new ProductBundleProductConcreteAfterUpdatePlugin(),
            new ProductValidityUpdatePlugin(),
            new SaveDiscontinuedNotesProductConcretePluginUpdate(),
            new DiscontinuedProductConcreteAfterUpdatePlugin(),
            new ProductBundleDeactivatorProductConcreteAfterUpdatePlugin(),
            new ShipmentTypeProductConcretePostUpdatePlugin(),
            new ProductClassProductConcreteAfterUpdatePlugin(),
        ];
    }

    /**
     * The order of execution is important for expanding with correct product approval statuses.
     *
     * @return array<\Spryker\Zed\ProductExtension\Dependency\Plugin\ProductAbstractPreCreatePluginInterface>
     */
    protected function getProductAbstractPreCreatePlugins(): array
    {
        return [
            new MerchantProductApprovalProductAbstractPreCreatePlugin(),
            new ProductApprovalProductAbstractPreCreatePlugin(),
        ];
    }

    /**
     * @return list<\Spryker\Zed\ProductExtension\Dependency\Plugin\ProductAbstractExpanderPluginInterface>
     */
    protected function getProductAbstractExpanderPlugins(Container $container): array // phpcs:ignore SlevomatCodingStandard.Functions.UnusedParameter
    {
        return [
            new ProductImageProductAbstractExpanderPlugin(),
            new TaxSetProductAbstractExpanderPlugin(),
            new PriceProductProductAbstractExpanderPlugin(),
            new MerchantProductProductAbstractExpanderPlugin(),
        ];
    }

    /**
     * @return array<\Spryker\Zed\ProductExtension\Dependency\Plugin\ProductConcreteExpanderPluginInterface>
     */
    protected function getProductConcreteExpanderPlugins(): array
    {
        return [
            new ProductImageProductConcreteExpanderPlugin(),
            new StockProductConcreteExpanderPlugin(),
            new PriceProductProductConcreteExpanderPlugin(),
            new ProductSearchProductConcreteExpanderPlugin(),
            new ProductBundleProductConcreteExpanderPlugin(),
            new ProductValidityProductConcreteExpanderPlugin(),
            new ProductReviewProductConcreteExpanderPlugin(),
            new MerchantProductOfferProductConcreteExpanderPlugin(),
            new ProductConcreteCategoriesExpanderPlugin(),
            new ProductLabelProductConcreteExpanderPlugin(),
            new ShipmentTypeProductConcreteExpanderPlugin(),
            new ProductClassesProductConcreteExpanderPlugin(),
            new SuperAttributeProductConcreteExpanderPlugin(),
        ];
    }

    /**
     * @return array<\Spryker\Zed\ProductExtension\Dependency\Plugin\ProductConcreteMergerPluginInterface>
     */
    protected function getProductConcreteMergerPlugins(): array
    {
        return [
            new ImageSetProductConcreteMergerPlugin(),
            new PriceProductConcreteMergerPlugin(),
            new ApprovalStatusProductConcreteMergerPlugin(),
        ];
    }

    /**
     * @return array<\Spryker\Zed\ProductExtension\Dependency\Plugin\ProductAbstractCollectionExpanderPluginInterface>
     */
    protected function getProductAbstractCollectionExpanderPlugins(): array
    {
        return [
            new TaxSetProductAbstractCollectionExpanderPlugin(),
            new ProductImageAbstractCollectionExpanderPlugin(),
            new ProductCategoryAbstractCollectionExpanderPlugin(),
        ];
    }

    /**
     * @return array<\Spryker\Zed\Product\Dependency\Plugin\ProductConcretePluginUpdateInterface>
     */
    protected function getProductConcreteAfterUpdateCollectionPlugins(): array
    {
        return [
            new ImageSetProductConcreteAfterUpdatePlugin(),
            new PriceProductConcreteAfterUpdatePlugin(),
            new StockProductConcreteCollectionAfterUpdatePlugin(),
            new ProductSearchProductConcreteAfterUpdatePlugin(),
            new ProductValidityUpdatePlugin(),
            new ProductClassProductConcreteAfterUpdatePlugin(),
            new ShipmentTypeProductConcretePostUpdatePlugin(),
            new ProductBundleProductConcreteAfterUpdatePlugin(),
            new ProductBundleDeactivatorProductConcreteAfterUpdatePlugin(),
        ];
    }

    /**
     * @return array<\Spryker\Zed\ProductExtension\Dependency\Plugin\ProductConcreteCollectionCreateValidatorPluginInterface>
     */
    protected function getProductConcreteCollectionCreateValidatorPlugins(): array
    {
        return [
            new PriceProductConcreteCollectionCreateValidatorPlugin(),
            new ProductImageSetExistsProductConcreteCollectionCreateValidatorPlugin(),
            new StockExistsProductConcreteCollectionCreateValidatorPlugin(),
            new ShipmentTypeExistsProductConcreteCollectionCreateValidatorPlugin(),
            new ProductClassExistsProductConcreteCollectionCreateValidatorPlugin(),
            new ProductBundleProductConcreteCollectionCreateValidatorPlugin(),
        ];
    }

    /**
     * @return array<\Spryker\Zed\ProductExtension\Dependency\Plugin\ProductConcreteCollectionUpdateValidatorPluginInterface>
     */
    protected function getProductConcreteCollectionUpdateValidatorPlugins(): array
    {
        return [
            new PriceProductConcreteCollectionUpdateValidatorPlugin(),
            new ProductImageSetExistsProductConcreteCollectionUpdateValidatorPlugin(),
            new StockExistsProductConcreteCollectionUpdateValidatorPlugin(),
            new ShipmentTypeExistsProductConcreteCollectionUpdateValidatorPlugin(),
            new ProductClassExistsProductConcreteCollectionUpdateValidatorPlugin(),
            new ProductBundleProductConcreteCollectionUpdateValidatorPlugin(),
        ];
    }

    /**
     * @return array<\Spryker\Zed\ProductExtension\Dependency\Plugin\ProductAbstractCollectionCreateValidatorPluginInterface>
     */
    protected function getProductAbstractCollectionCreateValidatorPlugins(): array
    {
        return [
            new CategoryExistsProductAbstractCollectionCreateValidatorPlugin(),
            new TaxSetExistsProductAbstractCollectionCreateValidatorPlugin(),
            new PriceProductAbstractCollectionCreateValidatorPlugin(),
        ];
    }

    /**
     * @return array<\Spryker\Zed\ProductExtension\Dependency\Plugin\ProductAbstractCollectionUpdateValidatorPluginInterface>
     */
    protected function getProductAbstractCollectionUpdateValidatorPlugins(): array
    {
        return [
            new CategoryExistsProductAbstractCollectionUpdateValidatorPlugin(),
            new TaxSetExistsProductAbstractCollectionUpdateValidatorPlugin(),
        ];
    }
}
