<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Shared\PublishAndSynchronizeMetrics;

/**
 * Declares global environment configuration keys. Do not use it for other class constants.
 */
interface PublishAndSynchronizeMetricsConstants
{
    /**
     * Specification:
     * - Name of the message header carrying the publish timestamp, a float unix timestamp with
     *   microsecond resolution.
     * - Written on the publishing side and read on the consuming side, both through hooks, so the
     *   two sides MUST reference this constant rather than repeating the literal.
     *
     * @var string
     */
    public const string HEADER_ENQUEUED_AT = 'x-spryker-enqueued-at';

    /**
     * Specification:
     * - Name of the message body key carrying the time the current attempt was enqueued, a float
     *   unix timestamp with microsecond resolution, formatted as a string.
     * - Written only when a message is sent back for retry, never on first send.
     * - Lives in the body rather than beside `HEADER_ENQUEUED_AT`: the retry republish copies the body
     *   alone and drops every message property, so a header would not survive the trip.
     *
     * @var string
     */
    public const string HEADER_ATTEMPT_ENQUEUED_AT = 'x-spryker-attempt-enqueued-at';

    /**
     * Specification:
     * - Enables the instrumentation. Nothing is hooked at all when this is unset.
     *
     * @var string
     */
    public const string ENV_ENABLED = 'QUEUE_METRICS_ENABLED';

    /**
     * Specification:
     * - Absolute path of the file task-run records are appended to.
     *
     * @var string
     */
    public const string ENV_OUTPUT_FILE = 'QUEUE_METRICS_OUTPUT_FILE';

    /**
     * Specification:
     * - Absolute path of the file import records are appended to.
     * - Defaults to `import-runs.jsonl` beside the task-run file, or in the system temp directory
     *   when no task-run file is configured either.
     *
     * @var string
     */
    public const string ENV_IMPORT_OUTPUT_FILE = 'QUEUE_METRICS_IMPORT_OUTPUT_FILE';

    /**
     * Specification:
     * - Enables the per-importer data set duration histogram (`rows` records).
     * - Off by default: it adds one hook call per data set, where everything else is per importer.
     *
     * @var string
     */
    public const string ENV_IMPORT_ROWS = 'QUEUE_METRICS_IMPORT_ROWS';

    /**
     * Specification:
     * - Enables the time breakdown: database, key-value store, search and broker time per task run,
     *   listener and direct-sync flush record.
     * - Adds one hook call per query, key-value call, search request and broker operation.
     *
     * @var string
     */
    public const string ENV_BREAKDOWN = 'QUEUE_METRICS_BREAKDOWN';

    /**
     * Specification:
     * - Absolute path of the file direct-sync flush records are appended to.
     * - Defaults to `sync-flush-runs.jsonl` beside the task-run file, or in the system temp directory.
     *
     * @var string
     */
    public const string ENV_SYNC_FLUSH_OUTPUT_FILE = 'QUEUE_METRICS_SYNC_FLUSH_OUTPUT_FILE';

    /**
     * Specification:
     * - Absolute path of the settings file read when `QUEUE_METRICS_ENABLED` is not set in the process
     *   environment, for processes started by the scheduler.
     * - Defaults to `queue-metrics.settings.json` in the system temp directory.
     * - The file is used only while its `expires_at` is in the future.
     *
     * @var string
     */
    public const string ENV_SETTINGS_FILE = 'QUEUE_METRICS_SETTINGS_FILE';
}
