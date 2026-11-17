<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Shared\PublishAndSynchronizeMetrics\Instrumentation;

use Generated\Shared\Transfer\QueueSendMessageTransfer;
use Spryker\Client\Queue\Model\Proxy\QueueProxy;
use Spryker\Shared\PublishAndSynchronizeMetrics\PublishAndSynchronizeMetricsConstants;
use function OpenTelemetry\Instrumentation\hook;

/**
 * Stamps outgoing messages with a publish timestamp, without modifying the queue client.
 *
 * This is the one hook that writes rather than observes: a pre-callback may return a replacement
 * argument list, and the method receives it. QueueProxy is the single choke point every producer
 * passes through, so stamping here reaches every queue with no producer change.
 *
 * Messages carry no usable publish time otherwise. Event messages do have a `timestamp` in their
 * payload, but only in whole seconds, which says nothing about batches that finish in tens of
 * milliseconds; sync messages carry none at all.
 */
class MessagePublishInstrumentation
{
    protected static bool $isRegistered = false;

    /**
     * @return void
     */
    public static function register(): void
    {
        if (static::$isRegistered || !function_exists('OpenTelemetry\Instrumentation\hook')) {
            return;
        }

        static::$isRegistered = true;

        hook(
            class: QueueProxy::class,
            function: 'sendMessage',
            pre: static function (object $queueProxy, array $params): array {
                if (isset($params[1]) && $params[1] instanceof QueueSendMessageTransfer) {
                    $params[1] = static::stamp($params[1]);
                }

                return $params;
            },
        );

        hook(
            class: QueueProxy::class,
            function: 'sendMessages',
            pre: static function (object $queueProxy, array $params): array {
                if (!is_array($params[1] ?? null)) {
                    return $params;
                }

                foreach ($params[1] as $key => $queueSendMessageTransfer) {
                    if ($queueSendMessageTransfer instanceof QueueSendMessageTransfer) {
                        $params[1][$key] = static::stamp($queueSendMessageTransfer);
                    }
                }

                return $params;
            },
        );
    }

    /**
     * @param \Generated\Shared\Transfer\QueueSendMessageTransfer $queueSendMessageTransfer
     *
     * @return \Generated\Shared\Transfer\QueueSendMessageTransfer
     */
    protected static function stamp(QueueSendMessageTransfer $queueSendMessageTransfer): QueueSendMessageTransfer
    {
        $headers = $queueSendMessageTransfer->getHeaders() ?: [];

        // A message re-sent through the queue client keeps the timestamp it already carries. The
        // consumer's retry republish does not pass through here and drops every header; that path
        // carries the first-send stamp in the body instead, see RetryInstrumentation.
        if (array_key_exists(PublishAndSynchronizeMetricsConstants::HEADER_ENQUEUED_AT, $headers)) {
            return $queueSendMessageTransfer;
        }

        // Formatted explicitly rather than cast: a (string) cast of a float honours the `precision`
        // ini setting, 14 by default, which silently rounds away the microseconds of a timestamp.
        $headers[PublishAndSynchronizeMetricsConstants::HEADER_ENQUEUED_AT] = sprintf('%.6F', microtime(true));

        return $queueSendMessageTransfer->setHeaders($headers);
    }
}
