<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Service\ModuleFinder;

use Spryker\Service\ModuleFinder\ModuleFinderConfig as SprykerModuleFinderConfig;

class ModuleFinderConfig extends SprykerModuleFinderConfig
{
    /**
     * Split installations keep SprykerFeature modules (e.g. self-service-portal) under
     * vendor/spryker-feature/. The core config only scans spryker/spryker-shop/spryker-eco/...,
     * so SprykerFeature modules are never discovered and their Facade/Client/Service are not
     * registered as concrete container services by SprykerDefaultsPass (they fall back to
     * failing lazy proxies in the API Platform Glue container). Adding the fragment makes
     * them discoverable.
     *
     * @var array<string>
     */
    protected array $organizationPathFragments = [
        'spryker',
        'spryker-shop',
        'spryker-eco',
        'spryker-sdk',
        'spryker-middleware',
        'spryker-feature',
    ];

    /**
     * @return array<string>
     */
    public function getInternalOrganizations(): array
    {
        return array_merge(parent::getInternalOrganizations(), ['SprykerFeature']);
    }
}
