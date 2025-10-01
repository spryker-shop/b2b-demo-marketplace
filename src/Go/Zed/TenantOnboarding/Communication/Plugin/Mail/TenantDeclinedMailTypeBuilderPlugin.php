<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Go\Zed\TenantOnboarding\Communication\Plugin\Mail;

use Generated\Shared\Transfer\MailRecipientTransfer;
use Generated\Shared\Transfer\MailTemplateTransfer;
use Generated\Shared\Transfer\MailTransfer;
use Spryker\Zed\Kernel\Communication\AbstractPlugin;
use Spryker\Zed\MailExtension\Dependency\Plugin\MailTypeBuilderPluginInterface;

/**
 * @method \Go\Zed\TenantOnboarding\TenantOnboardingConfig getConfig()
 * @method \Go\Zed\TenantOnboarding\Business\TenantOnboardingFacadeInterface getFacade()
 * @method \Go\Zed\TenantOnboarding\Communication\TenantOnboardingCommunicationFactory getFactory()
 */
class TenantDeclinedMailTypeBuilderPlugin extends AbstractPlugin implements MailTypeBuilderPluginInterface
{
    /**
     * @var string
     */
    public const MAIL_TYPE = 'tenant onboarding declined';

    /**
     * @var string
     */
    protected const MAIL_TEMPLATE_HTML = 'tenantOnboarding/mail/tenant_onboarding_declined.html.twig';

    /**
     * @var string
     */
    protected const MAIL_TEMPLATE_TEXT = 'tenantOnboarding/mail/tenant_onboarding_declined.text.twig';

    /**
     * {@inheritDoc}
     * - Returns the name of mail for an order invoice mail.
     *
     * @api
     *
     * @return string
     */
    public function getName(): string
    {
        return static::MAIL_TYPE;
    }

    /**
     * {@inheritDoc}
     * - Builds the `MailTransfer` with data for an order invoice mail.
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\MailTransfer $mailTransfer
     *
     * @return \Generated\Shared\Transfer\MailTransfer
     */
    public function build(MailTransfer $mailTransfer): MailTransfer
    {
        $tenantRegistrationTransfer = $mailTransfer->getTenantRegistrationOrFail();
        $tenantRegistrationTransfer->setBackofficeHost(
            $this->getConfig()->getBackofficeHost(),
        );

        return $mailTransfer
            ->setSubject('Your Registration in Spryker.Go is Declined')
            ->addTemplate(
                (new MailTemplateTransfer())
                    ->setName(static::MAIL_TEMPLATE_HTML)
                    ->setIsHtml(true),
            )
            ->addTemplate(
                (new MailTemplateTransfer())
                    ->setName(static::MAIL_TEMPLATE_TEXT)
                    ->setIsHtml(false),
            )
            ->addRecipient(
                (new MailRecipientTransfer())
                    ->setEmail($tenantRegistrationTransfer->getEmailOrFail())
                    ->setName($tenantRegistrationTransfer->getCompanyNameOrFail()),
            );
    }
}
