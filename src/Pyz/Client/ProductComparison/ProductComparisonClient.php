<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Pyz\Client\ProductComparison;

use Generated\Shared\Transfer\ProductComparisonTransfer;
use Spryker\Client\Kernel\AbstractClient;

/**
 * @method \Pyz\Client\ProductComparison\ProductComparisonFactory getFactory()
 */
class ProductComparisonClient extends AbstractClient implements ProductComparisonClientInterface
{
    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\ProductComparisonTransfer $productComparisonTransfer
     *
     * @return \Generated\Shared\Transfer\ProductComparisonTransfer
     */
    public function get(ProductComparisonTransfer $productComparisonTransfer): ProductComparisonTransfer
    {
        return $this->getFactory()->createProductComparisonStrategyExecutor()->get($productComparisonTransfer);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\ProductComparisonTransfer $productComparisonTransfer
     *
     * @return void
     */
    public function save(ProductComparisonTransfer $productComparisonTransfer): void
    {
        $this->getFactory()->createProductComparisonStrategyExecutor()->save($productComparisonTransfer);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\ProductComparisonTransfer $productComparisonTransfer
     *
     * @return void
     */
    public function delete(ProductComparisonTransfer $productComparisonTransfer): void
    {
        $this->getFactory()->createProductComparisonStrategyExecutor()->delete($productComparisonTransfer);
    }
}
