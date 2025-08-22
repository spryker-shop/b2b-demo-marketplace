<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Pyz\Zed\TenantOnboarding;

use Generated\Shared\Transfer\PasswordPolicyTransfer;
use Spryker\Zed\Kernel\AbstractBundleConfig;

class TenantOnboardingConfig extends AbstractBundleConfig
{
    public const QUEUE_NAME_TENANT_ONBOARDING = 'tenant-onboarding';
    public const QUEUE_NAME_TENANT_ONBOARDING_RETRY = 'tenant-onboarding.retry';
    public const QUEUE_NAME_TENANT_ONBOARDING_ERROR = 'tenant-onboarding.error';
    public const REGISTRATION_STATUS_PENDING = 'pending';
    public const REGISTRATION_STATUS_APPROVED = 'approved';
    public const REGISTRATION_STATUS_DECLINED = 'declined';
    public const REGISTRATION_STATUS_PROCESSING = 'processing';
    public const REGISTRATION_STATUS_COMPLETED = 'completed';
    public const REGISTRATION_STATUS_FAILED = 'failed';

    public const GROUP_TENANT_MANAGER = 'Tenant Manager';
    public const ROLE_TENANT_MANAGER = 'TenantManager';

    /**
     * @return \Generated\Shared\Transfer\PasswordPolicyTransfer
     */
    public function getPasswordPolicy(): PasswordPolicyTransfer
    {
        $policy = new PasswordPolicyTransfer();
        $policy->setMinLength(12);
        $policy->setRequireUpper(true);
        $policy->setRequireLower(true);
        $policy->setRequireNumber(true);
        $policy->setRequireSpecial(true);

        return $policy;
    }

    /**
     * @return string
     */
    public function getOnboardingQueueName(): string
    {
        return static::QUEUE_NAME_TENANT_ONBOARDING;
    }

    /**
     * @return int
     */
    public function getOnboardingMaxAttempts(): int
    {
        return 3;
    }

    /**
     * @return array<string>
     */
    public function getValidRegistrationStatuses(): array
    {
        return [
            static::REGISTRATION_STATUS_PENDING,
            static::REGISTRATION_STATUS_APPROVED,
            static::REGISTRATION_STATUS_DECLINED,
            static::REGISTRATION_STATUS_PROCESSING,
            static::REGISTRATION_STATUS_COMPLETED,
            static::REGISTRATION_STATUS_FAILED,
        ];
    }
}
