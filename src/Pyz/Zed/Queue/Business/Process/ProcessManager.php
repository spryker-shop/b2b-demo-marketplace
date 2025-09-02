<?php

declare(strict_types = 1);

namespace Pyz\Zed\Queue\Business\Process;

use Spryker\Zed\Queue\Business\Process\ProcessManager as SprykerProcessManager;
use Symfony\Component\Process\Process;

class ProcessManager extends SprykerProcessManager implements ProcessManagerInterface
{
    public function triggerQueueProcessForStore(string $storeCode, string $command, string $queue): Process
    {
        return $this->triggerQueueProcess($command, $this->getStoreBasedQueueName($storeCode, $queue));
    }

    public function getBusyProcessNumberForStore(string $storeCode, string $queueName): int
    {
        return $this->getBusyProcessNumber($this->getStoreBasedQueueName($storeCode, $queueName));
    }

    protected function getStoreBasedQueueName(string $storeCode, string $queueName): string
    {
        return sprintf('%s.%s', $storeCode, $queueName);
    }
}
