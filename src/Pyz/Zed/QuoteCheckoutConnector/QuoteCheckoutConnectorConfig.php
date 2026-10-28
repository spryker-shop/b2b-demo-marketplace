<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\QuoteCheckoutConnector;

use Spryker\Zed\QuoteCheckoutConnector\QuoteCheckoutConnectorConfig as SprykerQuoteCheckoutConnectorConfig;
use SprykerFeature\Zed\OrderExperienceManagement\OrderExperienceManagementConfig;

class QuoteCheckoutConnectorConfig extends SprykerQuoteCheckoutConnectorConfig
{
    /**
     * @return array<string>
     */
    public function getQuoteCheckoutLockExemptSources(): array
    {
        return [
            OrderExperienceManagementConfig::SOURCE_API, # OrderExperienceManagement API
        ];
    }
}
