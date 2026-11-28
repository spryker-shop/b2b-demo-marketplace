<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\ProductCategoryStorage\Business\Trigger;

interface ProductCategoryPublishEventTriggerInterface
{
    /**
     * @param array<int> $productAbstractIds
     */
    public function triggerProductCategoryPublishEvents(array $productAbstractIds): void;
}
