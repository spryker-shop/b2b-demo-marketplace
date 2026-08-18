<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Client\SymfonyScheduler;

use Spryker\Client\SymfonyScheduler\SymfonySchedulerConfig as SprykerSymfonySchedulerConfig;
use Spryker\Shared\MessageBroker\MessageBrokerConstants;

class SymfonySchedulerConfig extends SprykerSymfonySchedulerConfig
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public function getCronJobs(): array
    {
        $logger = $this->getLoggerCommand(); // script for jenkins logging

        $jobs = [
            'queue-worker-start' => [
                'command' => $logger . '$PHP_BIN vendor/bin/console queue:worker:start',
                'schedule' => '* * * * *',
                'priority' => 250,
            ],
            'check-product-validity' => [
                'command' => $logger . '$PHP_BIN vendor/bin/console product:check-validity',
                'schedule' => '0 6 * * *',
                'priority' => 240,
            ],
            'check-product-label-validity' => [
                'command' => $logger . '$PHP_BIN vendor/bin/console product-label:validity',
                'schedule' => '0 6 * * *',
                'priority' => 230,
            ],
            'update-product-label-relations' => [
                'command' => $logger . '$PHP_BIN vendor/bin/console product-label:relations:update -vvv --no-touch',
                'schedule' => '* * * * *',
                'priority' => 220,
            ],
            'check-workflow-conditions' => [
                'command' => $logger . '$PHP_BIN vendor/bin/console workflow:check-condition',
                'schedule' => '* * * * *',
                'priority' => 220,
            ],
            'check-workflow-timeouts' => [
                'command' => $logger . '$PHP_BIN vendor/bin/console workflow:check-timeout',
                'schedule' => '* * * * *',
                'priority' => 220,
            ],
            'check-oms-conditions' => [
                'command' => $logger . '$PHP_BIN vendor/bin/console oms:check-condition',
                'schedule' => '* * * * *',
                'priority' => 210,
            ],
            'check-oms-timeouts' => [
                'command' => $logger . '$PHP_BIN vendor/bin/console oms:check-timeout',
                'schedule' => '* * * * *',
                'priority' => 200,
            ],
            'clear-oms-locks' => [
                'command' => $logger . '$PHP_BIN vendor/bin/console oms:clear-locks',
                'schedule' => '0 6 * * *',
                'priority' => 190,
            ],
            'recurring-orders-check-condition' => [
                'command' => $logger . '$PHP_BIN vendor/bin/console state-machine:check-condition RecurringOrder',
                'schedule' => '* * * * *',
                'priority' => 180,
            ],
            'recurring-orders-clear-locks' => [
                'command' => $logger . '$PHP_BIN vendor/bin/console state-machine:clear-locks',
                'schedule' => '0 6 * * *',
                'priority' => 170,
            ],
            'product-relation-updater' => [
                'command' => $logger . '$PHP_BIN vendor/bin/console product-relation:update -vvv',
                'schedule' => '30 2 * * *',
                'priority' => 160,
            ],
            'event-trigger-timeout' => [
                'command' => $logger . '$PHP_BIN vendor/bin/console event:trigger:timeout',
                'schedule' => '*/5 * * * *',
                'priority' => 150,
            ],
            'deactivate-discontinued-products' => [
                'command' => $logger . '$PHP_BIN vendor/bin/console product:deactivate-discontinued-products',
                'schedule' => '0 0 * * *',
                'priority' => 140,
            ],
            'clean-expired-guest-cart' => [
                'command' => $logger . '$PHP_BIN vendor/bin/console cart:guest:clean-expired',
                'schedule' => '30 1 * * *',
                'priority' => 130,
            ],
            'close-outdated-quote-requests' => [
                'command' => $logger . '$PHP_BIN vendor/bin/console quote-request:close-outdated',
                'schedule' => '0 * * * *',
                'priority' => 120,
            ],
            'apply-price-product-schedule' => [
                'command' => $logger . '$PHP_BIN vendor/bin/console price-product:schedule:apply',
                'schedule' => '0 6 * * *',
                'priority' => 110,
            ],
            'remove-expired-refresh-tokens' => [
                'command' => $logger . '$PHP_BIN vendor/bin/console oauth:refresh-token:remove-expired',
                'schedule' => '*/5 * * * *',
                'priority' => 90,
            ],
            'delete-expired-customer-invalidated' => [
                'command' => $logger . '$PHP_BIN vendor/bin/console customer:delete-expired-invalidated',
                'schedule' => '0 0 * * 0',
                'priority' => 80,
            ],
            'order-invoice-send' => [
                'command' => $logger . '$PHP_BIN vendor/bin/console order:invoice:send',
                'schedule' => '*/5 * * * *',
                'priority' => 70,
            ],
            'glue-api-generate-documentation' => [
                'command' => $logger . '$PHP_BIN vendor/bin/glue api:generate:documentation --invalidated-after-interval 90sec',
                'schedule' => '* * * * *',
                'priority' => 50,
            ],
            'sync-order-matrix' => [
                'command' => $logger . '$PHP_BIN vendor/bin/console order-matrix:sync',
                'schedule' => '* * * * *',
                'priority' => 40,
            ],
            'generate-sitemap-files' => [
                'command' => $logger . '$PHP_BIN vendor/bin/console sitemap:generate',
                'schedule' => '0 0 * * *',
                'priority' => 30,
            ],
            'data-import-merchant-import' => [
                'command' => $logger . '$PHP_BIN vendor/bin/console data-import-merchant:import',
                'schedule' => '* * * * *',
                'priority' => 20,
            ],
            'import-job-run' => [
                'command' => $logger . '$PHP_BIN vendor/bin/console import:job:run',
                'schedule' => '* * * * *',
                'priority' => 10,
            ],
        ];

        /* Message broker */
        if ($this->get(MessageBrokerConstants::IS_ENABLED)) {
            $jobs['message-broker-consume-channels'] = [
                'command' => $logger . '$PHP_BIN vendor/bin/console message-broker:consume --time-limit=15 --sleep=5',
                'schedule' => '* * * * *',
                'priority' => 25,
            ];
        }

        return $jobs;
    }

    protected function getLoggerCommand(): string
    {
        return '';
    }
}
