<?php

namespace Pyz\Shared\Vertex;

use SprykerEco\Shared\Vertex\VertexConfig as SprykerVertexConfig;

class VertexConfig extends SprykerVertexConfig
{
    public function isConfigurationModuleUsed(): bool
    {
        return false;
    }
}
