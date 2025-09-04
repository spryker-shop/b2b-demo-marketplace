<?php

declare(strict_types = 1);

namespace Pyz\Zed\Queue\Business\Process;

use Symfony\Component\Process\Process;

interface ProcessManagerInterface
{
    /**
     * @param string $storeCode
     * @param string $command
     * @param string $queue
     *
     * @return \Symfony\Component\Process\Process
     */
    public function triggerQueueProcessForStore(string $storeCode, string $command, string $queue): Process;

    /**
     * @param string $storeCode
     * @param string $queueName
     *
     * @return int
     */
    public function getBusyProcessNumberForStore(string $storeCode, string $queueName): int;
}
