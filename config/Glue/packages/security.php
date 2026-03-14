<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

use Spryker\ApiPlatform\Security\ApiUserProvider;
use Spryker\ApiPlatform\Security\OauthAuthenticator;
use Symfony\Config\SecurityConfig;

return static function (SecurityConfig $security): void {
    $security->provider('api_oauth_provider')
        ->id(ApiUserProvider::class);

    $security->firewall('main')
        ->lazy(true)
        ->stateless(true)
        ->provider('api_oauth_provider')
        ->customAuthenticators([OauthAuthenticator::class]);

    // Public by default - individual resources use security expressions for authorization
    $security->accessControl()
        ->path('^/')
        ->roles(['PUBLIC_ACCESS']);
};
