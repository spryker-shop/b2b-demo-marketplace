<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace PyzTest\Yves\ContentProductWidget\Reader;

use ArrayObject;
use Codeception\Test\Unit;
use Generated\Shared\Transfer\CategoryTransfer;
use Generated\Shared\Transfer\ContentProductAbstractListTypeTransfer;
use Generated\Shared\Transfer\ProductAbstractCategoryStorageTransfer;
use Generated\Shared\Transfer\ProductCategoryStorageTransfer;
use Generated\Shared\Transfer\ProductViewTransfer;
use Generated\Shared\Transfer\StoreTransfer;
use Pyz\Yves\ContentProductWidget\Reader\ContentProductAbstractReader;
use Spryker\Client\ProductCategoryStorage\ProductCategoryStorageClientInterface;
use Spryker\Client\Store\StoreClientInterface;
use SprykerShop\Yves\ContentProductWidget\Dependency\Client\ContentProductWidgetToContentProductClientBridgeInterface;
use SprykerShop\Yves\ContentProductWidget\Dependency\Client\ContentProductWidgetToProductStorageClientBridgeInterface;

/**
 * @group PyzTest
 * @group Yves
 * @group ContentProductWidget
 * @group Reader
 * @group ContentProductAbstractReaderTest
 */
class ContentProductAbstractReaderTest extends Unit
{
    /**
     * @return void
     */
    public function testFindProductAbstractCollectionReturnsNullWhenContentNotFound(): void
    {
        // Arrange
        $contentProductClientMock = $this->createMock(ContentProductWidgetToContentProductClientBridgeInterface::class);
        $productStorageClientMock = $this->createMock(ContentProductWidgetToProductStorageClientBridgeInterface::class);
        $productCategoryStorageClientMock = $this->createMock(ProductCategoryStorageClientInterface::class);
        $storeClientMock = $this->createMock(StoreClientInterface::class);

        $contentProductClientMock->expects($this->once())
            ->method('executeProductAbstractListTypeByKey')
            ->with('content-key', 'en_US')
            ->willReturn(null);

        $reader = new ContentProductAbstractReader(
            $contentProductClientMock,
            $productStorageClientMock,
            $productCategoryStorageClientMock,
            $storeClientMock,
        );

        // Act
        $result = $reader->findProductAbstractCollection('content-key', 'en_US');

        // Assert
        $this->assertNull($result);
    }

    /**
     * @return void
     */
    public function testFindProductAbstractCollectionAddsMainCategoryToProductView(): void
    {
        // Arrange
        $contentProductClientMock = $this->createMock(ContentProductWidgetToContentProductClientBridgeInterface::class);
        $productStorageClientMock = $this->createMock(ContentProductWidgetToProductStorageClientBridgeInterface::class);
        $productCategoryStorageClientMock = $this->createMock(ProductCategoryStorageClientInterface::class);
        $storeClientMock = $this->createMock(StoreClientInterface::class);

        $contentKey = 'content-key';
        $localeName = 'en_US';
        $storeName = 'DE';
        $idProductAbstract = 1;
        $mainCategoryName = 'Main category';
        $secondaryCategoryName = 'Secondary category';

        $contentProductAbstractListTypeTransfer = (new ContentProductAbstractListTypeTransfer())
            ->setIdProductAbstracts([$idProductAbstract]);

        $contentProductClientMock->expects($this->once())
            ->method('executeProductAbstractListTypeByKey')
            ->with($contentKey, $localeName)
            ->willReturn($contentProductAbstractListTypeTransfer);

        $productViewTransfer = (new ProductViewTransfer())
            ->setIdProductAbstract($idProductAbstract);

        $productStorageClientMock->expects($this->once())
            ->method('getProductAbstractViewTransfers')
            ->with([$idProductAbstract], $localeName)
            ->willReturn([$productViewTransfer]);

        $storeClientMock->expects($this->once())
            ->method('getCurrentStore')
            ->willReturn((new StoreTransfer())->setName($storeName));

        $mainCategoryStorageTransfer = (new ProductCategoryStorageTransfer())
            ->setName($mainCategoryName);

        $secondaryCategoryStorageTransfer = (new ProductCategoryStorageTransfer())
            ->setName($secondaryCategoryName);

        $productAbstractCategoryStorageTransfer = (new ProductAbstractCategoryStorageTransfer())
            ->setIdProductAbstract($idProductAbstract)
            // Intentionally provide categories in non-sorted order
            ->setCategories(new ArrayObject([$secondaryCategoryStorageTransfer, $mainCategoryStorageTransfer]));

        $productCategoryStorageClientMock->expects($this->once())
            ->method('findBulkProductAbstractCategory')
            ->with([$idProductAbstract], $localeName, $storeName)
            ->willReturn([$productAbstractCategoryStorageTransfer]);

        $productCategoryStorageClientMock->expects($this->once())
            ->method('sortProductCategories')
            ->with([$secondaryCategoryStorageTransfer, $mainCategoryStorageTransfer])
            // Simulate sorting result where the main category comes first
            ->willReturn([$mainCategoryStorageTransfer, $secondaryCategoryStorageTransfer]);

        $reader = new ContentProductAbstractReader(
            $contentProductClientMock,
            $productStorageClientMock,
            $productCategoryStorageClientMock,
            $storeClientMock,
        );

        // Act
        $result = $reader->findProductAbstractCollection($contentKey, $localeName);

        // Assert
        $this->assertIsArray($result);
        $this->assertCount(1, $result);

        /** @var \Generated\Shared\Transfer\ProductViewTransfer $resultProductViewTransfer */
        $resultProductViewTransfer = $result[0];

        $categories = $resultProductViewTransfer->getCategories();
        $this->assertNotNull($categories);
        $this->assertGreaterThan(0, $categories->count());

        /** @var \Generated\Shared\Transfer\CategoryTransfer $firstCategoryTransfer */
        $firstCategoryTransfer = $categories[0];
        $this->assertInstanceOf(CategoryTransfer::class, $firstCategoryTransfer);
        $this->assertSame($mainCategoryName, $firstCategoryTransfer->getName());
    }
}
