<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\AclMerchantPortal;

use Spryker\Zed\AclMerchantPortal\AclMerchantPortalConfig as SprykerAclMerchantPortalConfig;

class AclMerchantPortalConfig extends SprykerAclMerchantPortalConfig
{
    /**
     * @return bool
     */
    public function isMerchantToMerchantUserConjunctionByUsernameEnabled(): bool
    {
        return true;
    }
}
