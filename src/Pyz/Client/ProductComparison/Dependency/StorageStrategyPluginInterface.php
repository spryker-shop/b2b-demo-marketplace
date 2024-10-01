<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Pyz\Client\ProductComparison\Dependency;

use Generated\Shared\Transfer\ProductComparisonTransfer;

interface StorageStrategyPluginInterface
{
    public function isSupported(ProductComparisonTransfer $productComparisonTransfer): bool;

    public function get(ProductComparisonTransfer $productComparisonTransfer): ProductComparisonTransfer;

    /**
     * @param \Generated\Shared\Transfer\ProductComparisonTransfer $productComparisonTransfer
     *
     * @return void
     */
    public function save(ProductComparisonTransfer $productComparisonTransfer): void;

    /**
     * @param \Generated\Shared\Transfer\ProductComparisonTransfer $productComparisonTransfer
     *
     * @return void
     */
    public function delete(ProductComparisonTransfer $productComparisonTransfer): void;
}
