<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\CustomerGroup;

use Spryker\Zed\CustomerGroup\CustomerGroupConfig as SprykerCustomerGroupConfig;
use SprykerFeature\Zed\CustomerExperienceManagement\Communication\Plugin\CustomerGroup\CompanyBusinessUnitCustomerGroupFormTabExpanderPlugin;
use SprykerFeature\Zed\CustomerExperienceManagement\Communication\Plugin\CustomerGroup\CompanyCustomerGroupFormTabExpanderPlugin;

class CustomerGroupConfig extends SprykerCustomerGroupConfig
{
    public function isRedirectToEditPageAfterUpdateEnabled(): bool
    {
        return true;
    }

    /**
     * @return list<string>
     */
    public function getFormTabOrder(): array
    {
        return [
            static::FORM_TAB_NAME_GENERAL,
            CompanyCustomerGroupFormTabExpanderPlugin::TAB_NAME,
            CompanyBusinessUnitCustomerGroupFormTabExpanderPlugin::TAB_NAME,
            static::FORM_TAB_NAME_CUSTOMER_ASSIGNMENT,
        ];
    }
}
