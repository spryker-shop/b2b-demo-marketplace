<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\AclEntity;

use Spryker\Zed\AclEntity\AclEntityDependencyProvider as SprykerAclEntityDependencyProvider;
use Spryker\Zed\AclMerchantPortal\Communication\Plugin\AclEntity\MerchantPortalConfigurationAclEntityMetadataConfigExpanderPlugin;

class AclEntityDependencyProvider extends SprykerAclEntityDependencyProvider
{
    /**
     * @return array<\Spryker\Zed\AclEntityExtension\Dependency\Plugin\AclEntityMetadataConfigExpanderPluginInterface>
     */
    protected function getAclEntityMetadataCollectionExpanderPlugins(): array
    {
        return [
            new MerchantPortalConfigurationAclEntityMetadataConfigExpanderPlugin(),
        ];
    }
}
