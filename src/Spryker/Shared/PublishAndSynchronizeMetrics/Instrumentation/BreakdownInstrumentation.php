<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Shared\PublishAndSynchronizeMetrics\Instrumentation;

use Throwable;
use function OpenTelemetry\Instrumentation\hook;

/**
 * Times the calls that leave the process, per category, without modifying any of the classes involved.
 *
 * Each category is hooked at the narrowest point every caller passes through, so one logical call is
 * counted once:
 * - db: the PDO methods that run a statement or end a transaction. Propel's StatementWrapper and
 *   ConnectionWrapper delegate to exactly these, and hooking the wrappers as well would count twice.
 * - kv: the innermost Spryker Redis adapters. The compression, key prefix and logging adapters all
 *   decorate one of these two, and the storage client reaches the store only through them.
 * - search: Elastica's Client::request(). A bulk write, a delete and a read all end there.
 * - broker: the php-amqplib channel methods that write to the socket. batch_basic_publish() only
 *   buffers, so the batch is counted once, at publish_batch().
 *
 * Third-party classes are named by string rather than imported: a category whose library is not
 * installed is simply reported as null, and the module does not have to require any of them.
 */
class BreakdownInstrumentation
{
    /**
     * @var array<int, array<string, list<string>>>
     */
    protected const array HOOK_TARGETS = [
        BreakdownCollector::CATEGORY_DB => [
            'PDOStatement' => ['execute'],
            'PDO' => ['exec', 'query', 'beginTransaction', 'commit', 'rollBack'],
        ],
        BreakdownCollector::CATEGORY_KV => [
            'Spryker\Client\Redis\Adapter\VersionAgnosticPhpredisAdapter' => self::KV_METHODS,
            'Spryker\Client\Redis\Adapter\VersionAgnosticPredisAdapter' => self::KV_METHODS,
        ],
        BreakdownCollector::CATEGORY_SEARCH => [
            'Elastica\Client' => ['request'],
        ],
        BreakdownCollector::CATEGORY_BROKER => [
            'PhpAmqpLib\Channel\AMQPChannel' => ['basic_publish', 'publish_batch', 'basic_ack', 'basic_reject', 'basic_nack'],
        ],
    ];

    /**
     * Every method of RedisAdapterInterface that talks to the server. connect() is left out: it runs
     * once per process, and counting it would make the first span of a process look slower than the rest.
     *
     * @var list<string>
     */
    protected const array KV_METHODS = ['get', 'mget', 'set', 'setex', 'mset', 'del', 'eval', 'incr', 'expire', 'ttl', 'keys', 'scan', 'dbSize', 'info', 'flushDb'];

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
        $breakdownCollector = BreakdownCollector::getInstance();

        foreach (static::HOOK_TARGETS as $category => $methodsByClass) {
            if (static::hookCategory($breakdownCollector, $category, $methodsByClass)) {
                $breakdownCollector->markInstalled($category);
            }
        }

        $breakdownCollector->enable();
    }

    /**
     * @param \Spryker\Shared\PublishAndSynchronizeMetrics\Instrumentation\BreakdownCollector $breakdownCollector
     * @param int $category
     * @param array<string, list<string>> $methodsByClass
     *
     * @return bool Whether at least one target of the category was hooked.
     */
    protected static function hookCategory(BreakdownCollector $breakdownCollector, int $category, array $methodsByClass): bool
    {
        $isInstalled = false;

        foreach ($methodsByClass as $className => $methods) {
            if (!class_exists($className)) {
                continue;
            }

            foreach ($methods as $method) {
                $isInstalled = static::hookMethod($breakdownCollector, $category, $className, $method) || $isInstalled;
            }
        }

        return $isInstalled;
    }

    /**
     * The callbacks capture the collector and the category instead of looking either up, and they
     * return nothing: a pre-callback that returns an array replaces the call's arguments.
     *
     * @param \Spryker\Shared\PublishAndSynchronizeMetrics\Instrumentation\BreakdownCollector $breakdownCollector
     * @param int $category
     * @param string $className
     * @param string $method
     *
     * @return bool
     */
    protected static function hookMethod(BreakdownCollector $breakdownCollector, int $category, string $className, string $method): bool
    {
        return hook(
            class: $className,
            function: $method,
            pre: static function (mixed $scope, array $params) use ($breakdownCollector, $category): void {
                $breakdownCollector->beginCall($category);
            },
            post: static function (mixed $scope, array $params, mixed $response, ?Throwable $throwable) use ($breakdownCollector, $category): void {
                $breakdownCollector->endCall($category);
            },
        );
    }
}
