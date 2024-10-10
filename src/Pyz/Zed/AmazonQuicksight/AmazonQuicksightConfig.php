<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Pyz\Zed\AmazonQuicksight;

use SprykerEco\Zed\AmazonQuicksight\AmazonQuicksightConfig as SprykerEcoAmazonQuicksightConfig;

class AmazonQuicksightConfig extends SprykerEcoAmazonQuicksightConfig
{
    /**
     * @var string
     */
    protected const DEFAULT_DATA_SOURCE_ID = 'B2BMPSprykerDefaultDataSource';

    /**
     * @var string
     */
    protected const ASSET_BUNDLE_IMPORT_FILE_PATH = '%s/src/Pyz/Zed/AmazonQuicksight/data/asset-bundle.zip';

    /**
     * @return string
     */
    public function getAssetBundleImportFilePath(): string
    {
        return sprintf(static::ASSET_BUNDLE_IMPORT_FILE_PATH, APPLICATION_ROOT_DIR);
    }
}
