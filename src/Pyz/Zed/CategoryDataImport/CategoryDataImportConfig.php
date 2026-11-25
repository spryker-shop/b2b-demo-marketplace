<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\CategoryDataImport;

use Spryker\Zed\CategoryDataImport\CategoryDataImportConfig as SprykerCategoryDataImportConfig;

class CategoryDataImportConfig extends SprykerCategoryDataImportConfig
{
    /**
     * Store CH has two locales of languages that other stores use too (de_CH, fr_CH). With language-only
     * URL prefixes their category URLs collide with de_DE and fr_FR, so they need full locale names.
     * Product URLs already use them, see {@link \Pyz\Zed\Product\ProductConfig::isFullLocaleNamesInUrlEnabled()}.
     *
     * @return bool
     */
    public function isFullLocaleNamesInUrlEnabled(): bool
    {
        return true;
    }
}
