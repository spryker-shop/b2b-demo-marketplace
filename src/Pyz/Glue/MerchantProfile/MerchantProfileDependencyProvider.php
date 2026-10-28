<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Glue\MerchantProfile;

use Spryker\Glue\MerchantProfile\MerchantProfileDependencyProvider as SprykerMerchantProfileDependencyProvider;
use Spryker\Glue\MerchantProfile\Plugin\MerchantProfile\HtmlTagWhitelistMerchantProfileValidatorPlugin;
use Spryker\Glue\MerchantProfile\Plugin\MerchantProfile\StoreLocaleMerchantProfileValidatorPlugin;

class MerchantProfileDependencyProvider extends SprykerMerchantProfileDependencyProvider
{
    /**
     * @return array<\Spryker\Glue\MerchantExtension\Dependency\Plugin\MerchantProfileValidatorPluginInterface>
     */
    protected function getMerchantProfileValidatorPlugins(): array
    {
        return [
            new StoreLocaleMerchantProfileValidatorPlugin(),
            new HtmlTagWhitelistMerchantProfileValidatorPlugin(),
        ];
    }
}
