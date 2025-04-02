<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\SspServiceManagement;

use SprykerFeature\Zed\SspServiceManagement\SspServiceManagementConfig as SprykerSspServiceManagementConfig;

class SspServiceManagementConfig extends SprykerSspServiceManagementConfig
{
    /**
     * @return string
     */
    public function getDefaultMerchantReference(): string
    {
        return 'MER000001';
    }
}
