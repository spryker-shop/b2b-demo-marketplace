<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\AiCommerce\Business\BackofficeAssistant\PlaceOrder;

use Generated\Shared\Transfer\QuoteTransfer;

interface QuoteCustomerHydratorInterface
{
    /**
     * Hydrates customer on a quote loaded from DB via findQuoteById().
     * The quote_data JSON blob does not contain the customer transfer — only the
     * customer_reference column is persisted. Any operation that needs the full
     * CustomerTransfer will fail without this hydration.
     *
     * @param \Generated\Shared\Transfer\QuoteTransfer $quoteTransfer
     *
     * @return \Generated\Shared\Transfer\QuoteTransfer
     */
    public function hydrateQuoteCustomer(QuoteTransfer $quoteTransfer): QuoteTransfer;

    /**
     * Drains all flash messages added by cart/checkout plugins to prevent
     * them from leaking into the next Backoffice page render.
     *
     * @return void
     */
    public function drainFlashMessages(): void;
}
