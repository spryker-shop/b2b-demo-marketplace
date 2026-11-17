<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Shared\PublishAndSynchronizeMetrics\Instrumentation;

/**
 * Measures the CPU time of a scope for the records of contract C8: user and system time of this
 * process from `getrusage()`, as the difference between scope end and scope start.
 *
 * With the wall time of the same span this separates work done in this process (CPU) from waiting
 * on the database, the key-value store, the search engine or the broker (wall minus CPU).
 *
 * Same scope pattern as `MemoryTracker`: `beginScope()` at span start, `endScope()` at span end, and
 * a scope that could not be measured stays null all the way into the record, never 0.
 *
 * Precision: the kernel reports both times in microseconds, and their sum follows the scheduler's
 * runtime accounting closely. The split between user and system time of a short scope is sampled
 * per scheduler tick, so for spans of a few milliseconds read the sum, not the split.
 */
class CpuTracker
{
    /**
     * @var string
     */
    public const string FIELD_USER_MS = 'cpu_user_ms';

    /**
     * @var string
     */
    public const string FIELD_SYS_MS = 'cpu_sys_ms';

    /**
     * @var string
     */
    protected const string USAGE_USER_US = 'user_us';

    /**
     * @var string
     */
    protected const string USAGE_SYS_US = 'sys_us';

    protected static ?self $instance = null;

    /**
     * @return self
     */
    public static function getInstance(): self
    {
        if (static::$instance === null) {
            static::$instance = new self();
        }

        return static::$instance;
    }

    /**
     * Exists so a test can assert against a clean tracker; nothing in the runtime path calls it.
     *
     * @return void
     */
    public static function reset(): void
    {
        static::$instance = null;
    }

    /**
     * @param array<string, float>|null $cpu The value `endScope()` returned.
     *
     * @return array<string, float|null> Both record fields, null when the scope was not measured.
     */
    public static function toRecordFields(?array $cpu): array
    {
        return [
            static::FIELD_USER_MS => $cpu === null ? null : round($cpu[static::FIELD_USER_MS], 3),
            static::FIELD_SYS_MS => $cpu === null ? null : round($cpu[static::FIELD_SYS_MS], 3),
        ];
    }

    /**
     * Sums the CPU time of several scopes, such as the calls of one listener group. A missing scope
     * adds nothing, so the total stays null until one scope was measured.
     *
     * @param array<string, float>|null $total
     * @param array<string, float>|null $addition
     *
     * @return array<string, float>|null
     */
    public static function merge(?array $total, ?array $addition): ?array
    {
        if ($addition === null) {
            return $total;
        }

        if ($total === null) {
            return $addition;
        }

        return [
            static::FIELD_USER_MS => $total[static::FIELD_USER_MS] + $addition[static::FIELD_USER_MS],
            static::FIELD_SYS_MS => $total[static::FIELD_SYS_MS] + $addition[static::FIELD_SYS_MS],
        ];
    }

    /**
     * @return array<string, int>|null Usage at start, or null when it cannot be read. The value goes
     *   back into `endScope()`.
     */
    public function beginScope(): ?array
    {
        return $this->readUsage();
    }

    /**
     * @param array<string, int>|null $scope The value `beginScope()` returned.
     *
     * @return array<string, float>|null User and system milliseconds spent in the scope, keyed by
     *   `FIELD_USER_MS` and `FIELD_SYS_MS`, or null when the scope was not measured.
     */
    public function endScope(?array $scope): ?array
    {
        if ($scope === null) {
            return null;
        }

        $usage = $this->readUsage();

        if ($usage === null) {
            return null;
        }

        return [
            static::FIELD_USER_MS => max(0, $usage[static::USAGE_USER_US] - $scope[static::USAGE_USER_US]) / 1000,
            static::FIELD_SYS_MS => max(0, $usage[static::USAGE_SYS_US] - $scope[static::USAGE_SYS_US]) / 1000,
        ];
    }

    /**
     * @return array<string, int>|null User and system time of this process so far, in microseconds.
     */
    protected function readUsage(): ?array
    {
        if (!function_exists('getrusage')) {
            return null;
        }

        $usage = getrusage();

        if (!is_array($usage) || !isset($usage['ru_utime.tv_sec'], $usage['ru_utime.tv_usec'], $usage['ru_stime.tv_sec'], $usage['ru_stime.tv_usec'])) {
            return null;
        }

        return [
            static::USAGE_USER_US => $usage['ru_utime.tv_sec'] * 1000000 + $usage['ru_utime.tv_usec'],
            static::USAGE_SYS_US => $usage['ru_stime.tv_sec'] * 1000000 + $usage['ru_stime.tv_usec'],
        ];
    }
}
