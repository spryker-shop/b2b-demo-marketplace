<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Shared\PublishAndSynchronizeMetrics;

use Generated\Shared\Transfer\QueueSendMessageTransfer;
use Spryker\Shared\PublishAndSynchronizeMetrics\Instrumentation\MessagePublishInstrumentation;

/**
 * Exposes the stamping logic without the OpenTelemetry hook.
 *
 * The hook itself cannot be exercised in a unit test: it rewrites arguments inside the extension at
 * call time. Stamping is the whole behaviour the hook contributes, so it is reached directly here
 * rather than left untested.
 */
class MessagePublishInstrumentationProxy extends MessagePublishInstrumentation
{
    /**
     * @param \Generated\Shared\Transfer\QueueSendMessageTransfer $queueSendMessageTransfer
     *
     * @return \Generated\Shared\Transfer\QueueSendMessageTransfer
     */
    public static function stampMessage(QueueSendMessageTransfer $queueSendMessageTransfer): QueueSendMessageTransfer
    {
        return static::stamp($queueSendMessageTransfer);
    }
}
