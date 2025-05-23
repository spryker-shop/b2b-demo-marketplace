<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\UserMerchantPortalGui;

use Spryker\Zed\UserMerchantPortalGui\UserMerchantPortalGuiConfig as SprykerUserMerchantPortalGuiConfig;

class UserMerchantPortalGuiConfig extends SprykerUserMerchantPortalGuiConfig
{
    /**
     * @var bool
     */
    protected const IS_SECURITY_BLOCKER_FOR_MERCHANT_USER_EMAIL_CHANGING_ENABLED = true;

    /**
     * @var bool
     */
    protected const IS_EMAIL_UPDATE_PASSWORD_VERIFICATION_ENABLED = true;
}
