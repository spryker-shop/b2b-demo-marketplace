<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Pyz\Client\ProductComparison;

use Pyz\Client\ProductComparison\Strategy\ProductComparisonStrategyExecutor;
use Pyz\Client\ProductComparison\Strategy\ProductComparisonStrategyExecutorInterface;
use Spryker\Client\Kernel\AbstractFactory;
use Spryker\Client\Session\SessionClientInterface;
use Spryker\Client\Storage\StorageClientInterface;

class ProductComparisonFactory extends AbstractFactory
{
    public function getStorageClient(): StorageClientInterface
    {
        return $this->getProvidedDependency(ProductComparisonDependencyProvider::CLIENT_STORAGE);
    }

    public function getSessionClient(): SessionClientInterface
    {
        return $this->getProvidedDependency(ProductComparisonDependencyProvider::CLIENT_SESSION);
    }

    public function createProductComparisonStrategyExecutor(): ProductComparisonStrategyExecutorInterface
    {
        return new ProductComparisonStrategyExecutor($this->getStorageStrategyPlugins());
    }

    /**
     * @return array<\Pyz\Client\ProductComparison\Dependency\StorageStrategyPluginInterface>
     */
    private function getStorageStrategyPlugins(): array
    {
        return $this->getProvidedDependency(ProductComparisonDependencyProvider::PLUGINS_STORAGE_STRATEGY);
    }
}
