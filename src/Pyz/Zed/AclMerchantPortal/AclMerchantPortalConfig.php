<?php

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
