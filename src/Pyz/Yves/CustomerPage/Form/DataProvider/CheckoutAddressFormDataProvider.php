<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Yves\CustomerPage\Form\DataProvider;

use Generated\Shared\Transfer\QuoteTransfer;
use Spryker\Shared\Kernel\Transfer\AbstractTransfer;
use SprykerShop\Yves\CustomerPage\Form\DataProvider\CheckoutAddressFormDataProvider as SprykerCheckoutAddressFormDataProvider;

class CheckoutAddressFormDataProvider extends SprykerCheckoutAddressFormDataProvider
{
    /**
     * TEMP DIAGNOSTIC (remove after): trace address-form data building. Grep "CHKTRACE".
     *
     * @param \Spryker\Shared\Kernel\Transfer\AbstractTransfer $quoteTransfer
     *
     * @return \Spryker\Shared\Kernel\Transfer\AbstractTransfer
     */
    public function getData(AbstractTransfer $quoteTransfer)
    {
        $t = microtime(true);
        error_log('[CHKTRACE] DataProvider::getData ENTER');
        $result = parent::getData($quoteTransfer);
        error_log(sprintf('[CHKTRACE] DataProvider::getData DONE dt=%.3fs', microtime(true) - $t));

        return $result;
    }

    /**
     * @param \Spryker\Shared\Kernel\Transfer\AbstractTransfer $quoteTransfer
     *
     * @return array<string, mixed>
     */
    public function getOptions(AbstractTransfer $quoteTransfer)
    {
        $t = microtime(true);
        error_log('[CHKTRACE] DataProvider::getOptions ENTER');
        $result = parent::getOptions($quoteTransfer);
        error_log(sprintf('[CHKTRACE] DataProvider::getOptions DONE dt=%.3fs', microtime(true) - $t));

        return $result;
    }

    /**
     * TEMP DIAGNOSTIC (remove after): time each address-form expander plugin. If "expanders ENTER"
     * never logs, the hang is earlier in getOptions (getAddressChoices / getAvailableCountries).
     *
     * @param \Generated\Shared\Transfer\QuoteTransfer $quoteTransfer
     * @param array<string, mixed> $options
     *
     * @return array<string, mixed>
     */
    protected function executeCheckoutAddressCollectionFormExpanderPlugins(QuoteTransfer $quoteTransfer, array $options): array
    {
        error_log(sprintf('[CHKTRACE] expanders ENTER count=%d', count($this->checkoutAddressCollectionFormExpanderPlugins)));
        foreach ($this->checkoutAddressCollectionFormExpanderPlugins as $plugin) {
            $cls = get_class($plugin);
            $t = microtime(true);
            error_log('[CHKTRACE] expander START ' . $cls);
            $options = $plugin->expandOptions($quoteTransfer, $options);
            error_log(sprintf('[CHKTRACE] expander DONE %s dt=%.3fs', $cls, microtime(true) - $t));
        }
        error_log('[CHKTRACE] expanders DONE');

        return $options;
    }

    protected function canDeliverToMultipleShippingAddresses(QuoteTransfer $quoteTransfer): bool
    {
        $t = microtime(true);
        $items = $this->productBundleClient->getGroupedBundleItems(
            $quoteTransfer->getItems(),
            $quoteTransfer->getBundleItems(),
        );

        $result = count($items) >= 1
            && $this->shipmentClient->isMultiShipmentSelectionEnabled()
            && !$this->hasQuoteGiftCardItems($quoteTransfer);
        error_log(sprintf('[CHKTRACE] canDeliverToMultipleShippingAddresses=%d items=%d dt=%.3fs', (int)$result, count($items), microtime(true) - $t));

        return $result;
    }
}
