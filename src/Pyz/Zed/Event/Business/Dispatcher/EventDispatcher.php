<?php

namespace Pyz\Zed\Event\Business\Dispatcher;

use Spryker\Shared\Kernel\Transfer\TransferInterface;

class EventDispatcher extends \Spryker\Zed\Event\Business\Dispatcher\EventDispatcher
{


    /**
     * @param string $eventName
     * @param \Spryker\Shared\Kernel\Transfer\TransferInterface $transfer
     *
     * @return void
     */
    public function trigger(string $eventName, TransferInterface $transfer): void
    {
        $eventListeners = $this->extractEventListeners($eventName);

        if (count($eventListeners) === 0) {
            return;
        }

        $idTenant = (new \Pyz\Zed\TenantBehavior\Business\TenantBehaviorFacade())->getCurrentTenantId();
        if ($idTenant && method_exists($transfer, 'setIdTenant')) {
            $transfer->setIdTenant($idTenant);
        }

        foreach (clone $eventListeners as $eventListener) {
            if ($eventListener->isHandledInQueue()) {
                $this->eventQueueProducer->enqueueListener(
                    $eventName,
                    $transfer,
                    $eventListener->getListenerName(),
                    $eventListener->getQueuePoolName(),
                    $eventListener->getEventQueueName(),
                );
            } else {
                $this->eventProducer($eventName, $transfer, $eventListener);
            }
            $this->logEventHandle($eventName, $transfer, $eventListener);
        }
    }

    /**
     * @param string $eventName
     * @param array<\Generated\Shared\Transfer\EventEntityTransfer> $transfers
     *
     * @return void
     */
    public function triggerBulk(string $eventName, array $transfers): void
    {
        $eventListeners = $this->extractEventListeners($eventName);

        if (count($eventListeners) === 0) {
            return;
        }

        $idTenant = (new \Pyz\Zed\TenantBehavior\Business\TenantBehaviorFacade())->getCurrentTenantId();
        if ($idTenant) {
            foreach ($transfers as $transfer) {
                $transfer->setIdTenant($idTenant);
            }
        }

        foreach (clone $eventListeners as $eventListener) {
            if ($eventListener->isHandledInQueue()) {
                $this->eventQueueProducer->enqueueListenerBulk(
                    $eventName,
                    $transfers,
                    $eventListener->getListenerName(),
                    $eventListener->getQueuePoolName(),
                    $eventListener->getEventQueueName(),
                );
            } else {
                $this->eventBulkProducer($eventName, $transfers, $eventListener);
            }

            $this->logEventHandleBulk($eventName, $transfers, $eventListener);
        }
    }
}
