<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Shared\PublishAndSynchronizeMetrics\Instrumentation;

use Spryker\Shared\PublishAndSynchronizeMetrics\PublishAndSynchronizeMetricsConstants;

/**
 * Enables the instrumentation for processes whose environment cannot be set by the caller.
 *
 * The scheduler starts queue workers through its own job runner, so a measurement run cannot pass
 * `QUEUE_METRICS_*` variables to them. The run writes this file instead; every process that starts
 * while it is valid copies its `QUEUE_METRICS_*` entries into its own environment, before
 * `_register.php` reads them.
 *
 * File format: `{"expires_at": <unix timestamp>, "env": {"QUEUE_METRICS_ENABLED": "1", ...}}`.
 * A file without a future `expires_at` is ignored, so a file a run left behind cannot switch the
 * instrumentation on for good. Keys outside the `QUEUE_METRICS_` prefix are ignored. A variable that
 * is already set in the process environment wins over the file.
 */
class SettingsFile
{
    /**
     * @var string
     */
    protected const string KEY_PREFIX = 'QUEUE_METRICS_';

    /**
     * @var string
     */
    protected const string DEFAULT_FILE_NAME = 'queue-metrics.settings.json';

    /**
     * @return string
     */
    public static function getPath(): string
    {
        $path = getenv(PublishAndSynchronizeMetricsConstants::ENV_SETTINGS_FILE);

        return $path ?: sys_get_temp_dir() . DIRECTORY_SEPARATOR . static::DEFAULT_FILE_NAME;
    }

    /**
     * @param string $path
     * @param float $now
     *
     * @return int Number of variables copied into the environment.
     */
    public static function apply(string $path, float $now): int
    {
        if (!is_file($path) || !is_readable($path)) {
            return 0;
        }

        $settings = json_decode((string)file_get_contents($path), true);
        if (!is_array($settings) || !is_numeric($settings['expires_at'] ?? null) || (float)$settings['expires_at'] <= $now) {
            return 0;
        }

        $applied = 0;
        foreach ((array)($settings['env'] ?? []) as $name => $value) {
            if (!is_string($name) || !str_starts_with($name, static::KEY_PREFIX) || !is_scalar($value)) {
                continue;
            }
            if (getenv($name) !== false) {
                continue;
            }
            putenv($name . '=' . $value);
            $applied++;
        }

        return $applied;
    }
}
