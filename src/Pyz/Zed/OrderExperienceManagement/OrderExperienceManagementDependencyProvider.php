<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\OrderExperienceManagement;

use SprykerFeature\Zed\OrderExperienceManagement\Communication\Plugin\Cadence\BiWeeklyCadenceTypePlugin;
use SprykerFeature\Zed\OrderExperienceManagement\Communication\Plugin\Cadence\EveryNWeeksCadenceTypePlugin;
use SprykerFeature\Zed\OrderExperienceManagement\Communication\Plugin\Cadence\MonthlyCadenceTypePlugin;
use SprykerFeature\Zed\OrderExperienceManagement\Communication\Plugin\Cadence\WeeklyCadenceTypePlugin;
use SprykerFeature\Zed\OrderExperienceManagement\Communication\Plugin\ScheduleValidator\CheckoutPlaceabilityScheduleValidatorPlugin;
use SprykerFeature\Zed\OrderExperienceManagement\Communication\Plugin\ScheduleValidator\PriceScheduleValidatorPlugin;
use SprykerFeature\Zed\OrderExperienceManagement\OrderExperienceManagementDependencyProvider as SprykerOrderExperienceManagementDependencyProvider;

class OrderExperienceManagementDependencyProvider extends SprykerOrderExperienceManagementDependencyProvider
{
    /**
     * @return array<\SprykerFeature\Zed\OrderExperienceManagement\Dependency\Plugin\CadenceTypePluginInterface>
     */
    protected function getCadenceTypePlugins(): array
    {
        return [
            new WeeklyCadenceTypePlugin(), #RecurringOrdersFeature
            new BiWeeklyCadenceTypePlugin(), #RecurringOrdersFeature
            new MonthlyCadenceTypePlugin(), #RecurringOrdersFeature
            new EveryNWeeksCadenceTypePlugin(), #RecurringOrdersFeature
        ];
    }

    /**
     * @return array<\SprykerFeature\Zed\OrderExperienceManagement\Dependency\Plugin\ScheduleValidatorPluginInterface>
     */
    protected function getScheduleValidatorPlugins(): array
    {
        return [
            new PriceScheduleValidatorPlugin(), #RecurringOrdersFeature
            new CheckoutPlaceabilityScheduleValidatorPlugin(), #RecurringOrdersFeature
        ];
    }
}
