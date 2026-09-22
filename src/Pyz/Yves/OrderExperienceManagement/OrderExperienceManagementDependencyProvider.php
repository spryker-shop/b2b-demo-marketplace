<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Yves\OrderExperienceManagement;

use SprykerFeature\Yves\OrderExperienceManagement\OrderExperienceManagementDependencyProvider as SprykerFeatureOrderExperienceManagementDependencyProvider;
use SprykerFeature\Yves\PurchasingControl\Plugin\OrderExperienceManagement\CostCenterRecurringOrderApproveFormExpanderPlugin;
use SprykerFeature\Yves\PurchasingControl\Plugin\OrderExperienceManagement\CostCenterRecurringScheduleEditFormExpanderPlugin;
use SprykerFeature\Yves\SelfServicePortal\Plugin\OrderExperienceManagement\ServiceProductAddedProductConcreteRestrictionPlugin;

class OrderExperienceManagementDependencyProvider extends SprykerFeatureOrderExperienceManagementDependencyProvider
{
    /**
     * @return array<\SprykerFeature\Yves\OrderExperienceManagement\Dependency\Plugin\RecurringOrderApproveFormExpanderPluginInterface>
     */
    protected function getRecurringOrderApproveFormExpanderPlugins(): array
    {
        return [
            new CostCenterRecurringOrderApproveFormExpanderPlugin(), #RecurringOrdersFeature
        ];
    }

    /**
     * @return array<\SprykerFeature\Yves\OrderExperienceManagement\Dependency\Plugin\RecurringScheduleEditFormExpanderPluginInterface>
     */
    protected function getRecurringScheduleEditFormExpanderPlugins(): array
    {
        return [
            new CostCenterRecurringScheduleEditFormExpanderPlugin(), #RecurringOrdersFeature
        ];
    }

    /**
     * @return array<\SprykerFeature\Yves\OrderExperienceManagement\Dependency\Plugin\AddedProductConcreteRestrictionPluginInterface>
     */
    protected function getAddedProductConcreteRestrictionPlugins(): array
    {
        return [
            new ServiceProductAddedProductConcreteRestrictionPlugin(), #RecurringOrdersFeature
        ];
    }
}
