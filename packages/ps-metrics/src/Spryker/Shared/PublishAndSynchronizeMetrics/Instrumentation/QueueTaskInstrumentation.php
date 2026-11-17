<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Shared\PublishAndSynchronizeMetrics\Instrumentation;

use Spryker\Shared\PublishAndSynchronizeMetrics\Metrics\JsonLinesTaskRunMetricsWriter;
use Spryker\Shared\PublishAndSynchronizeMetrics\PublishAndSynchronizeMetricsConstants;
use Spryker\Zed\Event\Communication\Plugin\Queue\EventQueueMessageProcessorPlugin;
use Spryker\Zed\Queue\Business\Task\TaskManager;
use Spryker\Zed\Synchronization\Communication\Plugin\Queue\SynchronizationSearchQueueMessageProcessorPlugin;
use Spryker\Zed\Synchronization\Communication\Plugin\Queue\SynchronizationStorageQueueMessageProcessorPlugin;
use Throwable;
use function OpenTelemetry\Instrumentation\hook;

/**
 * Observes one queue task run without modifying any of the classes involved.
 *
 * Three hooks compose the record that an inline patch used to build from inside TaskManager::run():
 * the run itself gives the queue name, the total duration and the message counts; getChunkSize()
 * gives the configured chunk, which is otherwise only a local; and the processor plugin gives the
 * batch in hand, which is where message age and payload bytes can be read.
 */
class QueueTaskInstrumentation
{
    /**
     * The complete set of concrete processor plugins. An interface cannot be hooked, so each
     * implementation is named; the project wires only these three.
     *
     * @var array<class-string>
     */
    protected const array PROCESSOR_PLUGINS = [
        EventQueueMessageProcessorPlugin::class,
        SynchronizationStorageQueueMessageProcessorPlugin::class,
        SynchronizationSearchQueueMessageProcessorPlugin::class,
    ];

    protected static bool $isRegistered = false;

    protected static float|int|null $taskRunStartedAt = null;

    protected static float|int|null $processingStartedAt = null;

    protected static ?int $breakdownToken = null;

    /**
     * @var array<string, int>|null
     */
    protected static ?array $cpuScope = null;

    /**
     * @return void
     */
    public static function register(): void
    {
        if (static::$isRegistered || !function_exists('OpenTelemetry\Instrumentation\hook')) {
            return;
        }

        static::$isRegistered = true;

        static::hookTaskRun();
        static::hookChunkSize();
        static::hookProcessors();
    }

    /**
     * @return void
     */
    protected static function hookTaskRun(): void
    {
        hook(
            class: TaskManager::class,
            function: 'run',
            pre: static function (object $taskManager, array $params): void {
                static::onTaskRunStart($params[0] ?? null);
            },
            post: static function (object $taskManager, array $params, mixed $response, ?Throwable $throwable): void {
                static::onTaskRunEnd($throwable);
            },
        );
    }

    /**
     * @return void
     */
    protected static function hookChunkSize(): void
    {
        hook(
            class: TaskManager::class,
            function: 'getChunkSize',
            post: static function (object $taskManager, array $params, mixed $response, ?Throwable $throwable): void {
                if (is_int($response)) {
                    TaskRunCollector::getInstance()->setConfiguredChunkSize($response);
                }
            },
        );
    }

    /**
     * @return void
     */
    protected static function hookProcessors(): void
    {
        foreach (static::PROCESSOR_PLUGINS as $processorPluginClass) {
            hook(
                class: $processorPluginClass,
                function: 'processMessages',
                pre: static function (object $plugin, array $params): void {
                    static::onProcessingStart($params[0] ?? null);
                },
                post: static function (object $plugin, array $params, mixed $response, ?Throwable $throwable): void {
                    static::onProcessingEnd($response, $throwable);
                },
            );
        }
    }

