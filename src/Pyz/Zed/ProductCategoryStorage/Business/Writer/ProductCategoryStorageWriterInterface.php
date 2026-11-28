<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\ProductCategoryStorage\Business\Writer;

use Spryker\Zed\ProductCategoryStorage\Business\Writer\ProductCategoryStorageWriterInterface as SprykerProductCategoryStorageWriterInterface;

interface ProductCategoryStorageWriterInterface extends SprykerProductCategoryStorageWriterInterface
{
    /**
     * Specification:
     * - Writes the product abstracts in place when there are at most `getCategoryProductAbstractPublishThreshold()` of them.
     * - Above that, re-enqueues them as `ProductCategory.category.publish` events in chunks.
     *
     * @param array<int> $productAbstractIds
     */
    public function writeCategoryProductAbstractCollection(array $productAbstractIds): void;
}
