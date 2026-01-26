<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\Gui;

use Spryker\Zed\Gui\GuiConfig as SprykerGuiConfig;

class GuiConfig extends SprykerGuiConfig
{
    /**
     * @var string
     */
    protected const string NAVIGATION_ICONS_TYPE_DEFAULT = 'google-material';
}
