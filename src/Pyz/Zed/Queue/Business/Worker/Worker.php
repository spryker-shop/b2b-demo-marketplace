<?php

namespace Pyz\Zed\Queue\Business\Worker;

class Worker extends \Spryker\Zed\Queue\Business\Worker\Worker
{
    /**
     * @param string $command
     * @param array<string, mixed> $options
     * @param int $round
     * @param array<\Symfony\Component\Process\Process> $processes
     *
     * @return void
     */
    public function start(string $command, array $options = [], int $round = 1, array $processes = []): void
    {
        $loopPassedSeconds = 0;
        $totalPassedSeconds = 0;
        $pendingProcesses = [];
        $startTime = $this->getFreshMicroTime();
        $maxThreshold = (int)$this->queueConfig->getQueueWorkerMaxThreshold();
        $delayIntervalMilliseconds = (int)$this->queueConfig->getQueueWorkerInterval();
        $maxNumberOfProcesses = getenv('MAX_NUMBER_OF_WORKER_PROCESSES') ?: 50;

        $this->workerProgressBar->start($maxThreshold, $round);

        while ($this->continueExecution($totalPassedSeconds, $maxThreshold, $options)) {
            if (count($this->getPendingProcesses($processes)) > $maxNumberOfProcesses) {
                usleep($delayIntervalMilliseconds * static::SECOND_TO_MILLISECONDS);
                continue;
            }
            $processes = array_merge($this->executeOperation($command), $processes);
            $pendingProcesses = $this->getPendingProcesses($processes);

            if ($this->isEmptyQueue($pendingProcesses, $options)) {
                return;
            }

            if ($loopPassedSeconds >= 1) {
                $this->workerProgressBar->advance(1);
                $totalPassedSeconds++;
                $startTime = $this->getFreshMicroTime();
            }
            usleep($delayIntervalMilliseconds * static::SECOND_TO_MILLISECONDS);
            $loopPassedSeconds = $this->getFreshMicroTime() - $startTime;
        }

        $this->workerProgressBar->finish();
        $this->processManager->flushIdleProcesses();
        $this->waitForPendingProcesses($pendingProcesses, $command, $round, $delayIntervalMilliseconds, $options);
    }
}
