<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Pyz\Client\ProductComparison\Plugin;

use Generated\Shared\Transfer\ProductComparisonTransfer;
use Pyz\Client\ProductComparison\Dependency\StorageStrategyPluginInterface;
use Spryker\Client\Kernel\AbstractPlugin;

/**
 * @method \Pyz\Client\ProductComparison\ProductComparisonFactory getFactory()
 */
class SessionStorageStrategyPlugin extends AbstractPlugin implements StorageStrategyPluginInterface
{
    private const SESSION_IDENTIFIER = 'comparison session identifier';

    public function isSupported(ProductComparisonTransfer $productComparisonTransfer): bool
    {
        if ($productComparisonTransfer->getIdCustomer()) {
            return true;
        }

        return false;
    }

    public function get(ProductComparisonTransfer $productComparisonTransfer): ProductComparisonTransfer
    {
        return $this->getFactory()
            ->getSessionClient()
            ->get(self::SESSION_IDENTIFIER, $productComparisonTransfer);
    }

    /**
     * @param \Generated\Shared\Transfer\ProductComparisonTransfer $productComparisonTransfer
     *
     * @return void
     */
    public function save(ProductComparisonTransfer $productComparisonTransfer): void
    {
        $this->getFactory()
            ->getSessionClient()
            ->set(self::SESSION_IDENTIFIER, $productComparisonTransfer);
    }

    /**
     * @param \Generated\Shared\Transfer\ProductComparisonTransfer $productComparisonTransfer
     *
     * @return void
     */
    public function delete(ProductComparisonTransfer $productComparisonTransfer): void
    {
        $this->getFactory()->getSessionClient()->remove(self::SESSION_IDENTIFIER);
    }
}
