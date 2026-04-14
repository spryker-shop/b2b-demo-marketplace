<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\AiCommerce\Business\BackofficeAssistant\PlaceOrder;

interface CustomerDetailsReaderInterface
{
    /**
     * Retrieves customer details including addresses by customer reference.
     *
     * @param array<string, mixed> $arguments
     */
    public function getCustomerDetails(array $arguments): string;
}
