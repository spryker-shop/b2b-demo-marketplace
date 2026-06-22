<?php

declare(strict_types=1);

namespace Pyz\Zed\Queue\Business\Timing;

use Pyz\Shared\QueueTiming\QueueTimingConstants;
use Spryker\Zed\Queue\Dependency\Plugin\QueueMessageProcessorPluginInterface;
use Symfony\Component\DependencyInjection\Attribute\Exclude;

/**
 * Perf-testing decorator around a real queue message processor.
 *
 * Wraps processMessages() to record, per processed batch and per queue:
 *  - processing time (total + per message)
 *  - dwell time (now - publish timestamp header), if the publisher stamped it
 *    (see {@link \Pyz\Client\Queue\QueueClient}).
 *
 * Transparent passthrough unless PS_QUEUE_TIMING=1.
 *
 * Note: lives under Business\Timing (NOT Communication\Plugin) on purpose — it is
 * instantiated manually in QueueDependencyProvider, and the symfony service
 * container autowires project module classes, which would fail on the
 * non-autowirable string $queueName constructor argument. #[Exclude] keeps it out
 * of the DIC — it is only ever instantiated manually in QueueDependencyProvider.
 */
#[Exclude]
class TimingQueueMessageProcessorPlugin implements QueueMessageProcessorPluginInterface
{
    /**
     * @var \Spryker\Zed\Queue\Dependency\Plugin\QueueMessageProcessorPluginInterface
     */
    protected $innerPlugin;

    /**
     * @var string
     */
    protected $queueName;

    /**
     * @param \Spryker\Zed\Queue\Dependency\Plugin\QueueMessageProcessorPluginInterface $innerPlugin
     * @param string $queueName
     */
    public function __construct(QueueMessageProcessorPluginInterface $innerPlugin, string $queueName)
    {
        $this->innerPlugin = $innerPlugin;
        $this->queueName = $queueName;
    }

    /**
     * @param array<\Generated\Shared\Transfer\QueueReceiveMessageTransfer> $queueMessageTransfers
     *
     * @return array<\Generated\Shared\Transfer\QueueReceiveMessageTransfer>
     */
    public function processMessages(array $queueMessageTransfers)
    {
        if (!QueueTimingConstants::isEnabled()) {
            return $this->innerPlugin->processMessages($queueMessageTransfers);
        }

        $nowMs = QueueTimingConstants::nowMs();
        $dwells = $this->collectDwells($queueMessageTransfers, $nowMs);

        $start = hrtime(true);
        $result = $this->innerPlugin->processMessages($queueMessageTransfers);
        $totalMs = (hrtime(true) - $start) / 1_000_000;

        $count = count($queueMessageTransfers);
        QueueTimingConstants::log([
            'ts' => $nowMs,
            'queue' => $this->queueName,
            'batch' => $count,
            'process_total_ms' => round($totalMs, 2),
            'process_per_msg_ms' => $count > 0 ? round($totalMs / $count, 2) : null,
            'dwell_count' => count($dwells),
            'dwell_avg_ms' => $dwells ? (int)round(array_sum($dwells) / count($dwells)) : null,
            'dwell_min_ms' => $dwells ? min($dwells) : null,
            'dwell_max_ms' => $dwells ? max($dwells) : null,
        ]);

        return $result;
    }

    /**
     * @return int
     */
    public function getChunkSize()
    {
        return $this->innerPlugin->getChunkSize();
    }

    /**
     * Per-message dwell (ms) = now - publish timestamp header, for messages that carry it.
     *
     * @param array<\Generated\Shared\Transfer\QueueReceiveMessageTransfer> $queueMessageTransfers
     * @param int $nowMs
     *
     * @return array<int>
     */
    protected function collectDwells(array $queueMessageTransfers, int $nowMs): array
    {
        $dwells = [];
        foreach ($queueMessageTransfers as $queueMessageTransfer) {
            $sendMessageTransfer = $queueMessageTransfer->getQueueMessage();
            if ($sendMessageTransfer === null) {
                continue;
            }
            $headers = $sendMessageTransfer->getHeaders() ?? [];
            if (!isset($headers[QueueTimingConstants::HEADER_PUBLISHED_MS])) {
                continue;
            }
            $dwells[] = $nowMs - (int)$headers[QueueTimingConstants::HEADER_PUBLISHED_MS];
        }

        return $dwells;
    }
}
