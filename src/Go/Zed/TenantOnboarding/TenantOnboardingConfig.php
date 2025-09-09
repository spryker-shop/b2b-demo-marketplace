<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Go\Zed\TenantOnboarding;

use Generated\Shared\Transfer\PasswordPolicyTransfer;
use Spryker\Shared\Application\ApplicationConstants;
use Spryker\Zed\Kernel\AbstractBundleConfig;

class TenantOnboardingConfig extends AbstractBundleConfig
{
    public const QUEUE_NAME_TENANT_ONBOARDING = 'tenant-onboarding';
    public const QUEUE_NAME_TENANT_ONBOARDING_RETRY = 'tenant-onboarding.retry';
    public const QUEUE_NAME_TENANT_ONBOARDING_ERROR = 'tenant-onboarding.error';

    public const TENANT_REGISTERED_EVENT = 'TenantRegistered';
    public const TENANT_REGISTERED_EVENT_WITH_FULL_DATA = 'TenantRegisteredFullData';
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

    public function isRequestAutoApproved(): bool
    {
        return true;
    }

    public function getStoreFrontHost(): string
    {
        return $this->get(ApplicationConstants::HOST_YVES);
    }

    public function getBackofficeHost(): string
    {
        return $this->get(ApplicationConstants::BASE_URL_ZED);
    }
}
