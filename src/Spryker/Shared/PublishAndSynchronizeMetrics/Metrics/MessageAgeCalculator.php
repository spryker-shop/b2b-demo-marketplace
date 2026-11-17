<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Shared\PublishAndSynchronizeMetrics\Metrics;

use Generated\Shared\Transfer\QueueReceiveMessageTransfer;
use Generator;
use Spryker\Shared\PublishAndSynchronizeMetrics\PublishAndSynchronizeMetricsConstants;

class MessageAgeCalculator implements MessageAgeCalculatorInterface
{
    /**
     * @var int
     */
    protected const int MILLISECONDS_PER_SECOND = 1000;

    /**
     * {@inheritDoc}
     *
     * @param array<\Generated\Shared\Transfer\QueueReceiveMessageTransfer> $queueReceiveMessageTransfers
     *
     * @return array<string, float|int>
     */
    public function calculate(array $queueReceiveMessageTransfers): array
    {
        $ages = iterator_to_array($this->yieldAges($queueReceiveMessageTransfers), false);

        if (!$ages) {
            return [];
        }

        sort($ages);

        return [
            'n' => count($ages),
            'min' => round($ages[0], 3),
            'avg' => round(array_sum($ages) / count($ages), 3),
            'p50' => round($this->percentile($ages, 0.50), 3),
            'p95' => round($this->percentile($ages, 0.95), 3),
            'max' => round($ages[count($ages) - 1], 3),
        ];
    }

    /**
     * {@inheritDoc}
     *
     * @param array<\Generated\Shared\Transfer\QueueReceiveMessageTransfer> $queueReceiveMessageTransfers
     *
     * @return array<string, mixed>
     */
    public function calculateHistogram(array $queueReceiveMessageTransfers): array
    {
        $histogram = new LatencyHistogram();

        foreach ($this->yieldAges($queueReceiveMessageTransfers) as $age) {
            $histogram->record($age);
        }

        return $histogram->toArray();
    }

    /**
     * {@inheritDoc}
     *
     * @param array<\Generated\Shared\Transfer\QueueReceiveMessageTransfer> $queueReceiveMessageTransfers
     *
     * @return array<string, mixed>
     */
    public function calculateAttemptHistogram(array $queueReceiveMessageTransfers): array
    {
        $histogram = new LatencyHistogram();
        $now = microtime(true);

        foreach ($queueReceiveMessageTransfers as $queueReceiveMessageTransfer) {
            $attemptEnqueuedAt = $this->extractAttemptEnqueuedAt($queueReceiveMessageTransfer);

            if ($attemptEnqueuedAt === null) {
                continue;
            }

            $histogram->record(($now - $attemptEnqueuedAt) * static::MILLISECONDS_PER_SECOND);
        }

        return $histogram->toArray();
    }

    /**
     * A generator, so a batch's ages stream into the histogram one at a time instead of being held
     * as an array the size of the batch.
     *
     * @param array<\Generated\Shared\Transfer\QueueReceiveMessageTransfer> $queueReceiveMessageTransfers
     *
     * @return \Generator<int, float>
     */
    protected function yieldAges(array $queueReceiveMessageTransfers): Generator
    {
        $now = microtime(true);

        foreach ($queueReceiveMessageTransfers as $queueReceiveMessageTransfer) {
            $enqueuedAt = $this->extractEnqueuedAt($queueReceiveMessageTransfer);

            if ($enqueuedAt === null) {
                continue;
            }

            // A negative age means the publishing and consuming clocks disagree. Clamping it to zero
            // would hide that, so the raw value is kept and the reader can spot the skew.
            yield ($now - $enqueuedAt) * static::MILLISECONDS_PER_SECOND;
        }
    }

    /**
     * The header is authoritative when present: it is only absent on a message delivered from a
     * retry queue, whose republish dropped it, and that is the one case the body key exists for.
     *
     * @param \Generated\Shared\Transfer\QueueReceiveMessageTransfer $queueReceiveMessageTransfer
     *
     * @return float|null
     */
    protected function extractEnqueuedAt(QueueReceiveMessageTransfer $queueReceiveMessageTransfer): ?float
    {
        $headers = $queueReceiveMessageTransfer->getQueueMessage()?->getHeaders() ?: [];

        if (isset($headers[PublishAndSynchronizeMetricsConstants::HEADER_ENQUEUED_AT])) {
            $enqueuedAt = $headers[PublishAndSynchronizeMetricsConstants::HEADER_ENQUEUED_AT];

            return is_numeric($enqueuedAt) ? (float)$enqueuedAt : null;
        }

        return $this->extractBodyTimestamp($queueReceiveMessageTransfer, PublishAndSynchronizeMetricsConstants::HEADER_ENQUEUED_AT);
    }

    /**
     * @param \Generated\Shared\Transfer\QueueReceiveMessageTransfer $queueReceiveMessageTransfer
     *
     * @return float|null
     */
    protected function extractAttemptEnqueuedAt(QueueReceiveMessageTransfer $queueReceiveMessageTransfer): ?float
    {
        return $this->extractBodyTimestamp($queueReceiveMessageTransfer, PublishAndSynchronizeMetricsConstants::HEADER_ATTEMPT_ENQUEUED_AT);
    }

    /**
     * @param \Generated\Shared\Transfer\QueueReceiveMessageTransfer $queueReceiveMessageTransfer
     * @param string $bodyKey
     *
     * @return float|null
     */
    protected function extractBodyTimestamp(QueueReceiveMessageTransfer $queueReceiveMessageTransfer, string $bodyKey): ?float
    {
        $body = (string)$queueReceiveMessageTransfer->getQueueMessage()?->getBody();

        // A substring test first: almost every message is a first attempt, and decoding every body
        // of every batch only to find the key absent would cost more than the rest of the hook.
        // The quotes keep one key from matching inside the other.
        if (!str_contains($body, sprintf('"%s"', $bodyKey))) {
            return null;
        }

        $decodedBody = json_decode($body, true);
        $timestamp = is_array($decodedBody) ? ($decodedBody[$bodyKey] ?? null) : null;

        if (!is_numeric($timestamp)) {
            return null;
        }

        return (float)$timestamp;
    }

    /**
     * Nearest-rank percentile over an already sorted, zero-indexed list.
     *
     * @param array<float> $sortedAges
     * @param float $percentile
     *
     * @return float
     */
    protected function percentile(array $sortedAges, float $percentile): float
    {
        $count = count($sortedAges);

        if ($count === 1) {
            return $sortedAges[0];
        }

        $index = (int)ceil($percentile * $count) - 1;

        return $sortedAges[max(0, min($index, $count - 1))];
    }
}
