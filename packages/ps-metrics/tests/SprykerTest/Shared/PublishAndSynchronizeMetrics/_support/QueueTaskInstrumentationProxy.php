<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Shared\PublishAndSynchronizeMetrics;

use Spryker\Shared\PublishAndSynchronizeMetrics\Instrumentation\QueueTaskInstrumentation;
use Throwable;

/**
 * Exposes the hook callbacks' bodies, so the sequence the extension runs them in (run pre, processor
 * pre, processor post, run post) can be replayed without registering a hook.
 */
class QueueTaskInstrumentationProxy extends QueueTaskInstrumentation
{
    /**
     * @param mixed $queueName
     *
     * @return void
     */
    public static function startTaskRun(mixed $queueName): void
    {
        static::onTaskRunStart($queueName);
    }

    /**
     * @param \Throwable|null $throwable
     *
     * @return void
     */
    public static function endTaskRun(?Throwable $throwable): void
    {
        static::onTaskRunEnd($throwable);
    }

    /**
     * @param mixed $queueReceiveMessageTransfers
     *
     * @return void
     */
    public static function startProcessing(mixed $queueReceiveMessageTransfers): void
    {
        static::onProcessingStart($queueReceiveMessageTransfers);
    }

    /**
     * @param mixed $processedMessages
     * @param \Throwable|null $throwable
     *
     * @return void
     */
    public static function endProcessing(mixed $processedMessages, ?Throwable $throwable): void
    {
        static::onProcessingEnd($processedMessages, $throwable);
    }
}
