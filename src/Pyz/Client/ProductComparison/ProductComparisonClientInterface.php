<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Pyz\Client\ProductComparison;

use Generated\Shared\Transfer\ProductComparisonTransfer;

interface ProductComparisonClientInterface
{
    /**
     * Specification:
     * - Gets comparison products for a customer.
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\ProductComparisonTransfer $productComparisonTransfer
     *
     * @return \Generated\Shared\Transfer\ProductComparisonTransfer
     */
    public function get(ProductComparisonTransfer $productComparisonTransfer): ProductComparisonTransfer;

    /**
     * Specification:
     * - Saves comparison products for a customer.
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\ProductComparisonTransfer $productComparisonTransfer
     *
     * @return void
     */
    public function save(ProductComparisonTransfer $productComparisonTransfer): void;

    /**
     * Specification:
     * - Deletes comparison products for a customer.
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\ProductComparisonTransfer $productComparisonTransfer
     *
     * @return void
     */
    public function delete(ProductComparisonTransfer $productComparisonTransfer): void;
}
