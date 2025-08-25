<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Pyz\Zed\TenantOnboarding\Communication\Plugin\TenantOnboarding;

use Generated\Shared\Transfer\TenantRegistrationTransfer;
use Generated\Shared\Transfer\TenantOnboardingStepResultTransfer;
use Generated\Shared\Transfer\TenantTransfer;
use Pyz\Zed\TenantOnboarding\Business\Plugin\OnboardingStepPluginInterface;
use Spryker\Zed\Kernel\Communication\AbstractPlugin;

/**
 * @method \Pyz\Zed\TenantOnboarding\Business\TenantOnboardingFacadeInterface getFacade()
 * @method \Pyz\Zed\TenantOnboarding\Communication\TenantOnboardingCommunicationFactory getFactory()
 * @method \Pyz\Zed\TenantOnboarding\TenantOnboardingConfig getConfig()
 */
class CreateTenantOnboardingStepPlugin extends AbstractPlugin implements OnboardingStepPluginInterface
{
    /**
     * @return string
     */
    public function getName(): string
    {
        return 'Create Tenant';
    }

    /**
     * @param \Generated\Shared\Transfer\TenantRegistrationTransfer $registrationTransfer
     *
     * @return \Generated\Shared\Transfer\TenantOnboardingStepResultTransfer
     */
    public function execute(TenantRegistrationTransfer $registrationTransfer): TenantOnboardingStepResultTransfer
    {
        $result = new TenantOnboardingStepResultTransfer();
        $result->setIsSuccessful(true);

        try {
            // Prepare tenant data with all additional information
            $tenantData = [
                'identifier' => $registrationTransfer->getTenantName(),
                'companyName' => $registrationTransfer->getCompanyName(),
                'email' => $registrationTransfer->getEmail(),
                'registrationDate' => $registrationTransfer->getCreatedAt(),
                'status' => $registrationTransfer->getStatus(),
            ];

            // Create tenant host from tenant name
            $tenantHost = $this->generateTenantHost($registrationTransfer->getTenantName());

            $tenantTransfer = new TenantTransfer();
            $tenantTransfer->setIdentifier($registrationTransfer->getTenantName());
            $tenantTransfer->setTenantHost($tenantHost);
            $tenantTransfer->setData(json_encode($tenantData));

            $createdTenant = $this->getFacade()->createTenant($tenantTransfer);

            $result->setContext([
                'tenant_id' => $createdTenant->getIdTenant(),
                'tenant_identifier' => $createdTenant->getIdentifier(),
                'tenant_host' => $createdTenant->getTenantHost(),
            ]);

        } catch (\Exception $e) {
            $result->setIsSuccessful(false);
            $result->addError('Failed to create tenant: ' . $e->getMessage());
        }

        return $result;
    }

    /**
     * @param string $tenantName
     *
     * @return string
     */
    protected function generateTenantHost(string $tenantName): string
    {
        return 'yves.eu.spryker.local';
    }
}
