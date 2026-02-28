<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\AnalyticsGui;

use Spryker\Zed\AnalyticsGui\AnalyticsGuiDependencyProvider as SprykerAnalyticsGuiDependencyProvider;
use SprykerEco\Zed\AmazonQuicksight\Communication\Plugin\AnalyticsGui\QuicksightAnalyticsCollectionExpanderPlugin;

class AnalyticsGuiDependencyProvider extends SprykerAnalyticsGuiDependencyProvider
{
    /**
     * @return list<\Spryker\Zed\AnalyticsGuiExtension\Dependency\Plugin\AnalyticsCollectionExpanderPluginInterface>
     */
    protected function getAnalyticsCollectionExpanderPlugins(): array
    {
        return [
            new QuicksightAnalyticsCollectionExpanderPlugin(),
        ];
    }
}
