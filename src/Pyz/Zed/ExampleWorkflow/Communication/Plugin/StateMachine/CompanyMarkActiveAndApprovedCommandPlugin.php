<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\ExampleWorkflow\Communication\Plugin\StateMachine;

use Generated\Shared\Transfer\StateMachineItemTransfer;
use Orm\Zed\Company\Persistence\Map\SpyCompanyTableMap;
use Orm\Zed\Company\Persistence\SpyCompanyQuery;
use Spryker\Zed\Kernel\Communication\AbstractPlugin;
use Spryker\Zed\Workflow\Dependency\Plugin\WorkflowCommandPluginInterface;

/**
 * Demo command for the CompanyOnboarding workflow. On the transition "customer group assignment -> approved"
 * it loads the SpyCompany by the state machine identifier (the company id) and marks it active and approved.
 *
 * @method \Pyz\Zed\ExampleWorkflow\Communication\ExampleWorkflowCommunicationFactory getFactory()
 * @method \Pyz\Zed\ExampleWorkflow\ExampleWorkflowConfig getConfig()
 */
class CompanyMarkActiveAndApprovedCommandPlugin extends AbstractPlugin implements WorkflowCommandPluginInterface
{
    /**
     * @var string
     */
    protected const NAME = 'CompanyOnboarding/MarkCompanyActiveAndApproved';

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
     * @param \Generated\Shared\Transfer\StateMachineItemTransfer $stateMachineItemTransfer
     *
     * @return void
     */
    public function run(StateMachineItemTransfer $stateMachineItemTransfer)
    {
        $companyEntity = SpyCompanyQuery::create()
            ->findOneByIdCompany($stateMachineItemTransfer->getIdentifier());

        if ($companyEntity === null) {
            return;
        }

        $companyEntity
            ->setIsActive(true)
            ->setStatus(SpyCompanyTableMap::COL_STATUS_APPROVED)
            ->save();
    }
}
