<?php

declare(strict_types = 1);

namespace Pyz\Zed\Queue\Business\Worker;

use Psr\Log\LoggerInterface;
use Pyz\Zed\Queue\Business\Strategy\QueueProcessingStrategyInterface;
use Pyz\Zed\Queue\Business\SystemResources\SystemResourcesManagerInterface;
use Pyz\Zed\Queue\QueueConfig;
use SplFixedArray;
use Spryker\Client\Queue\QueueClientInterface;
use Spryker\Shared\Queue\QueueConfig as SharedQueueConfig;
use Spryker\Zed\Queue\Business\Process\ProcessManagerInterface;
use Spryker\Zed\Queue\Business\SignalHandler\SignalDispatcherInterface;
use Spryker\Zed\Queue\Business\Worker\WorkerInterface;
use Spryker\Zed\Queue\Communication\Console\QueueWorkerConsole;

class WorkerV2 implements WorkerInterface
{
    /**
     * @var int
     */
    public const RETRY_INTERVAL_SECONDS = 5;

    /**
     * @var int
     */
    public const TOTAL_WAIT_TIME_IN_SECONDS = 60;

    /**
     * @var int
     */
    public const DEFAULT_MAX_QUEUE_WORKER = 10;

    /**
     * @var \Spryker\Zed\Queue\Business\Process\ProcessManagerInterface
     */
    protected ProcessManagerInterface $processManager;

    /**
     * @var \Pyz\Zed\Queue\QueueConfig
     */
    protected QueueConfig $queueConfig;

    /**
     * @var \Spryker\Client\Queue\QueueClientInterface
     */
    protected QueueClientInterface $queueClient;

    /**
     * @var array<string>
     */
    protected array $queueNames;

    /**
     * @var \Spryker\Zed\Queue\Business\SignalHandler\SignalDispatcherInterface
     */
    protected SignalDispatcherInterface $signalDispatcher;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected LoggerInterface $logger;

    /**
     * @var \Pyz\Zed\Queue\Business\Strategy\QueueProcessingStrategyInterface
     */
    protected QueueProcessingStrategyInterface $queueProcessingStrategy;

    /**
     * @var \SplFixedArray<\Symfony\Component\Process\Process>
     */
    protected SplFixedArray $processes;

    private int $runningProcessesCount = 0;

    private WorkerStats $stats;

    private SystemResourcesManagerInterface $sysResManager;

    protected array $processExecutionTimes = [];

    public function __construct(
        ProcessManagerInterface $processManager,
        QueueConfig $queueConfig,
        QueueClientInterface $queueClient,
        array $queueNames,
        QueueProcessingStrategyInterface $queueProcessingStrategy,
        SignalDispatcherInterface $signalDispatcher,
        SystemResourcesManagerInterface $sysResManager,
        LoggerInterface $logger,
    ) {
        $this->processManager = $processManager;
        $this->queueConfig = $queueConfig;
        $this->queueClient = $queueClient;
        $this->queueNames = $queueNames;
        $this->queueProcessingStrategy = $queueProcessingStrategy;
        $this->signalDispatcher = $signalDispatcher;
        $this->signalDispatcher->dispatch($this->queueConfig->getSignalsForGracefulWorkerShutdown());
        $this->sysResManager = $sysResManager;
        $this->logger = $logger;

        $this->processes = new SplFixedArray($this->queueConfig->getQueueWorkerMaxProcesses());
        $this->stats = new WorkerStats();
    }

