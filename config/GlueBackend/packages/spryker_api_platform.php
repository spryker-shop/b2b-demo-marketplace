<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

/**
 * @see config/README.md for more information about this configuration.
 */
use Symfony\Config\SprykerApiPlatformConfig;

return static function (SprykerApiPlatformConfig $sprykerApiPlatform): void {
    $sprykerApiPlatform->apiTypes(['backend']);

    // The following configuration is optional. By default, the source directories are set to 'src/Spryker', 'src/SprykerFeature', and 'src/Pyz'.
    $sprykerApiPlatform->sourceDirectories([
        'vendor/spryker',
        'vendor/spryker-shop',
        'src/Pyz',
    ]);

    //    $sprykerApiPlatform->generatedDir('src/Generated/Api');
    //    $sprykerApiPlatform->cacheDir('%kernel.cache_dir%/api-generator');
};
