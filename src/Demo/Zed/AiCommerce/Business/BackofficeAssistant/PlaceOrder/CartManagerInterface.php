<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\AiCommerce\Business\BackofficeAssistant\PlaceOrder;

interface CartManagerInterface
{
    /**
     * Adds a product to the cart by SKU. Auto-recalculates totals.
     *
     * @param array<string, mixed> $arguments
     */
    public function addItemToCart(array $arguments): string;

    /**
     * Updates item quantity or removes item from cart. Set quantity to 0 to remove.
     *
     * @param array<string, mixed> $arguments
     */
    public function updateCartItem(array $arguments): string;

    /**
     * Adds or removes a voucher/discount code on a quote.
     *
     * @param array<string, mixed> $arguments
     */
    public function manageVoucherCode(array $arguments): string;

    /**
     * Sets a note on the entire quote or a specific item.
     *
     * @param array<string, mixed> $arguments
     */
    public function setCartNote(array $arguments): string;
}
