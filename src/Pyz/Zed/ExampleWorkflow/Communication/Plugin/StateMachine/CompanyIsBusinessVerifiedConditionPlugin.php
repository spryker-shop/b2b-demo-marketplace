<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\ExampleWorkflow\Communication\Plugin\StateMachine;

use Generated\Shared\Transfer\StateMachineItemTransfer;
use Spryker\Zed\Kernel\Communication\AbstractPlugin;
use Spryker\Zed\Workflow\Dependency\Plugin\WorkflowConditionPluginInterface;

/**
 * Demo condition for the CompanyOnboarding workflow. It gates the timeout-driven, conditional transition
 * "business verification -> contract agreement" and always returns true for demo purposes.
 *
 * @method \Pyz\Zed\ExampleWorkflow\Communication\ExampleWorkflowCommunicationFactory getFactory()
 * @method \Pyz\Zed\ExampleWorkflow\ExampleWorkflowConfig getConfig()
 */
class CompanyIsBusinessVerifiedConditionPlugin extends AbstractPlugin implements WorkflowConditionPluginInterface
{
    /**
     * @var string
     */
    protected const NAME = 'CompanyOnboarding/IsBusinessVerified';

    /**
     * @var string
     */
    protected const SUBJECT_TYPE = 'Company';

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getName(): string
    {
        return static::NAME;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getSubjectType(): string
    {
        return static::SUBJECT_TYPE;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
     *
     * @param \Generated\Shared\Transfer\StateMachineItemTransfer $stateMachineItemTransfer
     *
     * @return bool
     */
    public function check(StateMachineItemTransfer $stateMachineItemTransfer)
    {
        // The demo condition is always satisfied; the item transfer is required by the plugin contract.
        return true;
    }
}
