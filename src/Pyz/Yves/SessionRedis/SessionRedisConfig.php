<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Yves\SessionRedis;

use Spryker\Yves\SessionRedis\SessionRedisConfig as SprykerSessionRedisConfig;

class SessionRedisConfig extends SprykerSessionRedisConfig
{
    /**
     * @return list<string>
     */
    public function getSessionRedisLockingIncludedUrlPatterns(): array
    {
        return [
            // Cart — the cart page itself and all sub-actions (sync + async); (\/|$) ensures /de/cart without trailing slash is also matched
            '/^.*\/cart(\/|$)/',
            // Voucher / promotion codes applied to the cart
            '/^.*\/cart-code\/code(|-async)\/(add|remove|clear)/',
            // Cart item and quote notes
            '/^.*\/cart-note\//',
            '/^.*\/order-custom-reference\//',
            // Multi-cart management (create, rename, delete, clear, duplicate, set-default)
            '/^.*\/multi-cart\/(create|update|delete|clear|duplicate|set-default)/',
            '/^.*\/multi-cart-async\/clear\//',
            // Re-order — rebuilds the cart from a past order
            '/^.*\/cart-reorder\//',
            // Shared cart — share and dismiss actions write to the session
            '/^.*\/shared-cart\/(share|dismiss)/',
            // Checkout — entire funnel is session-stateful (address, shipment, payment, place-order)
            '/^.*\/checkout/',
            // Customer authentication — creates or destroys the session
            '/^.*\/(login|logout|register)($|\/)/',
            // Customer account writes — profile, addresses, newsletter preference, account deletion
            '/^.*\/customer\/(profile|address|newsletter|delete)/',
        ];
    }
}
