<?php

namespace Pyz\Zed\Transfer\Business\Model\Generator;

use Psr\Log\LoggerInterface;

class TransferDefinitionBuilder extends \Spryker\Zed\Transfer\Business\Model\Generator\TransferDefinitionBuilder
{
    /**
     * @param \Psr\Log\LoggerInterface $messenger
     *
     * @return array<\Spryker\Zed\Transfer\Business\Model\Generator\ClassDefinitionInterface|\Spryker\Zed\Transfer\Business\Model\Generator\DefinitionInterface>
     */
    public function getDefinitions(LoggerInterface $messenger): array
    {
        $definitions = $this->loader->getDefinitions();
        $definitions = $this->merger->merge($definitions, $messenger);
        foreach ($definitions as $name => $definition) {
            $hasTenent = isset($definition['property']['idTenant']);
            if ($hasTenent) continue;
            $definitions[$name]['property']['idTenant'] = [
                "name" => "idTenant",
                "type" => "string",
                "bundles" => [
                    "TenantBehavior",
                ]
            ];
        }

        return $this->buildDefinitions($definitions, $this->classDefinition);
    }
}
