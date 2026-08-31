<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\ProductExperienceManagement;

use SprykerFeature\Zed\ProductExperienceManagement\Communication\Plugin\ProductCsvImportSchemaPlugin;
use SprykerFeature\Zed\ProductExperienceManagement\ProductExperienceManagementDependencyProvider as SprykerFeatureProductExperienceManagementDependencyProvider;

class ProductExperienceManagementDependencyProvider extends SprykerFeatureProductExperienceManagementDependencyProvider
{
    /**
     * @return array<\SprykerFeature\Zed\ProductExperienceManagement\Business\Dependency\Plugin\ImportSchemaPluginInterface>
     */
    protected function getImportSchemaPlugins(): array
    {
        return [
            new ProductCsvImportSchemaPlugin(),
        ];
    }
}
