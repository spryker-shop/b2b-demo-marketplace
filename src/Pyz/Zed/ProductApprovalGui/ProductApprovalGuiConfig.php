<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\ProductApprovalGui;

use Spryker\Zed\ProductApprovalGui\ProductApprovalGuiConfig as SprykerProductApprovalGuiConfig;

class ProductApprovalGuiConfig extends SprykerProductApprovalGuiConfig
{
    /**
     * @return bool
     */
    public function isApprovalStatusTreeCustomizationEnabled(): bool
    {
        return true;
    }
}
