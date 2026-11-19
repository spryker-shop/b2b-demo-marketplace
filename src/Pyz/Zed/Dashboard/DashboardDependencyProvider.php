<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\Dashboard;

use Spryker\Zed\Dashboard\DashboardDependencyProvider as SprykerDashboardDependencyProvider;
use Spryker\Zed\SalesStatistics\Communication\Plugin\DailyOrderCountChartPlugin;
use Spryker\Zed\SalesStatistics\Communication\Plugin\OrderItemCountByStatusChartPlugin;
use Spryker\Zed\SalesStatistics\Communication\Plugin\TopOrdersChartPlugin;

class DashboardDependencyProvider extends SprykerDashboardDependencyProvider
{
    /**
     * @return array<\Spryker\Shared\Dashboard\Dependency\Plugin\DashboardPluginInterface>
     */
    protected function getDashboardPlugins(): array
    {
        return [
            new DailyOrderCountChartPlugin(),
            new OrderItemCountByStatusChartPlugin(),
            new TopOrdersChartPlugin(),
        ];
    }
}
