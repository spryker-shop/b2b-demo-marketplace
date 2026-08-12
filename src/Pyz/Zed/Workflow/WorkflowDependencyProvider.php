<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\Workflow;

use Pyz\Zed\ExampleWorkflow\Communication\Plugin\StateMachine\CompanyCreateStateMachineProcessTriggerPlugin;
use Pyz\Zed\ExampleWorkflow\Communication\Plugin\StateMachine\CompanyHasCompanyUserInCustomerGroupConditionPlugin;
use Pyz\Zed\ExampleWorkflow\Communication\Plugin\StateMachine\CompanyIsBusinessVerifiedConditionPlugin;
use Pyz\Zed\ExampleWorkflow\Communication\Plugin\StateMachine\CompanyMarkActiveAndApprovedCommandPlugin;
use Spryker\Zed\Workflow\WorkflowDependencyProvider as SprykerWorkflowDependencyProvider;

class WorkflowDependencyProvider extends SprykerWorkflowDependencyProvider
{
    /**
     * @return array<\Spryker\Zed\Workflow\Dependency\Plugin\WorkflowCommandPluginInterface>
     */
    protected function getCommandPlugins(): array
    {
        return [
            new CompanyMarkActiveAndApprovedCommandPlugin(),
        ];
    }

    /**
     * @return array<\Spryker\Zed\Workflow\Dependency\Plugin\WorkflowConditionPluginInterface>
     */
    protected function getConditionPlugins(): array
    {
        return [
            new CompanyIsBusinessVerifiedConditionPlugin(),
            new CompanyHasCompanyUserInCustomerGroupConditionPlugin(),
        ];
    }

    /**
     * @return array<\Spryker\Zed\Workflow\Dependency\Plugin\StateMachineProcessTriggerPluginInterface>
     */
    protected function getTriggerPlugins(): array
    {
        return [
            new CompanyCreateStateMachineProcessTriggerPlugin(),
        ];
    }
}
