<?php

namespace Pyz\Zed\TenantOnboarding\Communication\Plugin\Event\Listener;

use Spryker\Zed\Event\Dependency\Plugin\EventBulkHandlerInterface;

/**
 * @method \Pyz\Zed\TenantOnboarding\Business\TenantOnboardingFacadeInterface getFacade()
 * @method \Pyz\Zed\TenantOnboarding\TenantOnboardingConfig getConfig()
 * @method \Pyz\Zed\TenantOnboarding\Communication\TenantOnboardingCommunicationFactory getFactory()
 */
class TenantImportMerchantListener extends AbstractTenantPartialImportListener implements EventBulkHandlerInterface
{
    protected const DATA_IMPORT_FULL_CONFIG_PATH = 'data/import/tenant/partial/merchant.yml';
}
