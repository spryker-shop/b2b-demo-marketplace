<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Pyz\Zed\TenantOnboarding\Business\Processor;

use Generated\Shared\Transfer\TenantOnboardingMessageTransfer;

interface OnboardingProcessorInterface
{
    /**
     * @param \Generated\Shared\Transfer\TenantOnboardingMessageTransfer $messageTransfer
     *
     * @return void
     */
    public function process(TenantOnboardingMessageTransfer $messageTransfer): void;
}