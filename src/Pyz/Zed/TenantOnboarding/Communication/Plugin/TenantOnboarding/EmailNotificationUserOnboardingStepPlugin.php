<?php

namespace Pyz\Zed\TenantOnboarding\Communication\Plugin\TenantOnboarding;

use Generated\Shared\Transfer\MailTransfer;
use Generated\Shared\Transfer\TenantOnboardingStepResultTransfer;
use Generated\Shared\Transfer\TenantRegistrationTransfer;
use Pyz\Zed\TenantOnboarding\Business\Plugin\OnboardingStepPluginInterface;
use Pyz\Zed\TenantOnboarding\Communication\Plugin\Mail\TenantOnboardingMailTypeBuilderPlugin;
use Spryker\Zed\Kernel\Communication\AbstractPlugin;

/**
 * @method \Pyz\Zed\TenantOnboarding\Business\TenantOnboardingFacadeInterface getFacade()
 * @method \Pyz\Zed\TenantOnboarding\Communication\TenantOnboardingCommunicationFactory getFactory()
 * @method \Pyz\Zed\TenantOnboarding\TenantOnboardingConfig getConfig()
 */
class EmailNotificationUserOnboardingStepPlugin extends AbstractPlugin implements OnboardingStepPluginInterface
{
    public function getName(): string
    {
        return 'Email Notification User';
    }

    public function execute(TenantRegistrationTransfer $tenantRegistrationTransfer): TenantOnboardingStepResultTransfer
    {
        $result = new TenantOnboardingStepResultTransfer();
        $result->setIsSuccessful(true)
            ->setTenantRegistration($tenantRegistrationTransfer);

        $mailTransfer = (new MailTransfer())
            ->setType(TenantOnboardingMailTypeBuilderPlugin::MAIL_TYPE)
            ->setTenantRegistration($tenantRegistrationTransfer);

        $this->getFactory()->getMailFacade()->handleMail($mailTransfer);

        return $result;
    }
}
