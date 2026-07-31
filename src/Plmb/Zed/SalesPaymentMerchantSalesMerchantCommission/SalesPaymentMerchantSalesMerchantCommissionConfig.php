<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Plmb\Zed\SalesPaymentMerchantSalesMerchantCommission;

use Pyz\Zed\SalesPaymentMerchantSalesMerchantCommission\SalesPaymentMerchantSalesMerchantCommissionConfig as PyzSalesPaymentMerchantSalesMerchantCommissionConfig;

/**
 * Redefines the store-keyed commission tax-deduction map for the project stores.
 *
 * The inherited Pyz const is keyed by the demoshop stores (DE/AT/US), so after the store rename
 * neither PL nor UA matched and both silently fell through to the core default.
 *
 * Developer decision: mirror the DE/AT semantics — no tax deduction from merchant commission in
 * either price mode — since PL and UA are both European gross-priced markets.
 */
class SalesPaymentMerchantSalesMerchantCommissionConfig extends PyzSalesPaymentMerchantSalesMerchantCommissionConfig
{
    /**
     * @var array<string, array<string, bool>>
     */
    protected const TAX_DEDUCTION_ENABLED_FOR_STORE_AND_PRICE_MODE = [
        'PL' => [self::PRICE_MODE_GROSS => false, self::PRICE_MODE_NET => false],
        'UA' => [self::PRICE_MODE_GROSS => false, self::PRICE_MODE_NET => false],
    ];
}
