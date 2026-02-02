<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\Refund;

use Spryker\Zed\Refund\RefundDependencyProvider as SprykerRefundDependencyProvider;
class RefundDependencyProvider extends SprykerRefundDependencyProvider
{
    /**
     * @return list<\Spryker\Zed\RefundExtension\Dependency\Plugin\RefundPostSavePluginInterface>
     */
    protected function getRefundPostSavePlugins(): array
    {
        return [];
    }
}
