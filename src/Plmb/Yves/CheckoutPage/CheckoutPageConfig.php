<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Plmb\Yves\CheckoutPage;

use Pyz\Yves\CheckoutPage\CheckoutPageConfig as PyzCheckoutPageConfig;

/**
 * Extends the Pyz config rather than the Spryker one so the inherited project override
 * (getExcludedPaymentMethodKeys()) is preserved — extending the core class here would
 * silently drop it.
 *
 * Only the locale-keyed T&C links are redefined, for the project locales.
 */
class CheckoutPageConfig extends PyzCheckoutPageConfig
{
    /**
     * @return array<string>
     */
    public function getLocalizedTermsAndConditionsPageLinks(): array
    {
        return [
            'en_US' => '/en/gtc',
            'pl_PL' => '/pl/gtc',
            'uk_UA' => '/uk/gtc',
        ];
    }
}
