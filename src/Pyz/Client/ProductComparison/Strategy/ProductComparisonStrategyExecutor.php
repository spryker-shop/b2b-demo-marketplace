<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Pyz\Client\ProductComparison\Strategy;

use Generated\Shared\Transfer\ProductComparisonTransfer;

class ProductComparisonStrategyExecutor implements ProductComparisonStrategyExecutorInterface
{
    /**
     * @var array<\Pyz\Client\ProductComparison\Dependency\StorageStrategyPluginInterface>
     */
    private array $storageStrategyPlugins;

    public function __construct(array $storageStrategyPlugins)
    {
        $this->storageStrategyPlugins = $storageStrategyPlugins;
    }

    public function get(ProductComparisonTransfer $productComparisonTransfer): ProductComparisonTransfer
    {
        foreach ($this->storageStrategyPlugins as $plugin) {
            if ($plugin->isSupported($productComparisonTransfer)) {
                $productComparisonTransfer = $plugin->get($productComparisonTransfer);
            }
        }

        return $productComparisonTransfer;
    }

    /**
     * @param \Generated\Shared\Transfer\ProductComparisonTransfer $productComparisonTransfer
     *
     * @return void
     */
    public function save(ProductComparisonTransfer $productComparisonTransfer): void
    {
        foreach ($this->storageStrategyPlugins as $plugin) {
            if ($plugin->isSupported($productComparisonTransfer)) {
                $plugin->save($productComparisonTransfer);
            }
        }
    }

    /**
     * @param \Generated\Shared\Transfer\ProductComparisonTransfer $productComparisonTransfer
     *
     * @return void
     */
    public function delete(ProductComparisonTransfer $productComparisonTransfer): void
    {
        foreach ($this->storageStrategyPlugins as $plugin) {
            if ($plugin->isSupported($productComparisonTransfer)) {
                $plugin->delete($productComparisonTransfer);
            }
        }
    }
}
