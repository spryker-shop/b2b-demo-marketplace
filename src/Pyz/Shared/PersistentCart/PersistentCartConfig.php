<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Shared\PersistentCart;

use Spryker\Shared\PersistentCart\PersistentCartConfig as SprykerPersistentCartConfig;

class PersistentCartConfig extends SprykerPersistentCartConfig
{
    protected const bool IS_QUOTE_UPDATE_PLUGINS_INSIDE_CART_ENABLED = true;
}