    public function start(string $command, array $options = []): void
    {
        $maxThreshold = $this->queueConfig->getQueueWorkerMaxThreshold();
        $delayIntervalMilliseconds = $this->queueConfig->getQueueWorkerInterval();
        $shouldIgnoreZeroMemory = $this->queueConfig->shouldIgnoreNotDetectedFreeMemory();

        $loopPassedSeconds = 0;
        $totalPassedSeconds = 0;
        $startTime = microtime(true);
        $lastStart = 0;
        $prevSecondsValue = 0;

        while ($totalPassedSeconds < $maxThreshold) {
            $this->stats->addCycle();

            /*
             * We have a choice on what to do in case we failed to determine free memory (e.g. 0)
             *   A. consider it as a no go - like we have NO free memory, so no processes will run
             *   B. just ignore memory limit then, but alert in logs
             */
            if (!$this->sysResManager->enoughResources($shouldIgnoreZeroMemory)) {
                $this->logger->debug('NO MEMORY');
                $this->stats->addNoMemCycle()->addSkipCycle();

                continue;
            }

            $freeIndex = $this->removeFinishedProcesses();
            if ($freeIndex === null) {
                $this->logger->debug('BUSY: no free slots available for a new process, waiting');
                $this->stats->addNoSlotCycle()->addSkipCycle();
            } elseif ((microtime(true) - $lastStart) * 1000 > $delayIntervalMilliseconds) {
                $lastStart = microtime(true);
                $this->executeQueueProcessingStrategy($freeIndex);
            } else {
                $this->stats->addCooldownCycle()->addSkipCycle();
            }

            if ($loopPassedSeconds >= 1) {
                $totalPassedSeconds++;
                $startTime = microtime(true);
            }

            if ($prevSecondsValue != $totalPassedSeconds && $totalPassedSeconds % 10 == 0) {
                $prevSecondsValue = $totalPassedSeconds;
                $this->logger->info(sprintf('TIME: %d sec', $totalPassedSeconds));
            }

            $loopPassedSeconds = microtime(true) - $startTime;
        }

        // to re-scan previously logged processes and update stats
        $this->removeFinishedProcesses();
        $this->processManager->flushIdleProcesses();

        $this->waitForPendingProcesses();

        $this->logger->info('DONE');

        foreach ($this->processExecutionTimes as $command => $executionTimes) {
            $this->logger->info(
                sprintf(
                    '(%s) ran %d times, average time: %f s',
                    $command,
                    count($executionTimes),
                    array_sum($executionTimes) / count($executionTimes),
                ),
            );
        }

        $this->logger->info(var_export($this->stats->getStats(), true));
        $this->logger->info(sprintf('Success Rate = %d%%', $this->stats->getSuccessRate()));
        $this->logger->info(var_export($this->stats->getCycleEfficiency(), true));
    }

    /**
     * @param int $totalWaitTimeSeconds
     *
     * @return void
     */
    protected function waitForPendingProcesses(
        int $totalWaitTimeSeconds = 0,
    ): void {
        if ($totalWaitTimeSeconds > static::TOTAL_WAIT_TIME_IN_SECONDS) {
            $this->logger->info(sprintf('Waited longer than %d seconds, exiting now', $totalWaitTimeSeconds));

            return;
        }

        $pendingProcesses = $this->getPendingProcesses($this->processes);
        $nPendingProcesses = count($pendingProcesses);
        $this->logger->info(sprintf('Number of pending processes: %d', $nPendingProcesses));
        if ($nPendingProcesses === 0) {
            return;
        }

        $totalWaitTimeSeconds += static::RETRY_INTERVAL_SECONDS;
        $this->logger->info(sprintf('Waiting for %d seconds before exiting', static::RETRY_INTERVAL_SECONDS));
        sleep(static::RETRY_INTERVAL_SECONDS);

        $this->removeFinishedProcesses();

        $this->waitForPendingProcesses($totalWaitTimeSeconds);
    }

    /**
     * @param \SplFixedArray<\Symfony\Component\Process\Process> $processes
     *
     * @return array<\Symfony\Component\Process\Process>
     */
    protected function getPendingProcesses(SplFixedArray $processes): array
    {
        $pendingProcesses = [];
        foreach ($processes as $process) {
            if ($process && $this->processManager->isProcessRunning($process->getPid())) {
                $pendingProcesses[] = $process;
            }
        }

        return $pendingProcesses;
    }

