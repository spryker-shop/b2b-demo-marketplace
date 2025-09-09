<?php

declare(strict_types = 1);

namespace Pyz\Zed\Queue\Business\SystemResources;

use Psr\Log\LoggerInterface;
use Pyz\Zed\Queue\QueueConfig;
use RuntimeException;
use Symfony\Component\Process\Process;
use Throwable;

class SystemResourcesManager implements SystemResourcesManagerInterface
{
    /**
     * @var \Pyz\Zed\Queue\QueueConfig
     */
    private QueueConfig $queueConfig;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @param \Pyz\Zed\Queue\QueueConfig $queueConfig
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        QueueConfig $queueConfig,
        LoggerInterface $logger,
    ) {
        $this->queueConfig = $queueConfig;
        $this->logger = $logger;
    }

    /**
     * Executed multiple times in a loop within X minutes
     *
     * @param bool $shouldIgnore
     *
     * @throws \RuntimeException
     *
     * @return bool
     */
    public function enoughResources(bool $shouldIgnore = false): bool
    {
        $freeMemory = $this->getFreeMemory();
        if ($freeMemory === 0 && !$shouldIgnore) {
            throw new RuntimeException('Could not detect free memory and configured not to ignore that.');
        }

        $this->logger->debug(sprintf('FREE MEM = %d MB', $freeMemory));

        return $freeMemory > $this->queueConfig->getFreeMemoryBuffer();
    }

    /**
     * Result is in MB
     *
     * @return int
     */
    public function getFreeMemory(): int
    {
        $memory = $this->readSystemMemoryInfo();
        if (!preg_match_all('/(Mem\w+[l|e]):\s+(\d+)/msi', $memory, $matches, PREG_SET_ORDER)) {
            return 0;
        }

        if (empty($matches[1][2])) {
            $matches[1][2] = 0;
        }
        $free = round((float)$matches[1][2]) / 1024;

        if (empty($matches[2][2])) {
            $matches[2][2] = 0;
        }
        $available = round((float)$matches[2][2]) / 1024;

        return (int)max($free, $available);
    }

    private function readSystemMemoryInfo(): string
    {
        $memoryReadProcessTimeout = $this->queueConfig->memoryReadProcessTimeout();

        $memory = file_get_contents('/proc/meminfo');
        if ($memory !== false) {
            return $memory;
        }

        $memoryReader = new Process(['cat', '/proc/meminfo'], null, null, null, $memoryReadProcessTimeout);
        try {
            $memoryReader->run(); // blocking call, but we really need that
            $output = $memoryReader->getOutput();
        } catch (Throwable $exception) {
            $this->logger->error($exception->getMessage());
            $this->logger->error($exception->getTraceAsString());

            $output = '';
        }

        return $output;
    }
}
