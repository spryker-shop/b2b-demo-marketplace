<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\CategoryPageSearch;

use Spryker\Zed\CategoryPageSearch\CategoryPageSearchDependencyProvider as SprykerCategoryPageSearchDependencyProvider;
use Spryker\Zed\ProductListSearch\Communication\Plugin\CategoryPageSearch\CategoryNodeProductListPageDataExpanderPlugin;

class CategoryPageSearchDependencyProvider extends SprykerCategoryPageSearchDependencyProvider
{
    /**
     * @return array<\Spryker\Zed\CategoryPageSearchExtension\Dependency\Plugin\CategoryNodePageSearchDataExpanderPluginInterface>
     */
    protected function getCategoryNodePageSearchDataExpanderPlugins(): array
    {
        return [
            new CategoryNodeProductListPageDataExpanderPlugin(),
        ];
    }
}
