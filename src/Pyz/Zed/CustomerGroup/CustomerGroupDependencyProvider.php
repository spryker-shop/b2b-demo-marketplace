<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\CustomerGroup;

use Spryker\Zed\CustomerGroup\CustomerGroupDependencyProvider as SprykerCustomerGroupDependencyProvider;
use SprykerFeature\Zed\CustomerExperienceManagement\Communication\Plugin\CustomerGroup\CompanyBusinessUnitCustomerGroupFormExpanderPlugin;
use SprykerFeature\Zed\CustomerExperienceManagement\Communication\Plugin\CustomerGroup\CompanyBusinessUnitCustomerGroupFormTabExpanderPlugin;
use SprykerFeature\Zed\CustomerExperienceManagement\Communication\Plugin\CustomerGroup\CompanyBusinessUnitCustomerGroupPostSavePlugin;
use SprykerFeature\Zed\CustomerExperienceManagement\Communication\Plugin\CustomerGroup\CompanyBusinessUnitCustomerGroupViewExpanderPlugin;
use SprykerFeature\Zed\CustomerExperienceManagement\Communication\Plugin\CustomerGroup\CompanyCustomerGroupFormExpanderPlugin;
use SprykerFeature\Zed\CustomerExperienceManagement\Communication\Plugin\CustomerGroup\CompanyCustomerGroupFormTabExpanderPlugin;
use SprykerFeature\Zed\CustomerExperienceManagement\Communication\Plugin\CustomerGroup\CompanyCustomerGroupPostSavePlugin;
use SprykerFeature\Zed\CustomerExperienceManagement\Communication\Plugin\CustomerGroup\CompanyCustomerGroupViewExpanderPlugin;

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
}
