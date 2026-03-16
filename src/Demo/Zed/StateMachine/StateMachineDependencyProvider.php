<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\StateMachine;

use Pyz\Zed\StateMachine\StateMachineDependencyProvider as PyzStateMachineDependencyProvider;
use Spryker\Zed\AiFoundation\Communication\Plugin\StateMachine\AiWorkflowStateMachineHandlerPlugin;

class StateMachineDependencyProvider extends PyzStateMachineDependencyProvider
{
    /**
     * @return array<\Spryker\Zed\StateMachine\Dependency\Plugin\StateMachineHandlerInterface>
     */
    protected function getStateMachineHandlers(): array
    {
        return array_merge(
            parent::getStateMachineHandlers(),
            [
                new AiWorkflowStateMachineHandlerPlugin(),
            ],
        );
    }
}