    /**
     * Runs as many times as it can per X minutes (default 1)
     *
     * Strategy defines:
     *  - what to run and how many
     *  - what is current and next proc
     *  - what it needs to make a decision
     *
     * Strategy can be different, later on we can inject some
     * smart strategy that will delegate actual processing to another one
     * depending on something
     *
     * @param int $freeIndex
     *
     * @return void
     */
    protected function executeQueueProcessingStrategy(int $freeIndex): void
    {
        $queueIterator = $this->queueProcessingStrategy->getNextQueue();
        if (!$queueIterator->valid()) {
            $this->logger->info('EMPTY: no more queues to process');
            $this->stats->addEmptyCycle()->addSkipCycle();

            return;
        }

        /** @var \Generated\Shared\Transfer\QueueTransfer $queueTransfer */
        $queueTransfer = $queueIterator->current();

        $busyProcessNumber = $this->processManager->getBusyProcessNumber($queueTransfer->getQueueName());
        $maxQueueWorker = $this->getMaxQueueWorker($queueTransfer->getQueueName());
        $availableWorkerSlots = $maxQueueWorker - $busyProcessNumber;
        if ($availableWorkerSlots <= 0) {
            return;
        }

        $processCommand = sprintf(
            '%s %s',
            QueueWorkerConsole::QUEUE_RUNNER_COMMAND,
            $queueTransfer->getQueueName(),
        );

        $this->logger->info(sprintf(
            'RUN [%d +1] %s',
            $this->runningProcessesCount,
            $queueTransfer->getQueueName(),
        ));

        $process = $this->processManager->triggerQueueProcess(
            $processCommand,
            sprintf('%s', $queueTransfer->getQueueName()),
        );
        $this->processes[$freeIndex] = $process;
        $this->runningProcessesCount++;

        $this->stats->addProcQty('new');
        $this->stats->addQueueQty($queueTransfer->getQueueName());
        $this->stats->addQueueQty(sprintf('%s', $queueTransfer->getQueueName()));

        $queueIterator->next();
    }

    /**
     * Removes finished processes from the processes array
     * Returns the first index of the array that is available for new processes
     *
     * @return int|null
     */
    protected function removeFinishedProcesses(): ?int
    {
        $freeIndex = -1;
        $runningProcCount = 0;

        foreach ($this->processes as $idx => $process) {
            if (!$process) {
                $freeIndex = $freeIndex >= 0 ? $freeIndex : $idx;

                continue;
            }

            if ($process->isRunning()) {
                $runningProcCount++;

                continue;
            }

            unset($this->processes[$idx]); // won't affect foreach

            $freeIndex = $freeIndex >= 0 ? $freeIndex : $idx;

            $now = microtime(true);
            $timeSpentForProcess = $now - $process->getStartTime();

            $this->processExecutionTimes[$process->getCommandLine()][] = $timeSpentForProcess;
            $this->logger->info(sprintf('DONE %s (%s) (Time: %f s).', $process->getExitCodeText(), $process->getCommandLine(), $timeSpentForProcess));
            if ($process->getExitCode() !== 0) {
                $this->stats->addProcQty('failed');

                $this->logger->error(sprintf('> --- FREE: %d MB', $this->sysResManager->getFreeMemory()));
                $this->logger->error($process->getCommandLine());
                $this->logger->error('Std output:' . $process->getOutput());
                $this->logger->error('Error output: ' . $process->getErrorOutput());
                $this->logger->error('< ---');
            }

            $this->stats->addErrorQty($process->getExitCodeText());
        }

        if ($this->runningProcessesCount !== $runningProcCount) {
            $this->logger->info(sprintf('RUNNING PROC = %d', $runningProcCount));
        }

        // current vs previous
        $this->stats->addProcQty('max', (int)max($this->runningProcessesCount, $runningProcCount));

        $this->runningProcessesCount = $runningProcCount; // current

        return $runningProcCount === $this->processes->count() ?
            null :
            $freeIndex;
    }

    protected function getMaxQueueWorker(string $queueName): int
    {
        $adapterConfiguration = $this->queueConfig->getQueueAdapterConfiguration();
        if (!$adapterConfiguration || !array_key_exists($queueName, $adapterConfiguration)) {
            $adapterConfiguration = $this->getQueueAdapterDefaultConfiguration($queueName);
        }
        $queueAdapterConfiguration = $adapterConfiguration[$queueName];

        if (!array_key_exists(SharedQueueConfig::CONFIG_MAX_WORKER_NUMBER, $queueAdapterConfiguration)) {
            return static::DEFAULT_MAX_QUEUE_WORKER;
        }

        return $queueAdapterConfiguration[SharedQueueConfig::CONFIG_MAX_WORKER_NUMBER];
    }

    protected function getQueueAdapterDefaultConfiguration(string $queueName): array
    {
        $adapterConfiguration = $this->queueConfig->getDefaultQueueAdapterConfiguration();

        if ($adapterConfiguration) {
            return [
                $queueName => $adapterConfiguration,
            ];
        }

        return [];
    }
}
