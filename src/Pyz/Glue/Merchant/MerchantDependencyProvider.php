<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Glue\Merchant;

use Spryker\Glue\Merchant\MerchantDependencyProvider as SprykerMerchantDependencyProvider;
use Spryker\Glue\MerchantRelationRequest\Api\Backend\Plugin\MerchantRelationRequestResourceExpanderPlugin;
use Spryker\Glue\MerchantRelationRequest\Api\Backend\Plugin\MerchantRelationRequestTransferExpanderPlugin;

class MerchantDependencyProvider extends SprykerMerchantDependencyProvider
{
    /**
     * @return array<\Spryker\Glue\MerchantExtension\Dependency\Plugin\MerchantBackendResourceExpanderPluginInterface>
     */
    protected function getMerchantBackendResourceExpanderPlugins(): array
    {
        return [
            new MerchantRelationRequestResourceExpanderPlugin(),
        ];
    }

    /**
     * @return array<\Spryker\Glue\MerchantExtension\Dependency\Plugin\MerchantBackendTransferExpanderPluginInterface>
     */
    protected function getMerchantBackendTransferExpanderPlugins(): array
    {
        return [
            new MerchantRelationRequestTransferExpanderPlugin(),
        ];
    }
}
