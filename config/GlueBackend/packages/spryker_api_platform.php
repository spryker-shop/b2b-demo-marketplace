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
        'src/Pyz',
        'vendor/spryker',
        'vendor/spryker-shop',
        'vendor/spryker-feature',
    ]);

    // Keep these modules on the legacy Glue REST stack by hiding their API Platform schemas from the generator.
    $sprykerApiPlatform->excludedPathFragments([
        'vendor/spryker/store/resources/api/',
        'vendor/spryker/currency/resources/api/',
        'vendor/spryker/locale/resources/api/',
        'vendor/spryker/store-context/resources/api/',
    ]);
};
