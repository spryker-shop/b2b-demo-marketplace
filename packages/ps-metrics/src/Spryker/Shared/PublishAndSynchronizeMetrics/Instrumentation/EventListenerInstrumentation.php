<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Shared\PublishAndSynchronizeMetrics\Instrumentation;

use Spryker\Zed\Event\Business\Queue\Consumer\EventQueueConsumer;
use Throwable;
use function OpenTelemetry\Instrumentation\hook;

/**
 * Times each listener group handled by the event queue consumer, without modifying it.
 *
 * `EventQueueConsumer::processMessages()` already groups the batch by listener class and then by
 * event name before dispatching, so that call boundary is where per-listener attribution is exact
 * and free: the consumer has already decoded every message body to build the grouping.
 */
class EventListenerInstrumentation
{
    /**
     * @var string
     */
    protected const string HOOKED_METHOD = 'handleBulk';

    /**
     * Called only after a listener's bulk call for one event threw, before the per-item fallback.
     *
     * @var string
     */
    protected const string HOOKED_METHOD_BULK_FAILURE = 'handleBulkItemsIndividually';

    /**
     * Called once per message a listener failed on, from both the single-handler path in
     * `processMessages()` and the per-item fallback, and the only failure-path method that receives
     * both the event name and the listener class.
     *
     * @var string
     */
    protected const string HOOKED_METHOD_MESSAGE_FAILURE = 'createErrorMessage';

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

        /** @var array<int, float|int> $startedAt */
        $startedAt = [];

        /** @var array<int, int|null> $breakdownTokens */
        $breakdownTokens = [];

        /** @var array<int, array<string, int>|null> $memoryScopes */
        $memoryScopes = [];

        /** @var array<int, array<string, int>|null> $cpuScopes */
        $cpuScopes = [];

        hook(
            class: EventQueueConsumer::class,
            function: static::HOOKED_METHOD,
            pre: static function (object $consumer, array $params) use (&$startedAt, &$breakdownTokens, &$memoryScopes, &$cpuScopes): void {
                $memoryScopes[] = MemoryTracker::getInstance()->beginScope();
                $cpuScopes[] = CpuTracker::getInstance()->beginScope();
                $startedAt[] = hrtime(true);
                $breakdownTokens[] = BreakdownCollector::getInstance()->openSpan();
            },
            post: static function (object $consumer, array $params, mixed $response, ?Throwable $throwable) use (&$startedAt, &$breakdownTokens, &$memoryScopes, &$cpuScopes): void {
                $breakdown = BreakdownCollector::getInstance()->closeSpan(array_pop($breakdownTokens));
                $start = array_pop($startedAt);
                $cpu = CpuTracker::getInstance()->endScope(array_pop($cpuScopes));
                $memoryIncrement = MemoryTracker::getInstance()->endScope(array_pop($memoryScopes));

                if ($start === null) {
                    return;
                }

                $milliseconds = (hrtime(true) - $start) / 1e6;
                $listenerClassName = is_string($params[1] ?? null) ? $params[1] : 'unknown';

                ListenerMetricsCollector::getInstance()->record(
                    $listenerClassName,
                    static::countItemsByEventName($params[0] ?? []),
                    $milliseconds,
                    $breakdown,
                    $memoryIncrement[ListenerMetricsCollector::MEMORY_KIND] ?? null,
                    $cpu,
                );
            },
        );

        static::hookFailures();
    }

    /**
     * A listener reports failure only by throwing: `EventBulkHandlerInterface::handleBulk()` returns
     * nothing, and the consumer catches every throwable inside its own `handleBulk()`, so the post
     * hook above never sees one. The failures are observed on the consumer's catch paths instead.
     *
     * @return void
     */
    protected static function hookFailures(): void
    {
        hook(
            class: EventQueueConsumer::class,
            function: static::HOOKED_METHOD_BULK_FAILURE,
            pre: static function (object $consumer, array $params): void {
                if (is_string($params[1] ?? null) && is_string($params[3] ?? null)) {
                    ListenerMetricsCollector::getInstance()->recordBulkCallFailure($params[3], $params[1]);
                }
            },
        );

        hook(
            class: EventQueueConsumer::class,
            function: static::HOOKED_METHOD_MESSAGE_FAILURE,
            pre: static function (object $consumer, array $params): void {
                if (is_string($params[0] ?? null) && is_string($params[1] ?? null)) {
                    ListenerMetricsCollector::getInstance()->recordMessageFailure($params[1], $params[0]);
                }
            },
        );
    }

    /**
     * @param mixed $eventItems
     *
     * @return array<string, int>
     */
    protected static function countItemsByEventName(mixed $eventItems): array
    {
        if (!is_array($eventItems)) {
            return [];
        }

        $counts = [];

        foreach ($eventItems as $eventName => $eventItem) {
            $transfers = is_array($eventItem) ? ($eventItem[EventQueueConsumer::EVENT_TRANSFERS] ?? []) : [];
            $counts[(string)$eventName] = is_array($transfers) ? count($transfers) : 0;
        }

        return $counts;
    }
}
