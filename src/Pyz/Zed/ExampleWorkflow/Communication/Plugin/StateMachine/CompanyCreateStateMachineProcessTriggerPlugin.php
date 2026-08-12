<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\ExampleWorkflow\Communication\Plugin\StateMachine;

use Spryker\Zed\Kernel\Communication\AbstractPlugin;
use Spryker\Zed\Workflow\Dependency\Plugin\StateMachineProcessTriggerPluginInterface;

class CompanyCreateStateMachineProcessTriggerPlugin extends AbstractPlugin implements StateMachineProcessTriggerPluginInterface
{
    protected const string EVENT_NAME = 'Entity.spy_company.create';

    protected const string NAME = 'Company created';

    protected const string SUBJECT_TYPE = 'Company';

    protected const string DESCRIPTION = 'Fired when a new company is created.';

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
