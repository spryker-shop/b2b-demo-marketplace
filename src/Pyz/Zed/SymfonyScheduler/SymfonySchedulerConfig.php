<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\SymfonyScheduler;

use Spryker\Shared\MessageBroker\MessageBrokerConstants;
use Spryker\Zed\SymfonyScheduler\SymfonySchedulerConfig as SprykerSymfonySchedulerConfigAlias;

class SymfonySchedulerConfig extends SprykerSymfonySchedulerConfigAlias
{
    public function getCronJobs(): array
    {
        $logger = $this->getLoggerCommand(); // script for jenkins logging

        $jobs = [
            'queue-worker-start' => [
                'command' => $logger . '$PHP_BIN vendor/bin/console queue:worker:start',
                'schedule' => '* * * * *',
            ],
            'check-product-validity' => [
                'command' => $logger . '$PHP_BIN vendor/bin/console product:check-validity',
                'schedule' => '0 6 * * *',
            ],
            'check-product-label-validity' => [
                'command' => $logger . '$PHP_BIN vendor/bin/console product-label:validity',
                'schedule' => '0 6 * * *',
            ],
            'update-product-label-relations' => [
                'command' => $logger . '$PHP_BIN vendor/bin/console product-label:relations:update -vvv --no-touch',
                'schedule' => '* * * * *',
            ],
            'check-oms-conditions' => [
                'command' => $logger . '$PHP_BIN vendor/bin/console oms:check-condition',
                'schedule' => '* * * * *',
            ],
            'check-oms-timeouts' => [
                'command' => $logger . '$PHP_BIN vendor/bin/console oms:check-timeout',
                'schedule' => '* * * * *',
            ],
            'clear-oms-locks' => [
                'command' => $logger . '$PHP_BIN vendor/bin/console oms:clear-locks',
                'schedule' => '0 6 * * *',
            ],
            'product-relation-updater' => [
                'command' => $logger . '$PHP_BIN vendor/bin/console product-relation:update -vvv',
                'schedule' => '30 2 * * *',
            ],
            'event-trigger-timeout' => [
                'command' => $logger . '$PHP_BIN vendor/bin/console event:trigger:timeout',
                'schedule' => '*/5 * * * *',
            ],
            'deactivate-discontinued-products' => [
                'command' => $logger . '$PHP_BIN vendor/bin/console product:deactivate-discontinued-products',
                'schedule' => '0 0 * * *',
            ],
            'clean-expired-guest-cart' => [
                'command' => $logger . '$PHP_BIN vendor/bin/console cart:guest:clean-expired',
                'schedule' => '30 1 * * *',
            ],
            'close-outdated-quote-requests' => [
                'command' => $logger . '$PHP_BIN vendor/bin/console quote-request:close-outdated',
                'schedule' => '0 * * * *',
            ],
            'apply-price-product-schedule' => [
                'command' => $logger . '$PHP_BIN vendor/bin/console price-product:schedule:apply',
                'schedule' => '0 6 * * *',
            ],
            'check-product-offer-validity' => [
                'command' => $logger . '$PHP_BIN vendor/bin/console product-offer:check-validity',
                'schedule' => '0 6 * * *',
            ],
            'remove-expired-refresh-tokens' => [
                'command' => $logger . '$PHP_BIN vendor/bin/console oauth:refresh-token:remove-expired',
                'schedule' => '*/5 * * * *',
            ],
            'delete-expired-customer-invalidated' => [
                'command' => $logger . '$PHP_BIN vendor/bin/console customer:delete-expired-invalidated',
                'schedule' => '0 0 * * 0',
            ],
            'order-invoice-send' => [
                'command' => $logger . '$PHP_BIN vendor/bin/console order:invoice:send',
                'schedule' => '*/5 * * * *',
            ],
            'page-product-abstract-refresh' => [
                'command' => $logger . '$PHP_BIN vendor/bin/console product-page-search:product-abstract-refresh',
                'schedule' => '0 6 * * *',
            ],
            'send-push-notifications' => [
                'command' => $logger . '$PHP_BIN vendor/bin/console push-notification:send',
                'schedule' => '* * * * *',
            ],
            'glue-api-generate-documentation' => [
                'command' => $logger . '$PHP_BIN vendor/bin/glue api:generate:documentation --invalidated-after-interval 90sec',
                'schedule' => '*/1 * * * *',
            ],
            'sync-order-matrix' => [
                'command' => $logger . '$PHP_BIN vendor/bin/console order-matrix:sync',
                'schedule' => '*/1 * * * *',
            ],
            'generate-sitemap-files' => [
                'command' => $logger . '$PHP_BIN vendor/bin/console sitemap:generate',
                'schedule' => '0 0 * * *',
            ],
            'data-import-merchant-import' => [
                'command' => $logger . '$PHP_BIN vendor/bin/console data-import:merchant-import',
                'schedule' => '0 0 * * *',
            ],
        ];

        /* Push notification */
        if (getenv('SPRYKER_PUSH_NOTIFICATION_WEB_PUSH_PHP_VAPID_PUBLIC_KEY')) {
            $jobs['delete-expired-push-notification-subscriptions'] = [
                'command' => $logger . '$PHP_BIN vendor/bin/console push-notification:delete-expired-push-notification-subscriptions',
                'schedule' => '0 0 * * 0',
            ];
        }

        /* Message broker */
        if ($this->get(MessageBrokerConstants::IS_ENABLED)) {
            $jobs['message-broker-consume-channels'] = [
                'command' => $logger . '$PHP_BIN vendor/bin/console message-broker:consume --time-limit=15 --sleep=5',
                'schedule' => '* * * * *',
            ];
        }

        return $jobs;
    }

    protected function getLoggerCommand(): string
    {
        return '';
    }
}
