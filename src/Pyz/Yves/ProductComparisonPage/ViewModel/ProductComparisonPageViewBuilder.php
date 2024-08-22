<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Pyz\Yves\ProductComparisonPage\ViewModel;

use ArrayObject;
use Generated\Shared\Transfer\ProductComparisonAttributeGroupTransfer;
use Generated\Shared\Transfer\ProductComparisonTransfer;
use Generated\Shared\Transfer\ProductComparisonViewTransfer;
use Pyz\Client\ProductAttributeGroupStorage\ProductAttributeGroupStorageClientInterface;
use Pyz\Yves\ProductComparisonPage\ProductComparisonPageConfig;
use Spryker\Client\Locale\LocaleClientInterface;
use Spryker\Client\ProductStorage\ProductStorageClientInterface;

/**
 * @SuppressWarnings(PHPMD.LongVariable)
 */
class ProductComparisonPageViewBuilder
{
    private ProductStorageClientInterface $productStorageClient;

    private LocaleClientInterface $localeClient;

    private ProductAttributeGroupStorageClientInterface $productAttributeGroupStorageClient;

    public function __construct(
        ProductStorageClientInterface $productStorageClient,
        LocaleClientInterface $localeClient,
        ProductAttributeGroupStorageClientInterface $productAttributeGroupStorageClient,
    ) {
        $this->productStorageClient = $productStorageClient;
        $this->localeClient = $localeClient;
        $this->productAttributeGroupStorageClient = $productAttributeGroupStorageClient;
    }

    public function getViewData(ProductComparisonTransfer $productComparisonTransfer): ProductComparisonViewTransfer
    {
        $productComparisonViewTransfer = new ProductComparisonViewTransfer();
        $this->expandWithProductComparisonAttributeGroups($productComparisonViewTransfer);

        if (!$productComparisonTransfer->getProductAbstractIds()) {
            return $productComparisonViewTransfer;
        }

        $productViewTransfers = $this->getProductViewTransfers($productComparisonTransfer);
        $this->expandWithProductComparisonAttributes($productComparisonViewTransfer, $productViewTransfers);

        $productComparisonViewTransfer->setProductViews(new ArrayObject($productViewTransfers));

        return $productComparisonViewTransfer;
    }

    /**
     * @param \Generated\Shared\Transfer\ProductComparisonTransfer $productComparisonTransfer
     *
     * @return array<\Generated\Shared\Transfer\ProductViewTransfer>
     */
    private function getProductViewTransfers(ProductComparisonTransfer $productComparisonTransfer): array
    {
        $productAbstractIds = $productComparisonTransfer->getProductAbstractIds();
        $currentLocale = $this->localeClient->getCurrentLocale();

        return $this->productStorageClient->getProductAbstractViewTransfers($productAbstractIds, $currentLocale);
    }

    private function expandWithProductComparisonAttributeGroups(ProductComparisonViewTransfer $productComparisonViewTransfer): ProductComparisonViewTransfer
    {
        $productAttributeGroupStorageTransfers = $this->productAttributeGroupStorageClient->getProductAttributeGroups();
        $sortedProductAttributeGroupStorageTransfers = array_flip(ProductComparisonPageConfig::COMPARISON_PRODUCT_ATTRIBUTE_GROUPS_ORDER);

        foreach ($productAttributeGroupStorageTransfers as $attributeGroupName => $productAttributeGroupStorageTransfer) {
            $sortedProductAttributeGroupStorageTransfers[$attributeGroupName] = $productAttributeGroupStorageTransfer;
        }

        foreach ($sortedProductAttributeGroupStorageTransfers as $productAttributeGroupStorageTransfer) {
            $productComparisonAttributeGroupTransfer = (new ProductComparisonAttributeGroupTransfer())
                ->setName($productAttributeGroupStorageTransfer->getName())
                ->setAllAttributes($productAttributeGroupStorageTransfer->getAttributes());

            $productComparisonViewTransfer->addProductComparisonAttributeGroup($productComparisonAttributeGroupTransfer);
        }

        return $productComparisonViewTransfer;
    }

    /**
     * @param \Generated\Shared\Transfer\ProductComparisonViewTransfer $productComparisonViewTransfer
     * @param array<\Generated\Shared\Transfer\ProductViewTransfer> $productViewTransfers
     *
     * @return \Generated\Shared\Transfer\ProductComparisonViewTransfer
     */
    private function expandWithProductComparisonAttributes(
        ProductComparisonViewTransfer $productComparisonViewTransfer,
        array $productViewTransfers,
    ): ProductComparisonViewTransfer {
        $comparisonAttributes = [];
        $indexedAttributes = [];
        foreach ($productComparisonViewTransfer->getProductComparisonAttributeGroups() as $productComparisonAttributeGroup) {
            foreach ($productComparisonAttributeGroup->getAllAttributes() as $attributeKey) {
                $indexedAttributes[$attributeKey] = $productComparisonAttributeGroup->getName();
            }
        }

        foreach ($productViewTransfers as $productViewTransfer) {
            $attributeKeys = array_keys($productViewTransfer->getAttributes());
            foreach ($attributeKeys as $attributeKey) {
                $groupName = $indexedAttributes[$attributeKey] ?? null;
                $comparisonAttributes[$groupName][$attributeKey] = $attributeKey;
            }
        }

        foreach ($productComparisonViewTransfer->getProductComparisonAttributeGroups() as $productComparisonAttributeGroup) {
            $productComparisonAttributeGroup->setComparisonAttributes($comparisonAttributes[$productComparisonAttributeGroup->getName()] ?? []);
        }

        return $productComparisonViewTransfer;
    }
}
