<?php

declare(strict_types=1);

namespace Pyz\Shared\QueueTiming;

/**
 * Perf-testing queue timing helpers (channel: ps-test).
 *
 * Emits per-queue metrics so a load test can answer:
 *  - average time to PROCESS a message per queue
 *  - average time a message SITS in a queue (dwell/wait)
 *
 * Off by default. Enable per env with PS_QUEUE_TIMING=1.
 * Records are appended as JSON lines (one per processed batch) to the log file.
 */
class QueueTimingConstants
{
    /**
     * AMQP application header carrying the publish time in epoch milliseconds.
     *
     * @var string
     */
    public const HEADER_PUBLISHED_MS = 'x-ps-published-ms';

    /**
     * @var string
     */
    public const ENV_ENABLED = 'PS_QUEUE_TIMING';

    /**
     * @var string
     */
    public const ENV_LOG = 'PS_QUEUE_TIMING_LOG';

    /**
     * @return bool
     */
    public static function isEnabled(): bool
    {
        return getenv(static::ENV_ENABLED) === '1';
    }

    /**
     * Current time in epoch milliseconds.
     *
     * @return int
     */
    public static function nowMs(): int
    {
        return (int)round(microtime(true) * 1000);
    }

    /**
     * Append one JSON-line record to the ps-test log.
     *
     * @param array<string, mixed> $record
     *
     * @return void
     */
    public static function log(array $record): void
    {
        $line = json_encode($record, JSON_UNESCAPED_SLASHES) . "\n";
        $path = static::logPath();
        $dir = dirname($path);
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        @file_put_contents($path, $line, FILE_APPEND | LOCK_EX);
    }

    /**
     * @return string
     */
    protected static function logPath(): string
    {
        $explicit = getenv(static::ENV_LOG);
        if ($explicit !== false && $explicit !== '') {
            return $explicit;
        }

        $logDir = getenv('SPRYKER_LOG_DIRECTORY');
        if ($logDir !== false && $logDir !== '') {
            return rtrim($logDir, '/') . '/ps-test.log';
        }

        return APPLICATION_ROOT_DIR . '/data/logs/ps-test.log';
    }
}
