<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Plmb\Zed\Stock;

use Pyz\Zed\Stock\StockConfig as PyzStockConfig;

/**
 * Extends the Pyz config rather than the Spryker one so the inherited project overrides
 * (isConditionalStockUpdateApplied(), getEventQueueName()) are preserved — extending the core
 * class here would silently drop them.
 *
 * Only the store-keyed warehouse mapping is redefined, for the project stores PL/UA.
 */
class StockConfig extends PyzStockConfig
{
    /**
     * @return array<string, list<string>>
     */
    public function getStoreToWarehouseMapping(): array
    {
        return [
            'PL' => [
                'Warehouse1',
                'Warehouse2',
            ],
            'UA' => [
                'Warehouse2',
            ],
        ];
    }
}
