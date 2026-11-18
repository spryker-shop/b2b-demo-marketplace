<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Glue\OrderExperienceManagement;

use SprykerFeature\Glue\OrderExperienceManagement\OrderExperienceManagementDependencyProvider as SprykerFeatureOrderExperienceManagementDependencyProvider;
use SprykerFeature\Glue\PurchasingControl\Plugin\OrderExperienceManagement\BudgetOrderResourceExpanderPlugin;

class OrderExperienceManagementDependencyProvider extends SprykerFeatureOrderExperienceManagementDependencyProvider
{
    /**
     * @return array<\SprykerFeature\Glue\OrderExperienceManagement\Dependency\Plugin\OrderResourceExpanderPluginInterface>
     */
    protected function getOrderResourceExpanderPlugins(): array
    {
        return [
            new BudgetOrderResourceExpanderPlugin(), #PurchasingControlFeature
        ];
    }
}
