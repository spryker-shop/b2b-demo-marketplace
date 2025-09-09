<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Go\Zed\TenantOnboarding\Communication\Plugin\TenantOnboarding;

use Generated\Shared\Transfer\TenantOnboardingStepResultTransfer;
use Generated\Shared\Transfer\TenantRegistrationTransfer;
use Generated\Shared\Transfer\UserCriteriaTransfer;
use Generated\Shared\Transfer\UserTransfer;
use Go\Zed\TenantOnboarding\Business\Plugin\OnboardingStepPluginInterface;
use Go\Zed\TenantOnboarding\TenantOnboardingConfig;
use Spryker\Zed\Kernel\Communication\AbstractPlugin;

/**
 * @method TenantOnboardingConfig getConfig()
 * @method \Go\Zed\TenantOnboarding\Business\TenantOnboardingFacadeInterface getFacade()
 * @method \Go\Zed\TenantOnboarding\Communication\TenantOnboardingCommunicationFactory getFactory()
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

        $userCollectionTransfer = $this->getFactory()->getUserFacade()->getUserCollection(
            (new UserCriteriaTransfer())->setUserConditions(
                (new \Generated\Shared\Transfer\UserConditionsTransfer())
                    ->addUsername($tenantRegistrationTransfer->getEmailOrFail())
            )
        );

        if ($userCollectionTransfer->getUsers()->count() > 0) {
            return $result;
        }

        $userTransfer = (new UserTransfer())
            ->setFirstName($tenantRegistrationTransfer->getCompanyNameOrFail())
            ->setLastName('Admin')
            ->setUsername($tenantRegistrationTransfer->getEmailOrFail())
            ->setEmail($tenantRegistrationTransfer->getEmailOrFail())
            ->setPassword($tenantRegistrationTransfer->getPasswordHashOrFail())
            ->setLocaleName('en_US')
            ->setTenantReference($tenantRegistrationTransfer->getTenantNameOrFail());

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
