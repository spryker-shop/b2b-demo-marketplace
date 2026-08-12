<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\ExampleWorkflow\Communication\Plugin\Event\Listener;

use Generated\Shared\Transfer\EventEntityTransfer;
use Generated\Shared\Transfer\StateMachineEventTriggerRequestTransfer;
use Pyz\Zed\ExampleWorkflow\ExampleWorkflowConfig;
use Spryker\Shared\Kernel\Transfer\TransferInterface;
use Spryker\Zed\Event\Dependency\Plugin\EventHandlerInterface;
use Spryker\Zed\Kernel\Communication\AbstractPlugin;

/**
 * @method \Pyz\Zed\ExampleWorkflow\Communication\ExampleWorkflowCommunicationFactory getFactory()
 */
class StateMachineCompanyEventListener extends AbstractPlugin implements EventHandlerInterface
{
    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @param \Spryker\Shared\Kernel\Transfer\TransferInterface $transfer
     * @param string $eventName
     *
     * @return void
     */
    public function handle(TransferInterface $transfer, $eventName): void
    {
        if (!$transfer instanceof EventEntityTransfer) {
            return;
        }

        $identifier = $transfer->getId();
        if ($identifier === null) {
            return;
        }

        if ($eventName !== ExampleWorkflowConfig::EVENT_COMPANY_CREATE) {
            return;
        }

        $this->getFactory()->getWorkflowFacade()->startStateMachineInstance(
            (new StateMachineEventTriggerRequestTransfer())
                ->setEventName(ExampleWorkflowConfig::EVENT_COMPANY_CREATE)
                ->setSubjectType(ExampleWorkflowConfig::SUBJECT_TYPE_COMPANY)
                ->setIdentifier((int)$identifier),
        );
    }
}