    /**
     * @param mixed $queueName
     *
     * @return void
     */
    protected static function onTaskRunStart(mixed $queueName): void
    {
        $memoryTracker = MemoryTracker::getInstance();
        $memoryTracker->observe();
        $taskRunCollector = TaskRunCollector::getInstance();
        $taskRunCollector->setMemoryStart($memoryTracker->readRealUsageBytes());

        static::$cpuScope = CpuTracker::getInstance()->beginScope();
        static::$taskRunStartedAt = hrtime(true);
        static::$breakdownToken = BreakdownCollector::getInstance()->openSpan();

        if (is_string($queueName)) {
            $taskRunCollector->setQueueName($queueName);
        }
    }

    /**
     * Runs on a throwing run as well: the post hook receives the throwable instead of a response,
     * and a batch that failed is the one record that must not go missing.
     *
     * @param \Throwable|null $throwable
     *
     * @return void
     */
    protected static function onTaskRunEnd(?Throwable $throwable): void
    {
        // Closed first, before any early return, so an idle poll never leaves a span open.
        $breakdown = BreakdownCollector::getInstance()->closeSpan(static::$breakdownToken);
        static::$breakdownToken = null;
        $cpu = CpuTracker::getInstance()->endScope(static::$cpuScope);
        static::$cpuScope = null;

        // Flushed per task run rather than only at shutdown: a shutdown function never runs when the
        // process is killed, which would lose every listener record of that run.
        ListenerMetricsWriter::flush();
        ListenerMetricsCollector::reset();

        $startedAt = static::$taskRunStartedAt;
        static::$taskRunStartedAt = null;

        if ($startedAt === null) {
            return;
        }

        $collector = TaskRunCollector::getInstance();
        $seconds = (hrtime(true) - $startedAt) / 1e9;

        // An idle poll carries no information and would otherwise dominate the output.
        if (!$collector->hasMessages()) {
            TaskRunCollector::reset();

            return;
        }

        if ($throwable !== null) {
            $collector->recordThrowable($throwable);
        }

        $collector->setBreakdown($breakdown);
        $collector->setCpu($cpu);
        $collector->setMemoryPeak(MemoryTracker::getInstance()->observe());
        static::createWriter()->write($collector->buildRecord($seconds));
        TaskRunCollector::reset();
    }

    /**
     * @param mixed $queueReceiveMessageTransfers
     *
     * @return void
     */
    protected static function onProcessingStart(mixed $queueReceiveMessageTransfers): void
    {
        static::$processingStartedAt = hrtime(true);

        if (is_array($queueReceiveMessageTransfers)) {
            TaskRunCollector::getInstance()->beforeProcessing($queueReceiveMessageTransfers);
        }
    }

    /**
     * @param mixed $processedMessages
     * @param \Throwable|null $throwable
     *
     * @return void
     */
    protected static function onProcessingEnd(mixed $processedMessages, ?Throwable $throwable): void
    {
        $startedAt = static::$processingStartedAt;
        static::$processingStartedAt = null;

        if ($startedAt === null) {
            return;
        }

        $collector = TaskRunCollector::getInstance();
        $collector->afterProcessing(
            is_array($processedMessages) ? count($processedMessages) : 0,
            (hrtime(true) - $startedAt) / 1e9,
        );

        if ($throwable !== null) {
            $collector->recordThrowable($throwable);

            return;
        }

        if (is_array($processedMessages)) {
            $collector->recordOutcome($processedMessages);
        }
    }

    /**
     * @return \Spryker\Shared\PublishAndSynchronizeMetrics\Metrics\JsonLinesTaskRunMetricsWriter
     */
    protected static function createWriter(): JsonLinesTaskRunMetricsWriter
    {
        $path = getenv(PublishAndSynchronizeMetricsConstants::ENV_OUTPUT_FILE);

        return new JsonLinesTaskRunMetricsWriter(
            $path !== false && $path !== '' ? $path : sys_get_temp_dir() . '/task-runs.jsonl',
        );
    }
}
