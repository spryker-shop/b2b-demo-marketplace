<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Shared\PublishAndSynchronizeMetrics\Instrumentation;

use Throwable;

/**
 * Writes the collected per-listener records once, when the queue task process ends.
 *
 * A queue task process handles one queue and then exits, so process end is the natural boundary:
 * writing per call would produce one line per listener group per batch and repeat the same
 * identifying fields thousands of times.
 */
class ListenerMetricsWriter
{
    /**
     * @var string
     */
    protected const string DEFAULT_FILE_NAME = 'listener-runs.jsonl';

    protected static bool $isRegistered = false;

    /**
     * @return void
     */
    public static function registerShutdownFlush(): void
    {
        if (static::$isRegistered) {
            return;
        }

        static::$isRegistered = true;

        register_shutdown_function(static function (): void {
            static::flush();
        });
    }

    /**
     * @return void
     */
    public static function flush(): void
    {
        try {
            $records = ListenerMetricsCollector::getInstance()->toArray();

            if (!$records) {
                return;
            }

            $path = static::resolvePath();
            $directory = dirname($path);

            if (!is_dir($directory) && !@mkdir($directory, 0775, true) && !is_dir($directory)) {
                return;
            }

            $queueName = static::resolveQueueName();
            $lines = '';

            foreach ($records as $record) {
                $lines .= json_encode(
                    ['ts' => round(microtime(true), 6), 'queue' => $queueName, 'pid' => getmypid()] + $record,
                    JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES,
                ) . PHP_EOL;
            }

            file_put_contents($path, $lines, FILE_APPEND | LOCK_EX);
        } catch (Throwable $throwable) {
            // Contained deliberately: this runs in a shutdown handler, where an uncaught throwable
            // would be reported as a fatal on a process that had otherwise finished its work.
            error_log(sprintf('Could not write listener metrics: %s', $throwable->getMessage()));
        }
    }

    /**
     * @return string
     */
    protected static function resolvePath(): string
    {
        $configured = getenv('QUEUE_METRICS_OUTPUT_FILE');

        if (!$configured) {
            return sys_get_temp_dir() . DIRECTORY_SEPARATOR . static::DEFAULT_FILE_NAME;
        }

        // Written beside the task-run records so one bundle holds both views of the same run.
        return dirname($configured) . DIRECTORY_SEPARATOR . static::DEFAULT_FILE_NAME;
    }

    /**
     * @return string
     */
    protected static function resolveQueueName(): string
    {
        $argv = $_SERVER['argv'] ?? [];

        // `queue:task:start <queue>` is the only caller that reaches the listener path.
        return isset($argv[2]) && is_string($argv[2]) ? $argv[2] : 'unknown';
    }
}
