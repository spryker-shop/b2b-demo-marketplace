<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Shared\PublishAndSynchronizeMetrics\Metrics;

interface MessageAgeCalculatorInterface
{
    /**
     * Specification:
     * - Calculates the distribution of message ages, in milliseconds, for the given batch.
     * - The age of a message is the time between it being published and now, so it is the
     *   enqueue-to-consumption latency of that message.
     * - The publish time is read from the message header, or, when the header is absent, from the
     *   same-named body key that a retry republish carries it in, so retried messages stay end to end.
     * - Messages without a publish timestamp are excluded rather than counted as zero, and the
     *   returned `n` states how many messages the distribution is actually based on.
     * - Returns an empty array when no message in the batch carries a timestamp.
     *
     * @param array<\Generated\Shared\Transfer\QueueReceiveMessageTransfer> $queueReceiveMessageTransfers
     *
     * @return array<string, float|int>
     */
    public function calculate(array $queueReceiveMessageTransfers): array;

    /**
     * Specification:
     * - Returns the same ages as a mergeable histogram rather than as percentiles.
     * - Percentiles cannot be combined, so the per-batch values returned by `calculate()` can never
     *   be aggregated into a run-wide figure; bucket counts can simply be summed.
     * - Returns an empty array when no message in the batch carries a timestamp.
     *
     * @param array<\Generated\Shared\Transfer\QueueReceiveMessageTransfer> $queueReceiveMessageTransfers
     *
     * @return array<string, mixed>
     */
    public function calculateHistogram(array $queueReceiveMessageTransfers): array;

    /**
     * Specification:
     * - Returns the histogram of ages, in milliseconds, since the current attempt of each message
     *   was enqueued, in the same shape as `calculateHistogram()`.
     * - The attempt time is read from the message body, where it is written only when a message is
     *   sent back for retry, so a first-attempt message is excluded rather than counted.
     * - Returns an empty array when no message in the batch carries an attempt timestamp.
     *
     * @param array<\Generated\Shared\Transfer\QueueReceiveMessageTransfer> $queueReceiveMessageTransfers
     *
     * @return array<string, mixed>
     */
    public function calculateAttemptHistogram(array $queueReceiveMessageTransfers): array;
}
