<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\AiCommerce\Business\BackofficeAssistant\PlaceOrder;

interface CheckoutManagerInterface
{
    /**
     * Returns available checkout options: customer addresses, shipment methods, and payment methods.
     *
     * @param array<string, mixed> $arguments
     *
     * @return string
     */
    public function getCheckoutData(array $arguments): string;

    /**
     * Sets billing and/or shipping address on the quote.
     *
     * @param array<string, mixed> $arguments
     *
     * @return string
     */
    public function setAddress(array $arguments): string;

    /**
     * Lists available shipment methods or sets one on the quote.
     *
     * @param array<string, mixed> $arguments
     *
     * @return string
     */
    public function setShipmentMethod(array $arguments): string;

    /**
     * Lists available payment methods or sets one on the quote.
     *
     * @param array<string, mixed> $arguments
     *
     * @return string
     */
    public function setPayment(array $arguments): string;

    /**
     * Places an order from the current quote with all checkout data provided as parameters.
     *
     * @param array<string, mixed> $arguments
     *
     * @return string
     */
    public function placeOrder(array $arguments): string;
}
