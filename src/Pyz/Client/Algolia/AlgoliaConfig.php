<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Client\Algolia;

use SprykerEco\Client\Algolia\AlgoliaConfig as SprykerEcoAlgoliaConfig;

class AlgoliaConfig extends SprykerEcoAlgoliaConfig
{
    /**
     * Enable product search in frontend.
     */
    public function isSearchInFrontendEnabledForProducts(): bool
    {
        return false;
    }

    /**
     * Enable CMS page search in frontend.
     */
    public function isSearchInFrontendEnabledForCmsPages(): bool
    {
        return false;
    }
}
