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
    $sprykerApiPlatform->apiTypes(['storefront']);

    // The following configuration is optional. By default, the source directories are set to 'src/Pyz'.
    $sprykerApiPlatform->sourceDirectories([
        'src/Pyz',
        'vendor/spryker',
        'vendor/spryker-shop',
        'vendor/spryker-feature',
    ]);

    // Keep these modules on the legacy Glue REST stack by hiding their API Platform schemas from the generator.
    $sprykerApiPlatform->excludedPathFragments([
        'vendor/spryker/customer/resources/api/',
        'vendor/spryker/store/resources/api/',
        'vendor/spryker/authentication/resources/api/',
    ]);
};
