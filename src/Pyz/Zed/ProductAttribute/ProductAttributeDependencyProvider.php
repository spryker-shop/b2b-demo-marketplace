<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\ProductAttribute;

use Spryker\Zed\ProductAttribute\ProductAttributeDependencyProvider as SprykerProductAttributeDependencyProvider;
use SprykerFeature\Zed\ProductExperienceManagement\Communication\Plugin\ProductAttribute\VisibilityProductAttributeQueryExpanderPlugin;
use SprykerFeature\Zed\ProductExperienceManagement\Communication\Plugin\ProductAttribute\VisibilitySuggestKeysExpanderPlugin;
use SprykerFeature\Zed\ProductExperienceManagement\Communication\Plugin\ProductAttribute\VisibilitySuggestKeysQueryExpanderPlugin;

class ProductAttributeDependencyProvider extends SprykerProductAttributeDependencyProvider
{
    /**
     * @return list<\Spryker\Zed\ProductAttributeExtension\Dependency\Plugin\ProductAttributeQueryExpanderPluginInterface>
     */
    protected function getProductAttributeQueryExpanderPlugins(): array
    {
        return [
            new VisibilityProductAttributeQueryExpanderPlugin(),
        ];
    }

    /**
     * @return list<\Spryker\Zed\ProductAttributeExtension\Dependency\Plugin\SuggestKeysQueryExpanderPluginInterface>
     */
    protected function getSuggestKeysQueryExpanderPlugins(): array
    {
        return [
            new VisibilitySuggestKeysQueryExpanderPlugin(),
        ];
    }

    /**
     * @return list<\Spryker\Zed\ProductAttributeExtension\Dependency\Plugin\SuggestKeysExpanderPluginInterface>
     */
    protected function getSuggestKeysExpanderPlugins(): array
    {
        return [
            new VisibilitySuggestKeysExpanderPlugin(),
        ];
    }
}
