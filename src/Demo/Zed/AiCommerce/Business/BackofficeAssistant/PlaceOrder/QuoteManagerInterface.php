<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\AiCommerce\Business\BackofficeAssistant\PlaceOrder;

interface QuoteManagerInterface
{
    /**
     * Creates a new persistent quote for a customer with store, currency, and price mode configuration.
     *
     * @param array<string, mixed> $arguments
     *
     * @return string
     */
    public function createQuote(array $arguments): string;

    /**
     * Deletes an existing quote by its ID.
     *
     * @param array<string, mixed> $arguments
     *
     * @return string
     */
    public function deleteQuote(array $arguments): string;

    /**
     * Returns the full state of a quote including items, totals, addresses, shipment, payment, and discounts.
     *
     * @param array<string, mixed> $arguments
     *
     * @return string
     */
    public function getQuoteSummary(array $arguments): string;
}
