<?php

namespace Go\Zed\TenantOnboarding\Communication\Plugin\Event\Listener;

/**
 * @method \Go\Zed\TenantOnboarding\Business\TenantOnboardingFacadeInterface getFacade()
 * @method \Go\Zed\TenantOnboarding\TenantOnboardingConfig getConfig()
 * @method \Go\Zed\TenantOnboarding\Communication\TenantOnboardingCommunicationFactory getFactory()
 */
class TenantImportSpecialCatalogListener extends AbstractTenantPartialImportListener
{
    protected const DATA_IMPORT_FULL_CONFIG_PATH = 'data/import/tenant/partial/special_catalog.yml';
}
