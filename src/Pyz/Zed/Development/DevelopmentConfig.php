<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\Development;

use Spryker\Zed\Development\DevelopmentConfig as SprykerDevelopmentConfig;

class DevelopmentConfig extends SprykerDevelopmentConfig
{
    public function getCodingStandard(): string
    {
        return APPLICATION_ROOT_DIR . DIRECTORY_SEPARATOR . 'phpcs.xml';
    }

    /**
     * @return array<string>
     */
    public function getOrganizationPathMap(): array
    {
        return [
            static::NAMESPACE_SPRYKER => $this->getPathToInternalNamespace(static::NAMESPACE_SPRYKER),
            static::NAMESPACE_SPRYKER_FEATURE => $this->getPathToInternalNamespace(static::NAMESPACE_SPRYKER_FEATURE),
            static::NAMESPACE_SPRYKER_ECO => $this->getPathToInternalNamespace(static::NAMESPACE_SPRYKER_ECO),
        ];
    }
}
