<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\OrderExperienceManagement;

use SprykerFeature\Shared\OrderExperienceManagement\OrderExperienceManagementConfig as SharedOrderExperienceManagementConfig;
use SprykerFeature\Zed\OrderExperienceManagement\OrderExperienceManagementConfig as SprykerOrderExperienceManagementConfig;

class OrderExperienceManagementConfig extends SprykerOrderExperienceManagementConfig
{
    /**
     * @api
     */
    public function getDefaultNotificationWindowHours(): int
    {
        return 18;
    }

    /**
     * @api
     *
     * @return array<string, array<string>>
     */
    public function getReviewReasonGroupMap(): array
    {
        return array_merge_recursive(parent::getReviewReasonGroupMap(), [
            SharedOrderExperienceManagementConfig::REVIEW_REASON_GROUP_UNAVAILABLE => [],
        ]);
    }

    /**
     * @api
     *
     * @return array<string>
     */
    public function getNonPurchasableReviewReasonGroups(): array
    {
        return [
            SharedOrderExperienceManagementConfig::REVIEW_REASON_GROUP_UNAVAILABLE,
            SharedOrderExperienceManagementConfig::REVIEW_REASON_GROUP_DISCONTINUED,
        ];
    }
}
