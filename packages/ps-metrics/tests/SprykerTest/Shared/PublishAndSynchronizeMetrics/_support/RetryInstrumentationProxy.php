<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Shared\PublishAndSynchronizeMetrics;

use Generated\Shared\Transfer\QueueReceiveMessageTransfer;
use Spryker\Shared\PublishAndSynchronizeMetrics\Instrumentation\RetryInstrumentation;

/**
 * Exposes the attempt stamping without the OpenTelemetry hook, for the same reason as
 * MessagePublishInstrumentationProxy: the stamp is the whole behaviour the hook contributes.
 */
class RetryInstrumentationProxy extends RetryInstrumentation
{
    /**
     * @param \Generated\Shared\Transfer\QueueReceiveMessageTransfer $queueReceiveMessageTransfer
     *
     * @return void
     */
    public static function stampAttemptOf(QueueReceiveMessageTransfer $queueReceiveMessageTransfer): void
    {
        static::stampAttempt($queueReceiveMessageTransfer);
    }
}
