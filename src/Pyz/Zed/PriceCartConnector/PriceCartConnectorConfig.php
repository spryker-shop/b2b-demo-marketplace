<?php

namespace Pyz\Zed\PriceCartConnector;

use Generated\Shared\Transfer\ItemTransfer;
use Spryker\Zed\PriceCartConnector\PriceCartConnectorConfig as SprykerPriceCartConnectorConfig;

class PriceCartConnectorConfig extends SprykerPriceCartConnectorConfig
{
    /**
     * @return array<string>
     */
    public function getItemFieldsForIsSameItemComparison(): array
    {
        return array_merge(parent::getItemFieldsForIsSameItemComparison(), [
            ItemTransfer::MERCHANT_REFERENCE,
            ItemTransfer::PRODUCT_OFFER_REFERENCE,
        ]);
    }
}
