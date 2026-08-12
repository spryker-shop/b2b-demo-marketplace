<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\ExampleWorkflow\Communication\Plugin\StateMachine;

use Generated\Shared\Transfer\StateMachineItemTransfer;
use Orm\Zed\CompanyUser\Persistence\Map\SpyCompanyUserTableMap;
use Orm\Zed\CompanyUser\Persistence\SpyCompanyUserQuery;
use Orm\Zed\CustomerGroup\Persistence\SpyCustomerGroupToCustomerQuery;
use Propel\Runtime\ActiveQuery\Criteria;
use Spryker\Zed\Kernel\Communication\AbstractPlugin;
use Spryker\Zed\Workflow\Dependency\Plugin\WorkflowConditionPluginInterface;

/**
 * Event-less condition for the CompanyOnboarding workflow. It keeps a company resting in
 * "customer group assignment" until at least one of the company's company users has been assigned to any
 * customer group. The condition cron re-evaluates it in the background and advances the instance the first
 * time that becomes true.
 *
 * @method \Pyz\Zed\ExampleWorkflow\Communication\ExampleWorkflowCommunicationFactory getFactory()
 */
class CompanyHasCompanyUserInCustomerGroupConditionPlugin extends AbstractPlugin implements WorkflowConditionPluginInterface
{
    /**
     * @var string
     */
    protected const NAME = 'CompanyOnboarding/HasCompanyUserInCustomerGroup';

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
     * @return bool
     */
    public function check(StateMachineItemTransfer $stateMachineItemTransfer)
    {
        $customerIds = SpyCompanyUserQuery::create()
            ->filterByFkCompany($stateMachineItemTransfer->getIdentifier())
            ->select(SpyCompanyUserTableMap::COL_FK_CUSTOMER)
            ->find()
            ->getData();

        if ($customerIds === []) {
            return false;
        }

        return SpyCustomerGroupToCustomerQuery::create()
            ->filterByFkCustomer($customerIds, Criteria::IN)
            ->exists();
    }
}
