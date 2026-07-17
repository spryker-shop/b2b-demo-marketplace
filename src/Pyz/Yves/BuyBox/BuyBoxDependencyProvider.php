<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Yves\BuyBox;

use SprykerFeature\Yves\BuyBox\BuyBoxDependencyProvider as SprykerBuyBoxDependencyProvider;
use SprykerFeature\Yves\BuyBox\Plugin\BuyBox\ConfigurableProductBuyBoxRenderConditionPlugin;

class BuyBoxDependencyProvider extends SprykerBuyBoxDependencyProvider
{
    /**
     * @return array<\SprykerFeature\Yves\BuyBox\Dependency\Plugin\BuyBoxRenderConditionPluginInterface>
     */
    protected function getBuyBoxRenderConditionPlugins(): array
    {
        return [
            new ConfigurableProductBuyBoxRenderConditionPlugin(),
        ];
    }
}
