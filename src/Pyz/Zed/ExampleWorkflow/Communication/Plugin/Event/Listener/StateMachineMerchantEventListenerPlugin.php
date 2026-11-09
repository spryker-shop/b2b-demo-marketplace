<?php

/**
 * This file is part of the Spryker Commerce OS.
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
 * @method \Pyz\Zed\ExampleWorkflow\ExampleWorkflowConfig getConfig()
 */
class StateMachineMerchantEventListenerPlugin extends AbstractPlugin implements EventHandlerInterface
{
    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @param string $eventName
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

        if ($eventName !== ExampleWorkflowConfig::EVENT_MERCHANT_CREATE) {
            return;
        }

        $this->getFactory()->getWorkflowFacade()->startStateMachineInstance(
            (new StateMachineEventTriggerRequestTransfer())
                ->setEventName(ExampleWorkflowConfig::EVENT_MERCHANT_CREATE)
                ->setSubjectType(ExampleWorkflowConfig::SUBJECT_TYPE_MERCHANT)
                ->setIdentifier((int)$identifier),
        );
    }
}
