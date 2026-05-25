<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\AiCommerce\Business;

use Demo\Zed\AiCommerce\AiCommerceDependencyProvider;
use Demo\Zed\AiCommerce\Business\BackofficeAssistant\PlaceOrder\CartManager;
use Demo\Zed\AiCommerce\Business\BackofficeAssistant\PlaceOrder\CartManagerInterface;
use Demo\Zed\AiCommerce\Business\BackofficeAssistant\PlaceOrder\CheckoutManager;
use Demo\Zed\AiCommerce\Business\BackofficeAssistant\PlaceOrder\CheckoutManagerInterface;
use Demo\Zed\AiCommerce\Business\BackofficeAssistant\PlaceOrder\CustomerDetailsReader;
use Demo\Zed\AiCommerce\Business\BackofficeAssistant\PlaceOrder\CustomerDetailsReaderInterface;
use Demo\Zed\AiCommerce\Business\BackofficeAssistant\PlaceOrder\QuoteCustomerHydrator;
use Demo\Zed\AiCommerce\Business\BackofficeAssistant\PlaceOrder\QuoteCustomerHydratorInterface;
use Demo\Zed\AiCommerce\Business\BackofficeAssistant\PlaceOrder\QuoteManager;
use Demo\Zed\AiCommerce\Business\BackofficeAssistant\PlaceOrder\QuoteManagerInterface;
use Spryker\Zed\Cart\Business\CartFacadeInterface;
use Spryker\Zed\CartCode\Business\CartCodeFacadeInterface;
use Spryker\Zed\CartNote\Business\CartNoteFacadeInterface;
use Spryker\Zed\Checkout\Business\CheckoutFacadeInterface;
use Spryker\Zed\Customer\Business\CustomerFacadeInterface;
use Spryker\Zed\Messenger\Business\MessengerFacadeInterface;
use Spryker\Zed\Payment\Business\PaymentFacadeInterface;
use Spryker\Zed\Quote\Business\QuoteFacadeInterface;
use Spryker\Zed\Shipment\Business\ShipmentFacadeInterface;
use SprykerFeature\Zed\AiCommerce\Business\AiCommerceBusinessFactory as SprykerFeatureAiCommerceBusinessFactory;

/**
 * @method \Demo\Zed\AiCommerce\AiCommerceConfig getConfig()
 * @method \SprykerFeature\Zed\AiCommerce\Persistence\AiCommerceEntityManagerInterface getEntityManager()
 * @method \SprykerFeature\Zed\AiCommerce\Persistence\AiCommerceRepositoryInterface getRepository()
 */
class AiCommerceBusinessFactory extends SprykerFeatureAiCommerceBusinessFactory
{
    public function createPlaceOrderQuoteManager(): QuoteManagerInterface
    {
        return new QuoteManager(
            $this->getQuoteFacade(),
            $this->getCartFacade(),
            $this->getCustomerFacade(),
            $this->createPlaceOrderQuoteCustomerHydrator(),
        );
    }

    public function createPlaceOrderCartManager(): CartManagerInterface
    {
        return new CartManager(
            $this->getQuoteFacade(),
            $this->getCartFacade(),
            $this->getCartCodeFacade(),
            $this->getCartNoteFacade(),
            $this->createPlaceOrderQuoteCustomerHydrator(),
        );
    }

    public function createCustomerDetailsReader(): CustomerDetailsReaderInterface
    {
        return new CustomerDetailsReader(
            $this->getCustomerFacade(),
        );
    }

    public function createPlaceOrderQuoteCustomerHydrator(): QuoteCustomerHydratorInterface
    {
        return new QuoteCustomerHydrator(
            $this->getCustomerFacade(),
            $this->getMessengerFacade(),
        );
    }

    public function createPlaceOrderCheckoutManager(): CheckoutManagerInterface
    {
        return new CheckoutManager(
            $this->getQuoteFacade(),
            $this->getCartFacade(),
            $this->getCustomerFacade(),
            $this->getCheckoutFacade(),
            $this->getShipmentFacade(),
            $this->getPaymentFacade(),
            $this->createPlaceOrderQuoteCustomerHydrator(),
        );
    }

    public function getQuoteFacade(): QuoteFacadeInterface
    {
        return $this->getProvidedDependency(AiCommerceDependencyProvider::FACADE_QUOTE);
    }

    public function getCartFacade(): CartFacadeInterface
    {
        return $this->getProvidedDependency(AiCommerceDependencyProvider::FACADE_CART);
    }

    public function getCustomerFacade(): CustomerFacadeInterface
    {
        return $this->getProvidedDependency(AiCommerceDependencyProvider::FACADE_CUSTOMER);
    }

    public function getCartCodeFacade(): CartCodeFacadeInterface
    {
        return $this->getProvidedDependency(AiCommerceDependencyProvider::FACADE_CART_CODE);
    }

    public function getCartNoteFacade(): CartNoteFacadeInterface
    {
        return $this->getProvidedDependency(AiCommerceDependencyProvider::FACADE_CART_NOTE);
    }

    public function getMessengerFacade(): MessengerFacadeInterface
    {
        return $this->getProvidedDependency(AiCommerceDependencyProvider::FACADE_MESSENGER);
    }

    public function getCheckoutFacade(): CheckoutFacadeInterface
    {
        return $this->getProvidedDependency(AiCommerceDependencyProvider::FACADE_CHECKOUT);
    }

    public function getShipmentFacade(): ShipmentFacadeInterface
    {
        return $this->getProvidedDependency(AiCommerceDependencyProvider::FACADE_SHIPMENT);
    }

    public function getPaymentFacade(): PaymentFacadeInterface
    {
        return $this->getProvidedDependency(AiCommerceDependencyProvider::FACADE_PAYMENT);
    }
}
