<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

use Spryker\Shared\PublishAndSynchronizeMetrics\Instrumentation\BreakdownInstrumentation;
use Spryker\Shared\PublishAndSynchronizeMetrics\Instrumentation\EventListenerInstrumentation;
use Spryker\Shared\PublishAndSynchronizeMetrics\Instrumentation\ImportInstrumentation;
use Spryker\Shared\PublishAndSynchronizeMetrics\Instrumentation\ListenerMetricsWriter;
use Spryker\Shared\PublishAndSynchronizeMetrics\Instrumentation\MessagePublishInstrumentation;
use Spryker\Shared\PublishAndSynchronizeMetrics\Instrumentation\QueueTaskInstrumentation;
use Spryker\Shared\PublishAndSynchronizeMetrics\Instrumentation\RetryInstrumentation;
use Spryker\Shared\PublishAndSynchronizeMetrics\Instrumentation\SettingsFile;
use Spryker\Shared\PublishAndSynchronizeMetrics\Instrumentation\SyncFlushInstrumentation;
use Spryker\Shared\PublishAndSynchronizeMetrics\PublishAndSynchronizeMetricsConstants;

// Loaded through composer `autoload.files`, which is what makes this instrumentation installable
// with `composer require` and no application code change. Off unless QUEUE_METRICS_ENABLED is set,
// so a project that does not want it pays nothing: no hook is registered at all.
// Processes the scheduler starts cannot get the variable from the caller; a valid settings file
// supplies it (see SettingsFile).
if (!getenv(PublishAndSynchronizeMetricsConstants::ENV_ENABLED)) {
    SettingsFile::apply(SettingsFile::getPath(), microtime(true));
}
if (!getenv(PublishAndSynchronizeMetricsConstants::ENV_ENABLED)) {
    return;
}

MessagePublishInstrumentation::register();
QueueTaskInstrumentation::register();
EventListenerInstrumentation::register();
RetryInstrumentation::register();
ImportInstrumentation::register();
SyncFlushInstrumentation::register();

// Per-call hooks on every query and storage call, so they are a separate switch.
if (getenv(PublishAndSynchronizeMetricsConstants::ENV_BREAKDOWN)) {
    BreakdownInstrumentation::register();
}

ListenerMetricsWriter::registerShutdownFlush();
