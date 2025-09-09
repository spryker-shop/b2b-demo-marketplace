<?php

namespace Go\Zed\TenantOnboarding\Communication\Plugin\TenantOnboarding;

use Generated\Shared\Transfer\MailTransfer;
use Generated\Shared\Transfer\TenantOnboardingStepResultTransfer;
use Generated\Shared\Transfer\TenantRegistrationTransfer;
use Go\Zed\TenantOnboarding\Business\Plugin\OnboardingStepPluginInterface;
use Go\Zed\TenantOnboarding\Communication\Plugin\Mail\TenantOnboardingMailTypeBuilderPlugin;
use Spryker\Zed\Kernel\Communication\AbstractPlugin;

/**
 * @method \Go\Zed\TenantOnboarding\Business\TenantOnboardingFacadeInterface getFacade()
 * @method \Go\Zed\TenantOnboarding\Communication\TenantOnboardingCommunicationFactory getFactory()
 * @method \Go\Zed\TenantOnboarding\TenantOnboardingConfig getConfig()
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
