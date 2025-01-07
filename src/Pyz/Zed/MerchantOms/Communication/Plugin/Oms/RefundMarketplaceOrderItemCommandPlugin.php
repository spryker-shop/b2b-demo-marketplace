<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\MerchantOms\Communication\Plugin\Oms;

/**
 * @method \Spryker\Zed\MerchantOms\Business\MerchantOmsFacadeInterface getFacade()
 * @method \Pyz\Zed\MerchantOms\MerchantOmsConfig getConfig()
 * @method \Pyz\Zed\MerchantOms\Communication\MerchantOmsCommunicationFactory getFactory()
 */
class RefundMarketplaceOrderItemCommandPlugin extends AbstractTriggerOmsEventCommandPlugin
{
    /**
     * @var string
     */
    protected const EVENT_REFUND = 'refund';

    /**
     * @return string
     */
    public function getEventName(): string
    {
        return static::EVENT_REFUND;
    }
}
