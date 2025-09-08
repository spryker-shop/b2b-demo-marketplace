<?php

namespace Go\Zed\Event\Business\Queue\Consumer;

use Spryker\Zed\Event\Dependency\Plugin\EventHandlerInterface;
use Throwable;

class EventQueueConsumer extends \Spryker\Zed\Event\Business\Queue\Consumer\EventQueueConsumer
{
    /**
     * @param array<\Generated\Shared\Transfer\QueueReceiveMessageTransfer> $queueMessageTransfers
     *
     * @return array<\Generated\Shared\Transfer\QueueReceiveMessageTransfer>
     */
    public function processMessages(array $queueMessageTransfers)
    {
        $bulkListener = [];
        $tenantBehaviorFacade = new \Go\Zed\TenantBehavior\Business\TenantBehaviorFacade();
        foreach ($queueMessageTransfers as $queueMessageTransfer) {
            $eventQueueSentMessageBodyTransfer = $this->createEventQueueSentMessageBodyTransfer(
                $queueMessageTransfer->getQueueMessage()->getBody(),
            );

            if (!$this->isMessageBodyValid($eventQueueSentMessageBodyTransfer)) {
                $this->markMessageAsFailed($queueMessageTransfer, 'Message body is not valid');

                continue;
            }

            try {
                $transfer = $this->mapEventTransfer($eventQueueSentMessageBodyTransfer);
                $identifier = method_exists($transfer, 'getId') && $transfer->getId() ? $transfer->getId() : spl_object_id($transfer);
                $tenantReference = method_exists($transfer, 'getTenantReference') && $transfer->getTenantReference() ? $transfer->getTenantReference() : 'N/A';
                $originalTenantId = ($tenantBehaviorFacade)->getCurrentTenantReference();
                $bulkListener[$tenantReference][$eventQueueSentMessageBodyTransfer->getListenerClassName()][$eventQueueSentMessageBodyTransfer->getEventName()][static::EVENT_TRANSFERS][$identifier] = $transfer;
                $bulkListener[$tenantReference][$eventQueueSentMessageBodyTransfer->getListenerClassName()][$eventQueueSentMessageBodyTransfer->getEventName()][static::EVENT_MESSAGES][$identifier] = $queueMessageTransfer;

                $listener = $this->createEventListener($eventQueueSentMessageBodyTransfer->getListenerClassName());
                if ($listener instanceof EventHandlerInterface) {
                    if ($tenantReference !== 'N/A') {
                        ($tenantBehaviorFacade)->setCurrentTenantReference($tenantReference);
                    }
                    $listener->handle($transfer, $eventQueueSentMessageBodyTransfer->getEventName());
                    if ($tenantReference !== 'N/A') {
                        ($tenantBehaviorFacade)->setCurrentTenantReference($originalTenantId);
                    }
                }

                $this->logConsumerAction(
                    sprintf(
                        '"%s" listener "%s" was successfully handled.',
                        $eventQueueSentMessageBodyTransfer->getEventName(),
                        $eventQueueSentMessageBodyTransfer->getListenerClassName(),
                    ),
                );

                $queueMessageTransfer->setAcknowledge(true);
            } catch (Throwable $exception) {
                $errorMessage = $this->createErrorMessage(
                    $eventQueueSentMessageBodyTransfer->getEventName(),
                    $eventQueueSentMessageBodyTransfer->getListenerClassName(),
                    $exception,
                );
                $this->logConsumerAction($errorMessage, $exception);
                $this->handleFailedMessage($queueMessageTransfer, $errorMessage);
            }
        }

        foreach ($bulkListener as $tenantReference => $listenerItems) {
            $originalTenantId = $tenantBehaviorFacade->getCurrentTenantReference();
            if ($tenantReference !== 'N/A') {
                ($tenantBehaviorFacade)->setCurrentTenantReference($tenantReference);
            }
            foreach ($listenerItems as $listenerClassName => $eventItems) {
                $this->handleBulk($eventItems, $listenerClassName);
            }
            if ($tenantReference !== 'N/A') {
                ($tenantBehaviorFacade)->setCurrentTenantReference($originalTenantId);
            }
        }

        return $queueMessageTransfers;
    }
}
