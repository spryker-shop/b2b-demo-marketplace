<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Pyz\Zed\TenantOnboarding\Communication\Plugin\TenantOnboarding;

use Generated\Shared\Transfer\TenantOnboardingStepResultTransfer;
use Generated\Shared\Transfer\TenantRegistrationTransfer;
use Generated\Shared\Transfer\UserTransfer;
use Pyz\Zed\TenantOnboarding\Business\Plugin\OnboardingStepPluginInterface;
use Pyz\Zed\TenantOnboarding\TenantOnboardingConfig;
use Spryker\Zed\Kernel\Communication\AbstractPlugin;

/**
 * @method TenantOnboardingConfig getConfig()
 * @method \Pyz\Zed\TenantOnboarding\Business\TenantOnboardingFacadeInterface getFacade()
 * @method \Pyz\Zed\TenantOnboarding\Communication\TenantOnboardingCommunicationFactory getFactory()
 */
class CreateBackofficeUserOnboardingStepPlugin extends AbstractPlugin implements OnboardingStepPluginInterface
{
    /**
     * @param \Generated\Shared\Transfer\TenantRegistrationTransfer $tenantRegistrationTransfer
     *
     * @return \Generated\Shared\Transfer\TenantOnboardingStepResultTransfer
     */
    public function execute(TenantRegistrationTransfer $tenantRegistrationTransfer): TenantOnboardingStepResultTransfer
    {
        $result = new TenantOnboardingStepResultTransfer();
        $result->setIsSuccessful(true);

        $userTransfer = (new UserTransfer())
            ->setFirstName($tenantRegistrationTransfer->getCompanyName())
            ->setLastName('Admin')
            ->setUsername($tenantRegistrationTransfer->getEmail())
            ->setEmail($tenantRegistrationTransfer->getEmail())
            ->setPassword($tenantRegistrationTransfer->getPasswordHash())
            ->setLocaleName('en_US')
            ->setIdTenant($tenantRegistrationTransfer->getTenantName());

        $userTransfer = $this->getFactory()->getUserFacade()->createUser($userTransfer);

        $aclGroup = $this->getFactory()->getAclFacade()->getGroupByName(TenantOnboardingConfig::GROUP_TENANT_MANAGER);
        if ($userTransfer->getIdUser() && $aclGroup->getIdAclGroup()) {
            $this->getFactory()->getAclFacade()->addUserToGroup($userTransfer->getIdUserOrFail(), $aclGroup->getIdAclGroupOrFail());
        }

        $result
            ->setTenantRegistration($tenantRegistrationTransfer)
            ->setContext([
                'userId' => $userTransfer->getIdUser(),
                'username' => $userTransfer->getUsername(),
            ])
            ->setIsSuccessful(!!$userTransfer->getIdUser());

        return $result;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'CreateBackofficeUser';
    }
}
