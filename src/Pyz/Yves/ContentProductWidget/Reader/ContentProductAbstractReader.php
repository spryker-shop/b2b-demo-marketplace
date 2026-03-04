<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Yves\ContentProductWidget\Reader;

use ArrayObject;
use Generated\Shared\Transfer\CategoryTransfer;
use Generated\Shared\Transfer\ProductCategoryStorageTransfer;
use Spryker\Client\ProductCategoryStorage\ProductCategoryStorageClientInterface;
use Spryker\Client\Store\StoreClientInterface;
use SprykerShop\Yves\ContentProductWidget\Dependency\Client\ContentProductWidgetToContentProductClientBridgeInterface;
use SprykerShop\Yves\ContentProductWidget\Dependency\Client\ContentProductWidgetToProductStorageClientBridgeInterface;
use SprykerShop\Yves\ContentProductWidget\Reader\ContentProductAbstractReader as SprykerShopContentProductAbstractReader;

class ContentProductAbstractReader extends SprykerShopContentProductAbstractReader
{
    protected ProductCategoryStorageClientInterface $productCategoryStorageClient;

    protected StoreClientInterface $storeClient;

    public function __construct(
        ContentProductWidgetToContentProductClientBridgeInterface $contentProductClient,
        ContentProductWidgetToProductStorageClientBridgeInterface $productStorageClient,
        ProductCategoryStorageClientInterface $productCategoryStorageClient,
        StoreClientInterface $storeClient,
    ) {
        $this->contentProductClient = $contentProductClient;
        $this->productStorageClient = $productStorageClient;
        $this->productCategoryStorageClient = $productCategoryStorageClient;
        $this->storeClient = $storeClient;
    }

    /**
     * @param string $contentKey
     * @param string $localeName
     *
     * @return array<\Generated\Shared\Transfer\ProductViewTransfer>|null
     */
    public function findProductAbstractCollection(string $contentKey, string $localeName): ?array
    {
        $contentProductAbstractListTypeTransfer = $this->contentProductClient->executeProductAbstractListTypeByKey($contentKey, $localeName);

        if ($contentProductAbstractListTypeTransfer === null) {
            return null;
        }

        /** @var array<\Generated\Shared\Transfer\ProductViewTransfer> $productAbstractViewCollection */
        $productAbstractViewCollection = $this->productStorageClient
            ->getProductAbstractViewTransfers($contentProductAbstractListTypeTransfer->getIdProductAbstracts(), $localeName);

        $productAbstractCategoryStorageTransfers = $this->productCategoryStorageClient->findBulkProductAbstractCategory(
            $contentProductAbstractListTypeTransfer->getIdProductAbstracts(),
            $localeName,
            $this->storeClient->getCurrentStore()->getName(),
        );

        $productCategoryStorageTransfers = $this->filterCategories($productAbstractCategoryStorageTransfers);

        foreach ($productAbstractViewCollection as $productAbstractView) {
            $idProductAbstract = $productAbstractView->getIdProductAbstractOrFail();

            if (!$productCategoryStorageTransfers->offsetExists($idProductAbstract)) {
                continue;
            }

            $productCategoryStorageTransfer = $productCategoryStorageTransfers->offsetGet($idProductAbstract);

            $productAbstractView->addCategory(
                (new CategoryTransfer())->setName($productCategoryStorageTransfer->getName()),
            );
        }

        return $productAbstractViewCollection;
    }

    /**
     * @param array<\Generated\Shared\Transfer\ProductAbstractCategoryStorageTransfer> $productAbstractCategoryStorageTransfers
     *
     * @return \ArrayObject<int, \Generated\Shared\Transfer\ProductCategoryStorageTransfer>
     */
    protected function filterCategories(array $productAbstractCategoryStorageTransfers): ArrayObject
    {
        $categories = new ArrayObject();

        foreach ($productAbstractCategoryStorageTransfers as $productAbstractCategoryStorageTransfer) {
            if (!$productAbstractCategoryStorageTransfer->getCategories()->count()) {
                continue;
            }

            $productCategoryStorageTransfers = $productAbstractCategoryStorageTransfer->getCategories()->getArrayCopy();

            $sortedProductCategoryStorageTransfers = $this->productCategoryStorageClient
                ->sortProductCategories($productCategoryStorageTransfers);

            $categories->offsetSet(
                $productAbstractCategoryStorageTransfer->getIdProductAbstractOrFail(),
                $this->getMainProductCategoryStorageTransfer($sortedProductCategoryStorageTransfers),
            );
        }

        return $categories;
    }

    /**
     * @param array<\Generated\Shared\Transfer\ProductCategoryStorageTransfer> $productCategoryStorageTransfers
     *
     * @return \Generated\Shared\Transfer\ProductCategoryStorageTransfer
     */
    protected function getMainProductCategoryStorageTransfer(array $productCategoryStorageTransfers): ProductCategoryStorageTransfer
    {
        return reset($productCategoryStorageTransfers);
    }
}
