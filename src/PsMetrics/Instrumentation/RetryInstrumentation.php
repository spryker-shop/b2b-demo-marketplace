<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Shared\PublishAndSynchronizeMetrics\Instrumentation;

use Generated\Shared\Transfer\QueueReceiveMessageTransfer;
use Spryker\Shared\PublishAndSynchronizeMetrics\PublishAndSynchronizeMetricsConstants;
use Spryker\Zed\Event\Business\Queue\Consumer\EventQueueConsumer;
use function OpenTelemetry\Instrumentation\hook;

/**
 * Stamps a message with the time its new attempt is enqueued, at the moment it is sent back for
 * retry, without modifying the event queue consumer.
 *
 * The retry republish copies the message body into a fresh broker message and drops every message
 * property, so the publish-time header does not survive it. The only carrier that does is the body,
 * which is also where the consumer itself keeps its retry counter. Two stamps travel there: the time
 * of the current attempt, and the original first-send time copied over from the header, so the
 * end-to-end age still covers the retry queues.
 *
 * `EventQueueConsumer::markMessageAsRetry()` is the one place a message is decided for retry; nothing
 * on the first-send path reaches it. Its pre-hook mutates the message transfer in place: the transfer
 * is an object, so the method sees the stamped body, and its own body rewrite (adding the retry
 * reason) decodes and re-encodes that body, keeping the stamp.
 */
class RetryInstrumentation
{
    /**
     * @var string
     */
    protected const string HOOKED_METHOD = 'markMessageAsRetry';

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
            class: EventQueueConsumer::class,
            function: static::HOOKED_METHOD,
            pre: static function (object $consumer, array $params): void {
                if (isset($params[0]) && $params[0] instanceof QueueReceiveMessageTransfer) {
                    static::stampAttempt($params[0]);
                }
            },
        );
    }

    /**
     * Overwrites any earlier attempt stamp: the age of interest is that of the current attempt, while
     * the end-to-end age is what the publish-time header is for.
     *
     * @param \Generated\Shared\Transfer\QueueReceiveMessageTransfer $queueReceiveMessageTransfer
     *
     * @return void
     */
    protected static function stampAttempt(QueueReceiveMessageTransfer $queueReceiveMessageTransfer): void
    {
        $queueSendMessageTransfer = $queueReceiveMessageTransfer->getQueueMessage();

        if ($queueSendMessageTransfer === null) {
            return;
        }

        $body = json_decode((string)$queueSendMessageTransfer->getBody(), true);

        // A body that is not a JSON object cannot carry the stamp; leaving it untouched is the only
        // safe choice for instrumentation that must never change what is delivered.
        if (!is_array($body)) {
            return;
        }

        $body = static::carryFirstSendStamp($body, $queueSendMessageTransfer->getHeaders() ?: []);

        // Formatted explicitly rather than cast, for the same reason as the publish-time stamp: a
        // float cast honours the `precision` ini setting and can round away the microseconds.
        $body[PublishAndSynchronizeMetricsConstants::HEADER_ATTEMPT_ENQUEUED_AT] = sprintf('%.6F', microtime(true));

        $encodedBody = json_encode($body);

        if ($encodedBody === false) {
            error_log(sprintf('Could not stamp the retry attempt time: %s', json_last_error_msg()));

            return;
        }

        $queueSendMessageTransfer->setBody($encodedBody);
    }

    /**
     * Copies the first-send stamp into the body once, on the first retry, while the received message
     * still carries it as a header. On later retries the message arrives from the retry queue with
     * no header but with the body key, which is then kept unchanged.
     *
     * @param array<mixed> $body
     * @param array<mixed> $headers
     *
     * @return array<mixed>
     */
    protected static function carryFirstSendStamp(array $body, array $headers): array
    {
        if (isset($body[PublishAndSynchronizeMetricsConstants::HEADER_ENQUEUED_AT])) {
            return $body;
        }

        $enqueuedAt = $headers[PublishAndSynchronizeMetricsConstants::HEADER_ENQUEUED_AT] ?? null;

        if (!is_numeric($enqueuedAt)) {
            return $body;
        }

        $body[PublishAndSynchronizeMetricsConstants::HEADER_ENQUEUED_AT] = (string)$enqueuedAt;

        return $body;
    }
}
