<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\ProductAttributeGui;

use Spryker\Zed\ProductAttributeGui\ProductAttributeGuiDependencyProvider as SprykerProductAttributeGuiDependencyProvider;
use SprykerFeature\Zed\ProductExperienceManagement\Communication\Plugin\ProductAttributeGui\VisibilityAttributeFormDataProviderExpanderPlugin;
use SprykerFeature\Zed\ProductExperienceManagement\Communication\Plugin\ProductAttributeGui\VisibilityAttributeFormExpanderPlugin;
use SprykerFeature\Zed\ProductExperienceManagement\Communication\Plugin\ProductAttributeGui\VisibilityAttributeFormTransferMapperExpanderPlugin;
use SprykerFeature\Zed\ProductExperienceManagement\Communication\Plugin\ProductAttributeGui\VisibilityAttributeTableConfigExpanderPlugin;
use SprykerFeature\Zed\ProductExperienceManagement\Communication\Plugin\ProductAttributeGui\VisibilityAttributeTableCriteriaExpanderPlugin;
use SprykerFeature\Zed\ProductExperienceManagement\Communication\Plugin\ProductAttributeGui\VisibilityAttributeTableDataExpanderPlugin;
use SprykerFeature\Zed\ProductExperienceManagement\Communication\Plugin\ProductAttributeGui\VisibilityAttributeTableFilterFormExpanderPlugin;
use SprykerFeature\Zed\ProductExperienceManagement\Communication\Plugin\ProductAttributeGui\VisibilityAttributeTableHeaderExpanderPlugin;

class ProductAttributeGuiDependencyProvider extends SprykerProductAttributeGuiDependencyProvider
{
    /**
     * @return array<\Spryker\Zed\ProductAttributeGuiExtension\Dependency\Plugin\AttributeTableConfigExpanderPluginInterface>
     */
    protected function getAttributeTableConfigExpanderPlugins(): array
    {
        return [
            new VisibilityAttributeTableConfigExpanderPlugin(),
        ];
    }

    /**
     * @return array<\Spryker\Zed\ProductAttributeGuiExtension\Dependency\Plugin\AttributeTableHeaderExpanderPluginInterface>
     */
    protected function getAttributeTableHeaderExpanderPlugins(): array
    {
        return [
            new VisibilityAttributeTableHeaderExpanderPlugin(),
        ];
    }

    /**
     * @return array<\Spryker\Zed\ProductAttributeGuiExtension\Dependency\Plugin\AttributeTableDataExpanderPluginInterface>
     */
    protected function getAttributeTableDataExpanderPlugins(): array
    {
        return [
            new VisibilityAttributeTableDataExpanderPlugin(),
        ];
    }

    /**
     * @return array<\Spryker\Zed\ProductAttributeGuiExtension\Dependency\Plugin\AttributeTableCriteriaExpanderPluginInterface>
     */
    protected function getAttributeTableCriteriaExpanderPlugins(): array
    {
        return [
            new VisibilityAttributeTableCriteriaExpanderPlugin(),
        ];
    }

    /**
     * @return array<\Spryker\Zed\ProductAttributeGuiExtension\Dependency\Plugin\AttributeFormExpanderPluginInterface>
     */
    protected function getAttributeFormExpanderPlugins(): array
    {
        return [
            new VisibilityAttributeFormExpanderPlugin(),
        ];
    }

    /**
     * @return array<\Spryker\Zed\ProductAttributeGuiExtension\Dependency\Plugin\AttributeFormDataProviderExpanderPluginInterface>
     */
    protected function getAttributeFormDataProviderExpanderPlugins(): array
    {
        return [
            new VisibilityAttributeFormDataProviderExpanderPlugin(),
        ];
    }

    /**
     * @return array<\Spryker\Zed\ProductAttributeGuiExtension\Dependency\Plugin\AttributeFormTransferMapperExpanderPluginInterface>
     */
    protected function getAttributeFormTransferMapperExpanderPlugins(): array
    {
        return [
            new VisibilityAttributeFormTransferMapperExpanderPlugin(),
        ];
    }

    /**
     * @return array<\Spryker\Zed\ProductAttributeGuiExtension\Dependency\Plugin\AttributeTableFilterFormExpanderPluginInterface>
     */
    protected function getAttributeTableFilterFormExpanderPlugins(): array
    {
        return [
            new VisibilityAttributeTableFilterFormExpanderPlugin(),
        ];
    }
}
