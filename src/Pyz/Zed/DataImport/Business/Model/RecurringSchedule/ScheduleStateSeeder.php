<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\DataImport\Business\Model\RecurringSchedule;

use Orm\Zed\OrderExperienceManagement\Persistence\SpyRecurringSchedule;
use Orm\Zed\OrderExperienceManagement\Persistence\SpyRecurringScheduleHistory;
use Orm\Zed\OrderExperienceManagement\Persistence\SpyRecurringScheduleQuery;
use Orm\Zed\StateMachine\Persistence\SpyStateMachineItemStateHistory;
use Orm\Zed\StateMachine\Persistence\SpyStateMachineItemStateQuery;
use Orm\Zed\StateMachine\Persistence\SpyStateMachineProcessQuery;
use Pyz\Zed\DataImport\Business\Exception\EntityNotFoundException;
use Pyz\Zed\OrderExperienceManagement\OrderExperienceManagementConfig;
use Spryker\Zed\DataImport\Dependency\Service\DataImportToUtilEncodingServiceInterface;
use SprykerFeature\Shared\OrderExperienceManagement\OrderExperienceManagementConfig as SharedOrderExperienceManagementConfig;

class ScheduleStateSeeder
{
    protected const string DATE_FORMAT = 'Y-m-d';

    /**
     * @see \SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\History\RecurringSchedulePlacementHistoryWriter
     * @see \SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\History\RecurringScheduleHistoryFailureReasonEnricher
     */
    protected const string DETAIL_KEY_MESSAGE = 'message';

    protected const string DETAIL_KEY_PARAMETERS = 'parameters';

    protected const string PARAMETER_SKU = '%sku%';

    protected const string GLOSSARY_KEY_PRODUCT_UNAVAILABLE = 'product.unavailable';

    public function __construct(
        protected RecurringOrderSettingsBuilder $recurringOrderSettingsBuilder,
        protected DataImportToUtilEncodingServiceInterface $utilEncodingService,
    ) {
    }

    public function findScheduleBySourceOrder(int $idSalesOrder): ?SpyRecurringSchedule
    {
        return SpyRecurringScheduleQuery::create()
            ->findOneByFkSourceSalesOrder($idSalesOrder);
    }

    public function applyNextTriggerDate(SpyRecurringSchedule $recurringScheduleEntity, string $dateExpression): void
    {
        $nextTriggerDate = $this->recurringOrderSettingsBuilder->resolveRelativeDate($dateExpression);

        $recurringScheduleEntity
            ->setNextTriggerDate($nextTriggerDate->format(static::DATE_FORMAT))
            ->save();
    }

    /**
     * @see \SprykerFeatureTest\Zed\OrderExperienceManagement\OrderExperienceManagementBusinessTester::seedStateMachineReviewState()
     */
    public function applyFailedState(SpyRecurringSchedule $recurringScheduleEntity, ?string $failureSku): void
    {
        $idStateMachineItemState = $this->getIdStateMachineItemState(SharedOrderExperienceManagementConfig::STATUS_FAILED);

        $recurringScheduleEntity
            ->setStatus(SharedOrderExperienceManagementConfig::STATUS_FAILED)
            ->setFkStateMachineItemState($idStateMachineItemState)
            ->save();

        $this->addStateMachineItemStateHistory($idStateMachineItemState, $recurringScheduleEntity->getIdRecurringSchedule());
        $this->addFailedHistory($recurringScheduleEntity, $failureSku);
    }

    protected function getIdStateMachineItemState(string $stateName): int
    {
        $stateMachineProcessEntity = SpyStateMachineProcessQuery::create()
            ->filterByStateMachineName(OrderExperienceManagementConfig::STATE_MACHINE_NAME)
            ->filterByName(OrderExperienceManagementConfig::PROCESS_NAME)
            ->findOne();

        if ($stateMachineProcessEntity === null) {
            throw new EntityNotFoundException(sprintf(
                'State machine process "%s" for state machine "%s" is not found.',
                OrderExperienceManagementConfig::PROCESS_NAME,
                OrderExperienceManagementConfig::STATE_MACHINE_NAME,
            ));
        }

        $stateMachineItemStateEntity = SpyStateMachineItemStateQuery::create()
            ->filterByFkStateMachineProcess($stateMachineProcessEntity->getIdStateMachineProcess())
            ->filterByName($stateName)
            ->findOneOrCreate();
        $stateMachineItemStateEntity->save();

        return $stateMachineItemStateEntity->getIdStateMachineItemState();
    }

    protected function addStateMachineItemStateHistory(int $idStateMachineItemState, int $idRecurringSchedule): void
    {
        (new SpyStateMachineItemStateHistory())
            ->setFkStateMachineItemState($idStateMachineItemState)
            ->setIdentifier($idRecurringSchedule)
            ->save();
    }

    /**
     * @see \SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\History\RecurringScheduleHistoryFailureReasonEnricher
     */
    protected function addFailedHistory(SpyRecurringSchedule $recurringScheduleEntity, ?string $failureSku): void
    {
        $detail = [
            [
                static::DETAIL_KEY_MESSAGE => static::GLOSSARY_KEY_PRODUCT_UNAVAILABLE,
                static::DETAIL_KEY_PARAMETERS => $failureSku !== null && $failureSku !== ''
                    ? [static::PARAMETER_SKU => $failureSku]
                    : [],
            ],
        ];

        (new SpyRecurringScheduleHistory())
            ->setFkRecurringSchedule($recurringScheduleEntity->getIdRecurringSchedule())
            ->setEventType(SharedOrderExperienceManagementConfig::HISTORY_EVENT_TYPE_FAILED)
            ->setDetail((string)$this->utilEncodingService->encodeJson($detail))
            ->save();
    }
}
