<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\Application;

use Spryker\Zed\Application\ApplicationConfig as SprykerApplicationConfig;

class ApplicationConfig extends SprykerApplicationConfig
{
    protected const string INDEX_ACTION_REDIRECT_URL = '/dashboard';

    /**
     * @inheritDoc
     */
    public function getIndexActionRedirectUrl(): ?string
    {
        return static::INDEX_ACTION_REDIRECT_URL;
    }
}
