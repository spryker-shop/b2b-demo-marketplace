<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Shared\PublishAndSynchronizeMetrics\Metrics;

interface TaskRunMetricsWriterInterface
{
    /**
     * Specification:
     * - Writes a single task-run record.
     * - Never interrupts queue processing: a failure to write is reported through the logger and
     *   contained at this boundary only, because losing a metrics line must not cost a message.
     *
     * @param array<string, mixed> $record
     *
     * @return void
     */
    public function write(array $record): void;
}
