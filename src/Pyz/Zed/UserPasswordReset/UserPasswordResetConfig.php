<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\UserPasswordReset;

use Spryker\Zed\UserPasswordReset\UserPasswordResetConfig as SprykerUserPasswordResetConfig;

class UserPasswordResetConfig extends SprykerUserPasswordResetConfig
{
    protected const int PASSWORD_EXPIRATION_TIME_IN_SECONDS = 900;
}
