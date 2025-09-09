<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Go\Zed\TenantOnboarding\Communication\Plugin\TenantOnboarding;

use Generated\Shared\Transfer\TenantOnboardingStepResultTransfer;
use Generated\Shared\Transfer\TenantRegistrationTransfer;
use Generated\Shared\Transfer\TenantTransfer;
use Go\Zed\TenantOnboarding\Business\Plugin\OnboardingStepPluginInterface;
use Spryker\Zed\Kernel\Communication\AbstractPlugin;

/**
 * @method \Go\Zed\TenantOnboarding\Business\TenantOnboardingFacadeInterface getFacade()
 * @method \Go\Zed\TenantOnboarding\Communication\TenantOnboardingCommunicationFactory getFactory()
 * @method \Go\Zed\TenantOnboarding\TenantOnboardingConfig getConfig()
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
    public function execute(TenantRegistrationTransfer $tenantRegistrationTransfer): TenantOnboardingStepResultTransfer
    {
        $result = new TenantOnboardingStepResultTransfer();
        $result->setIsSuccessful(true)
            ->setTenantRegistration($tenantRegistrationTransfer);

        $tenantTransfer = $this->getFacade()->findTenantByIdentifier($tenantRegistrationTransfer->getTenantName());
        if ($tenantTransfer) {
            $tenantRegistrationTransfer->setTenant($tenantTransfer);
            $result->setContext([
                'tenant_id' => $tenantTransfer->getIdTenant(),
                'tenant_identifier' => $tenantTransfer->getIdentifier(),
                'tenant_host' => $tenantTransfer->getTenantHost(),
            ]);

            return $result;
        }

        $tenantData = [
            'identifier' => $tenantRegistrationTransfer->getTenantName(),
            'companyName' => $tenantRegistrationTransfer->getCompanyName(),
            'email' => $tenantRegistrationTransfer->getEmail(),
            'registrationDate' => $tenantRegistrationTransfer->getCreatedAt(),
            'status' => $tenantRegistrationTransfer->getStatus(),
        ];

        $tenantHost = $this->generateTenantHost($tenantRegistrationTransfer->getTenantName());

        $tenantTransfer = new TenantTransfer();
        $tenantTransfer->setIdentifier($tenantRegistrationTransfer->getTenantName());
        $tenantTransfer->setTenantHost($tenantHost);
        $tenantTransfer->setData(json_encode($tenantData));

        $createdTenant = $this->getFacade()->createTenant($tenantTransfer);

        $tenantRegistrationTransfer->setTenant($createdTenant);

        $result
            ->setContext([
                'tenant_id' => $createdTenant->getIdTenant(),
                'tenant_identifier' => $createdTenant->getIdentifier(),
                'tenant_host' => $createdTenant->getTenantHost(),
            ]);

        return $result;
    }

    /**
     * @param string $tenantName
     *
     * @return string
     */
    protected function generateTenantHost(string $tenantName): string
    {
        return $tenantName . '.' . $this->getConfig()->getStoreFrontHost();
    }
}
