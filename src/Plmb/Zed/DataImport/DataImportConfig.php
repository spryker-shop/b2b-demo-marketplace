<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Plmb\Zed\DataImport;

use Pyz\Zed\DataImport\DataImportConfig as PyzDataImportConfig;

/**
 * Extends the Pyz config rather than the Spryker one so the inherited project overrides
 * (the IMPORT_TYPE_* const block, importer configuration, batch size) are preserved —
 * extending the core class here would silently drop them.
 *
 * Only the region fallback is redefined: the shipped default was the demoshop region 'EU'.
 */
class DataImportConfig extends PyzDataImportConfig
{
    /**
     * @return string|null
     */
    public function getDefaultYamlConfigPath(): ?string
    {
        $regionDir = defined('APPLICATION_REGION') ? APPLICATION_REGION : 'EE';

        return APPLICATION_ROOT_DIR . DIRECTORY_SEPARATOR . 'data/import/local/full_' . $regionDir . '.yml';
    }
}
