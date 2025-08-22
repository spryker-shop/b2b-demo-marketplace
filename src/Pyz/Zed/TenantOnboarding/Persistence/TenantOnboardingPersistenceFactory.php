<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Pyz\Zed\TenantOnboarding\Persistence;

use Orm\Zed\TenantOnboarding\Persistence\PyzTenantRegistrationQuery;
use Orm\Zed\TenantOnboarding\Persistence\PyzTenantQuery;
use Spryker\Zed\Kernel\Persistence\AbstractPersistenceFactory;

/**
 * @method \Pyz\Zed\TenantOnboarding\TenantOnboardingConfig getConfig()
 * @method \Pyz\Zed\TenantOnboarding\Persistence\TenantOnboardingQueryContainerInterface getQueryContainer()
 */
class TenantOnboardingPersistenceFactory extends AbstractPersistenceFactory
{
    /**
     * @return \Orm\Zed\TenantOnboarding\Persistence\PyzTenantRegistrationQuery
     */
    public function createTenantRegistrationQuery(): PyzTenantRegistrationQuery
    {
        return PyzTenantRegistrationQuery::create();
    }

    /**
     * @return \Orm\Zed\TenantOnboarding\Persistence\PyzTenantQuery
     */
    public function createTenantQuery(): PyzTenantQuery
    {
        return PyzTenantQuery::create();
    }
}