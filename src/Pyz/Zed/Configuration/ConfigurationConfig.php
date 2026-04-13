<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\Configuration;

use Spryker\Zed\Configuration\ConfigurationConfig as SprykerConfigurationConfig;

class ConfigurationConfig extends SprykerConfigurationConfig
{
    public function getProjectConfigSchemaPattens(): array
    {
        return array_merge(parent::getProjectConfigSchemaPattens(), [
            'config/configuration',
        ]);
    }
}
