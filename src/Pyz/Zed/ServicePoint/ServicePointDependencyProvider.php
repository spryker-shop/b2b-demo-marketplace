<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\ServicePoint;

use Spryker\Zed\ProductOfferServicePoint\Communication\Plugin\ServicePoint\ProductOfferServicePointViewSectionPlugin;
use Spryker\Zed\ServicePoint\ServicePointDependencyProvider as SprykerServicePointDependencyProvider;

class ServicePointDependencyProvider extends SprykerServicePointDependencyProvider
{
    /**
     * @return array<\Spryker\Zed\ServicePoint\Dependency\Plugin\ServicePointViewSectionPluginInterface>
     */
    protected function getServicePointViewSectionPlugins(): array
    {
        return [
            new ProductOfferServicePointViewSectionPlugin(),
        ];
    }
}
