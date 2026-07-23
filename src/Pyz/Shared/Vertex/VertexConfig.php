<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Shared\Vertex;

use SprykerEco\Shared\Vertex\VertexConfig as SprykerVertexConfig;

class VertexConfig extends SprykerVertexConfig
{
    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function isConfigurationModuleUsed(): bool
    {
        return true;
    }
}
