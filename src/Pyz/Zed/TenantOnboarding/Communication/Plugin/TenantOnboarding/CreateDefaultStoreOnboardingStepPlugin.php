<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Pyz\Zed\TenantOnboarding\Communication\Plugin\TenantOnboarding;

use Generated\Shared\Transfer\StoreApplicationContextCollectionTransfer;
use Generated\Shared\Transfer\StoreApplicationContextTransfer;
use Generated\Shared\Transfer\StoreTransfer;
use Generated\Shared\Transfer\TenantOnboardingStepResultTransfer;
use Generated\Shared\Transfer\TenantRegistrationTransfer;
use Pyz\Zed\TenantOnboarding\Business\Plugin\OnboardingStepPluginInterface;
use Spryker\Zed\Kernel\Communication\AbstractPlugin;

/**
 * @method \Pyz\Zed\TenantOnboarding\Communication\TenantOnboardingCommunicationFactory getFactory()
 */
class CreateDefaultStoreOnboardingStepPlugin extends AbstractPlugin implements OnboardingStepPluginInterface
{
    protected const STORE_NAME = 'DEFAULT';
    protected const LOCALES = ['en_US', 'de_DE'];
    protected const CURRENCIES = ['USD', 'EUR'];

    protected const DEFAULT_LOCALE = 'en_US';

    protected const DEFAULT_CURRENCY = 'USD';

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'CreateDefaultStore';
    }

    /**
     * Specification:
     * - Creates a new store with the name "DEFAULT"
     * - Configures locales: en_US, de_DE (en_US as default)
     * - Configures currencies: USD, EUR (USD as default)
     * - Associates the store with the tenant
     *
     * @param \Generated\Shared\Transfer\TenantRegistrationTransfer $tenantRegistrationTransfer
     *
     * @return \Generated\Shared\Transfer\TenantOnboardingStepResultTransfer
     */
    public function execute(TenantRegistrationTransfer $tenantRegistrationTransfer): TenantOnboardingStepResultTransfer
    {
        $resultTransfer = new TenantOnboardingStepResultTransfer();
        $resultTransfer->setIsSuccessful(false);

        try {
            // Create store transfer with tenant-specific name prefix
            $storeTransfer = $this->createStoreTransfer();
            $storeTransfer->setIdTenant($tenantRegistrationTransfer->getTenantName());

            // Create the store using Store facade
            $createdStoreResponseTransfer = $this->getFactory()->getStoreFacade()->createStore($storeTransfer);

            if ($createdStoreResponseTransfer->getIsSuccessful()) {
                $resultTransfer->setIsSuccessful(true);
                $resultTransfer->addContextItem('store_name: ' . $storeTransfer->getName());
                $resultTransfer->addContextItem('message: ' . 'Store created successfully with locales and currencies');
            } else {
                $resultTransfer->addError('Failed to create store: Store creation returned null');
            }
        } catch (\Exception $e) {
            $resultTransfer->addError('Store creation failed: ' . $e->getMessage());
        }

        return $resultTransfer;
    }

    protected function createStoreTransfer(): StoreTransfer
    {
        $storeName = static::STORE_NAME;
        $storeTransfer = new StoreTransfer();
        $storeTransfer->setName($storeName)
            ->setDefaultLocaleIsoCode(static::DEFAULT_LOCALE)
            ->setDefaultCurrencyIsoCode(static::DEFAULT_CURRENCY)
            ->setAvailableLocaleIsoCodes(static::LOCALES)
            ->setAvailableCurrencyIsoCodes(static::CURRENCIES)
            ->setApplicationContextCollection(
                (new StoreApplicationContextCollectionTransfer())
                    ->addApplicationContext(
                        (new StoreApplicationContextTransfer())
                            ->setTimezone('UTC')
                    )
            );

        return $storeTransfer;
    }
}
