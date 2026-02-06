<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\RuleEngine;

use Spryker\Zed\RuleEngine\RuleEngineDependencyProvider as SprykerRuleEngineDependencyProvider;

class RuleEngineDependencyProvider extends SprykerRuleEngineDependencyProvider
{
    /**
     * @return list<\Spryker\Zed\RuleEngineExtension\Communication\Dependency\Plugin\RuleSpecificationProviderPluginInterface>
     */
    protected function getRuleSpecificationProviderPlugins(): array
    {
        return [];
    }
}
