<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\CustomerGroup;

use Spryker\Zed\CustomerGroup\CustomerGroupDependencyProvider as SprykerCustomerGroupDependencyProvider;
use SprykerFeature\Zed\CustomerExperienceManagement\Communication\Plugin\CustomerGroup\CompanyBusinessUnitCustomerGroupAssignmentFilterPlugin;
use SprykerFeature\Zed\CustomerExperienceManagement\Communication\Plugin\CustomerGroup\CompanyBusinessUnitCustomerGroupFormExpanderPlugin;
use SprykerFeature\Zed\CustomerExperienceManagement\Communication\Plugin\CustomerGroup\CompanyBusinessUnitCustomerGroupFormTabExpanderPlugin;
use SprykerFeature\Zed\CustomerExperienceManagement\Communication\Plugin\CustomerGroup\CompanyBusinessUnitCustomerGroupPostSavePlugin;
use SprykerFeature\Zed\CustomerExperienceManagement\Communication\Plugin\CustomerGroup\CompanyBusinessUnitCustomerGroupQueryExpanderPlugin;
use SprykerFeature\Zed\CustomerExperienceManagement\Communication\Plugin\CustomerGroup\CompanyBusinessUnitCustomerGroupViewExpanderPlugin;
use SprykerFeature\Zed\CustomerExperienceManagement\Communication\Plugin\CustomerGroup\CompanyCustomerGroupAssignmentFilterPlugin;
use SprykerFeature\Zed\CustomerExperienceManagement\Communication\Plugin\CustomerGroup\CompanyCustomerGroupFormExpanderPlugin;
use SprykerFeature\Zed\CustomerExperienceManagement\Communication\Plugin\CustomerGroup\CompanyCustomerGroupFormTabExpanderPlugin;
use SprykerFeature\Zed\CustomerExperienceManagement\Communication\Plugin\CustomerGroup\CompanyCustomerGroupPostSavePlugin;
use SprykerFeature\Zed\CustomerExperienceManagement\Communication\Plugin\CustomerGroup\CompanyCustomerGroupQueryExpanderPlugin;
use SprykerFeature\Zed\CustomerExperienceManagement\Communication\Plugin\CustomerGroup\CompanyCustomerGroupViewExpanderPlugin;
use SprykerFeature\Zed\CustomerExperienceManagement\Communication\Plugin\CustomerGroup\CompanyUserCustomerGroupProviderPlugin;
use SprykerFeature\Zed\CustomerExperienceManagement\Communication\Plugin\CustomerGroup\CustomerCustomerGroupAssignmentFilterPlugin;

class CustomerGroupDependencyProvider extends SprykerCustomerGroupDependencyProvider
{
    /**
     * @return list<\Spryker\Zed\CustomerGroupExtension\Dependency\Plugin\CustomerGroupPostSavePluginInterface>
     */
    protected function getCustomerGroupPostSavePlugins(): array
    {
        return [
            new CompanyCustomerGroupPostSavePlugin(),
            new CompanyBusinessUnitCustomerGroupPostSavePlugin(),
        ];
    }

    /**
     * @return list<\Spryker\Zed\CustomerGroupExtension\Dependency\Plugin\CustomerGroupProviderPluginInterface>
     */
    protected function getCustomerGroupProviderPlugins(): array
    {
        return [
            new CompanyUserCustomerGroupProviderPlugin(),
        ];
    }

    /**
     * @return list<\Spryker\Zed\CustomerGroupExtension\Dependency\Plugin\CustomerGroupFormTabExpanderPluginInterface>
     */
    protected function getCustomerGroupFormTabExpanderPlugins(): array
    {
        return [
            new CompanyCustomerGroupFormTabExpanderPlugin(),
            new CompanyBusinessUnitCustomerGroupFormTabExpanderPlugin(),
        ];
    }

    /**
     * @return list<\Spryker\Zed\CustomerGroupExtension\Dependency\Plugin\CustomerGroupFormExpanderPluginInterface>
     */
    protected function getCustomerGroupFormExpanderPlugins(): array
    {
        return [
            new CompanyCustomerGroupFormExpanderPlugin(),
            new CompanyBusinessUnitCustomerGroupFormExpanderPlugin(),
        ];
    }

    /**
     * @return list<\Spryker\Zed\CustomerGroupExtension\Dependency\Plugin\CustomerGroupViewExpanderPluginInterface>
     */
    protected function getCustomerGroupViewExpanderPlugins(): array
    {
        return [
            new CompanyCustomerGroupViewExpanderPlugin(),
            new CompanyBusinessUnitCustomerGroupViewExpanderPlugin(),
        ];
    }

    /**
     * @return list<\Spryker\Zed\CustomerGroupExtension\Dependency\Plugin\CustomerGroupAssignmentFilterPluginInterface>
     */
    protected function getCustomerGroupAssignmentFilterPlugins(): array
    {
        return [
            new CustomerCustomerGroupAssignmentFilterPlugin(),
            new CompanyCustomerGroupAssignmentFilterPlugin(),
            new CompanyBusinessUnitCustomerGroupAssignmentFilterPlugin(),
        ];
    }

    /**
     * @return list<\Spryker\Zed\CustomerGroupExtension\Dependency\Plugin\CustomerGroupQueryExpanderPluginInterface>
     */
    protected function getCustomerGroupQueryExpanderPlugins(): array
    {
        return [
            new CompanyCustomerGroupQueryExpanderPlugin(),
            new CompanyBusinessUnitCustomerGroupQueryExpanderPlugin(),
        ];
    }
}
