<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Plmb\Zed\SalesPaymentMerchant;

use Pyz\Zed\SalesPaymentMerchant\SalesPaymentMerchantConfig as PyzSalesPaymentMerchantConfig;
use Spryker\Shared\Shipment\ShipmentConfig;

/**
 * Redefines the store-keyed expense-exclusion map for the project stores.
 *
 * The inherited Pyz const is keyed by the demoshop stores (AT), so after the store rename neither
 * PL nor UA matched and both silently fell through to the core default.
 *
 * Developer decision: only UA excludes the shipment expense from the merchant payment split;
 * PL includes it (mirroring the demo's asymmetric DE/AT split).
 */
class SalesPaymentMerchantConfig extends PyzSalesPaymentMerchantConfig
{
    /**
     * @var array<string, list<string>>
     */
    protected const EXCLUDED_EXPENSE_TYPES_FOR_STORE = [
        'UA' => [ShipmentConfig::SHIPMENT_EXPENSE_TYPE],
    ];
}
