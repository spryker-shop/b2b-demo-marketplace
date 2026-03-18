<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\SecurityOauthUser;

use Spryker\Zed\SecurityOauthUser\SecurityOauthUserConfig as SprykerSecurityOauthUserConfig;

class SecurityOauthUserConfig extends SprykerSecurityOauthUserConfig
{
    protected const string HOME_PATH = '/dashboard';
}
