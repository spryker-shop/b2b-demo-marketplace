<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\SymfonyMailer\Dependency\External;

use Generated\Shared\Transfer\MailTransfer;
use Spryker\Zed\SymfonyMailer\Dependency\External\SymfonyMailerToSymfonyMailerAdapter as SprykerSymfonyMailerToSymfonyMailerAdapter;

class SymfonyMailerToSymfonyMailerAdapter extends SprykerSymfonyMailerToSymfonyMailerAdapter
{
    /**
     * @var list<string>
     */
    protected const MUTED_EMAIL_PATTERNS = [
        '@acme.com',
    ];

    /**
     * @param \Generated\Shared\Transfer\MailTransfer $mailTransfer
     *
     * @return void
     */
    public function send(MailTransfer $mailTransfer): void
    {
        if ($this->shouldMute($mailTransfer)) {
            return;
        }

        parent::send($mailTransfer);
    }

    /**
     * @param \Generated\Shared\Transfer\MailTransfer $mailTransfer
     *
     * @return bool
     */
    protected function shouldMute(MailTransfer $mailTransfer): bool
    {
        foreach ($mailTransfer->getRecipients() as $recipientTransfer) {
            $email = $recipientTransfer->getEmail();
            if ($email === null) {
                continue;
            }

            foreach (static::MUTED_EMAIL_PATTERNS as $pattern) {
                if (stripos($email, $pattern) !== false) {
                    return true;
                }
            }
        }

        return false;
    }
}
