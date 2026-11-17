<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Shared\PublishAndSynchronizeMetrics\Instrumentation;

use ReflectionProperty;
use Spryker\Shared\PublishAndSynchronizeMetrics\Metrics\JsonLinesTaskRunMetricsWriter;
use Spryker\Shared\PublishAndSynchronizeMetrics\PublishAndSynchronizeMetricsConstants;
use Spryker\Zed\DataImport\Business\Model\DataImporter;
use Spryker\Zed\Queue\Business\Task\TaskManager;
use Throwable;
use function OpenTelemetry\Instrumentation\hook;

/**
 * Observes the direct-sync flush (contract C6) without modifying any of the classes involved.
 *
 * Under direct sync the storage and search writes are buffered by the synchronization behavior and
 * written by `InMemoryMessageSynchronizer::flushSynchronizationMessages()`, which
 * `DirectSynchronizationConsolePlugin` calls on console terminate: after `TaskManager::run()` and
 * after every importer has returned. The flush is therefore hooked on its own, and two light hooks
 * remember what the process did before it, so the record can say which task or import it belongs to.
 */
class SyncFlushInstrumentation
{
    /**
     * Not imported: spryker/synchronization is not a requirement of this module, and a hook on a class
     * that is not installed is simply never called.
     *
     * @var string
     */
    protected const string SYNCHRONIZER_CLASS = 'Spryker\Zed\Synchronization\Business\Synchronizer\InMemoryMessageSynchronizer';

    /**
     * The synchronizer's buffer, a protected static with no accessor, keyed by destination type, then
     * fallback queue, then operation type, each holding a list of messages.
     *
     * @var string
     */
    protected const string SYNCHRONIZER_BUFFER_PROPERTY = 'messages';

    /**
     * @var string
     */
    protected const string DEFAULT_FILE_NAME = 'sync-flush-runs.jsonl';

    protected static bool $isRegistered = false;

    protected static ?ReflectionProperty $synchronizerBuffer = null;

    /**
     * @return void
     */
    public static function register(): void
    {
        if (static::$isRegistered || !function_exists('OpenTelemetry\Instrumentation\hook')) {
            return;
        }

        static::$isRegistered = true;
        $syncFlushCollector = SyncFlushCollector::getInstance(new JsonLinesTaskRunMetricsWriter(static::resolveOutputFilePath()));

        hook(
            class: TaskManager::class,
            function: 'run',
            pre: static function (object $taskManager, array $params) use ($syncFlushCollector): void {
                if (is_string($params[0] ?? null)) {
                    $syncFlushCollector->setQueueName($params[0]);
                }
            },
        );

        hook(
            class: DataImporter::class,
            function: 'import',
            pre: static function (object $dataImporter, array $params) use ($syncFlushCollector): void {
                $syncFlushCollector->markImporterRun();
            },
        );

        hook(
            class: static::SYNCHRONIZER_CLASS,
            function: 'flushSynchronizationMessages',
            pre: static function (object $synchronizer, array $params) use ($syncFlushCollector): void {
                $syncFlushCollector->setCommandName(static::resolveCommandName());
                $syncFlushCollector->openFlush(static::countBufferedMessages());
            },
            post: static function (object $synchronizer, array $params, mixed $response, ?Throwable $throwable) use ($syncFlushCollector): void {
                $syncFlushCollector->closeFlush($throwable);
            },
        );
    }

    /**
     * @return int|null Null when the buffer cannot be read, which the record reports as null.
     */
    protected static function countBufferedMessages(): ?int
    {
        try {
            if (static::$synchronizerBuffer === null) {
                static::$synchronizerBuffer = new ReflectionProperty(static::SYNCHRONIZER_CLASS, static::SYNCHRONIZER_BUFFER_PROPERTY);
            }

            $buffer = static::$synchronizerBuffer->getValue();
        } catch (Throwable $throwable) {
            error_log(sprintf('Could not read the direct synchronization buffer: %s', $throwable->getMessage()));

            return null;
        }

        return is_array($buffer) ? static::countLeafMessages($buffer, 3) : null;
    }

    /**
     * @param array<mixed> $buffer
     * @param int $depth Levels of nesting above the message lists.
     *
     * @return int
     */
    protected static function countLeafMessages(array $buffer, int $depth): int
    {
        if ($depth === 0) {
            return count($buffer);
        }

        $count = 0;

        foreach ($buffer as $nested) {
            $count += is_array($nested) ? static::countLeafMessages($nested, $depth - 1) : 0;
        }

        return $count;
    }

    /**
     * @return string|null
     */
    protected static function resolveCommandName(): ?string
    {
        $commandName = $_SERVER['argv'][1] ?? null;

        // The console entry point takes the command name as its first argument; an option there means
        // the name is not where it is expected, and guessing would mislabel the record.
        return is_string($commandName) && $commandName !== '' && !str_starts_with($commandName, '-') ? $commandName : null;
    }

    /**
     * @return string
     */
    protected static function resolveOutputFilePath(): string
    {
        $configured = getenv(PublishAndSynchronizeMetricsConstants::ENV_SYNC_FLUSH_OUTPUT_FILE);

        if ($configured !== false && $configured !== '') {
            return $configured;
        }

        $taskRunFile = getenv(PublishAndSynchronizeMetricsConstants::ENV_OUTPUT_FILE);
        $directory = $taskRunFile !== false && $taskRunFile !== '' ? dirname($taskRunFile) : sys_get_temp_dir();

        return $directory . DIRECTORY_SEPARATOR . static::DEFAULT_FILE_NAME;
    }
}
