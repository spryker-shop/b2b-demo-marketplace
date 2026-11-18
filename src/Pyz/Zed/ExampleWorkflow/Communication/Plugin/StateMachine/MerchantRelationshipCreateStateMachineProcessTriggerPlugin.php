<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\ExampleWorkflow\Communication\Plugin\StateMachine;

use Pyz\Zed\ExampleWorkflow\ExampleWorkflowConfig;
use Spryker\Zed\Kernel\Communication\AbstractPlugin;
use Spryker\Zed\Workflow\Dependency\Plugin\StateMachineProcessTriggerPluginInterface;

/**
 * @method \Pyz\Zed\ExampleWorkflow\ExampleWorkflowConfig getConfig()
 * @method \Pyz\Zed\ExampleWorkflow\Communication\ExampleWorkflowCommunicationFactory getFactory()
 */
class MerchantRelationshipCreateStateMachineProcessTriggerPlugin extends AbstractPlugin implements StateMachineProcessTriggerPluginInterface
{
    protected const string EVENT_NAME = ExampleWorkflowConfig::EVENT_MERCHANT_RELATIONSHIP_CREATE;

    protected const string NAME = 'Merchant relationship created';

    protected const string SUBJECT_TYPE = ExampleWorkflowConfig::SUBJECT_TYPE_MERCHANT_RELATIONSHIP;

    protected const string DESCRIPTION = 'Fired when a new merchant relationship is created.';

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getEventName(): string
    {
        return static::EVENT_NAME;
    }

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
     */
    public function getDescription(): string
    {
        return static::DESCRIPTION;
    }
}
