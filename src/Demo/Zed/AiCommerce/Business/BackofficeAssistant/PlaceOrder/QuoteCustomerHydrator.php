<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\AiCommerce\Business\BackofficeAssistant\PlaceOrder;

use Generated\Shared\Transfer\QuoteTransfer;
use Spryker\Zed\Customer\Business\CustomerFacadeInterface;
use Spryker\Zed\Messenger\Business\MessengerFacadeInterface;

class QuoteCustomerHydrator implements QuoteCustomerHydratorInterface
{
    public function __construct(
        protected readonly CustomerFacadeInterface $customerFacade,
        protected readonly MessengerFacadeInterface $messengerFacade,
    ) {
    }

    /**
     * @param \Generated\Shared\Transfer\QuoteTransfer $quoteTransfer
     *
     * @return \Generated\Shared\Transfer\QuoteTransfer
     */
    public function hydrateQuoteCustomer(QuoteTransfer $quoteTransfer): QuoteTransfer
    {
        if ($quoteTransfer->getCustomer()) {
            return $quoteTransfer;
        }

        if (!$quoteTransfer->getCustomerReference()) {
            return $quoteTransfer;
        }

        $customerResponse = $this->customerFacade->findCustomerByReference($quoteTransfer->getCustomerReference());

        if ($customerResponse->getHasCustomer()) {
            $quoteTransfer->setCustomer($customerResponse->getCustomerTransfer());
        }

        return $quoteTransfer;
    }

    /**
     * @return void
     */
    public function drainFlashMessages(): void
    {
        $this->messengerFacade->getStoredMessages();
    }
}
