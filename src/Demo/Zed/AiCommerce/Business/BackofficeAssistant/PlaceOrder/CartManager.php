<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\AiCommerce\Business\BackofficeAssistant\PlaceOrder;

use Generated\Shared\Transfer\CartChangeTransfer;
use Generated\Shared\Transfer\CartCodeRequestTransfer;
use Generated\Shared\Transfer\CustomerTransfer;
use Generated\Shared\Transfer\ItemTransfer;
use Generated\Shared\Transfer\QuoteCartNoteRequestTransfer;
use Generated\Shared\Transfer\QuoteItemCartNoteRequestTransfer;
use Spryker\Zed\Cart\Business\CartFacadeInterface;
use Spryker\Zed\CartCode\Business\CartCodeFacadeInterface;
use Spryker\Zed\CartNote\Business\CartNoteFacadeInterface;
use Spryker\Zed\Quote\Business\QuoteFacadeInterface;

class CartManager implements CartManagerInterface
{
    protected const string ACTION_REMOVE = 'remove';

    protected const string ACTION_REMOVED = 'removed';

    protected const string ACTION_UPDATED = 'updated';

    public function __construct(
        protected readonly QuoteFacadeInterface $quoteFacade,
        protected readonly CartFacadeInterface $cartFacade,
        protected readonly CartCodeFacadeInterface $cartCodeFacade,
        protected readonly CartNoteFacadeInterface $cartNoteFacade,
        protected readonly QuoteCustomerHydratorInterface $quoteCustomerHydrator,
    ) {
    }

    /**
     * {@inheritDoc}
     *
     * @param array<string, mixed> $arguments
     */
    public function addItemToCart(array $arguments): string
    {
        $idQuote = (int)($arguments['idQuote'] ?? 0);
        $sku = (string)($arguments['sku'] ?? '');
        $quantity = (int)($arguments['quantity'] ?? 1);
        $merchantReference = $arguments['merchantReference'] ?? null;

        $quoteResponse = $this->quoteFacade->findQuoteById($idQuote);

        if (!$quoteResponse->getIsSuccessful()) {
            return (string)json_encode(['error' => sprintf('Quote %d not found.', $idQuote)]);
        }

        $quoteTransfer = $quoteResponse->getQuoteTransfer();

        if ($quoteTransfer === null) {
            return (string)json_encode(['error' => sprintf('Quote %d not found.', $idQuote)]);
        }

        $itemTransfer = (new ItemTransfer())
            ->setSku($sku)
            ->setQuantity($quantity);

        if ($merchantReference !== null) {
            $itemTransfer->setMerchantReference((string)$merchantReference);
        }

        $quote = $this->quoteCustomerHydrator->hydrateQuoteCustomer($quoteTransfer);

        $cartChangeTransfer = (new CartChangeTransfer())
            ->setQuote($quote)
            ->addItem($itemTransfer);

        $cartResponse = $this->cartFacade->addToCart($cartChangeTransfer);

        if (!$cartResponse->getIsSuccessful()) {
            $errors = [];

            foreach ($cartResponse->getErrors() as $error) {
                $errors[] = $error->getMessage();
            }

            return (string)json_encode(['error' => 'Failed to add item', 'details' => $errors]);
        }

        $quote = $cartResponse->getQuoteTransfer();

        if ($quote === null) {
            return (string)json_encode(['error' => sprintf('Quote %d not found.', $idQuote)]);
        }

        // Cart operations are non-persistent — persist the updated quote to DB
        $this->quoteFacade->updateQuote($quote);

        $this->quoteCustomerHydrator->drainFlashMessages();

        return (string)json_encode([
            'success' => true,
            'itemCount' => $quote->getItems()->count(),
            'grandTotal' => $quote->getTotals() ? $quote->getTotals()->getGrandTotal() : 0,
        ]);
    }

    /**
     * {@inheritDoc}
     *
     * @param array<string, mixed> $arguments
     */
    public function updateCartItem(array $arguments): string
    {
        $idQuote = (int)($arguments['idQuote'] ?? 0);
        $sku = (string)($arguments['sku'] ?? '');
        $quantity = (int)($arguments['quantity'] ?? 0);
        $groupKey = $arguments['groupKey'] ?? null;

        $quoteResponse = $this->quoteFacade->findQuoteById($idQuote);

        if (!$quoteResponse->getIsSuccessful()) {
            return (string)json_encode(['error' => sprintf('Quote %d not found.', $idQuote)]);
        }

        $quoteTransfer = $quoteResponse->getQuoteTransfer();

        if ($quoteTransfer === null) {
            return (string)json_encode(['error' => sprintf('Quote %d not found.', $idQuote)]);
        }

        $removeItemTransfer = (new ItemTransfer())->setSku($sku);

        if ($groupKey !== null) {
            $removeItemTransfer->setGroupKey((string)$groupKey);
        }

        $quote = $this->quoteCustomerHydrator->hydrateQuoteCustomer($quoteTransfer);

        $removeCartChangeTransfer = (new CartChangeTransfer())
            ->setQuote($quote)
            ->addItem($removeItemTransfer);

        $removeResponse = $this->cartFacade->removeFromCart($removeCartChangeTransfer);

        if (!$removeResponse->getIsSuccessful()) {
            $errors = [];

            foreach ($removeResponse->getErrors() as $error) {
                $errors[] = $error->getMessage();
            }

            return (string)json_encode(['error' => 'Failed to remove item', 'details' => $errors]);
        }

        $removedQuoteTransfer = $removeResponse->getQuoteTransfer();

        if ($removedQuoteTransfer === null) {
            return (string)json_encode(['error' => sprintf('Quote %d not found.', $idQuote)]);
        }

        // Quantity 0 means remove only
        if ($quantity === 0) {
            $this->quoteFacade->updateQuote($removedQuoteTransfer);
            $this->quoteCustomerHydrator->drainFlashMessages();

            return (string)json_encode([
                'success' => true,
                'action' => static::ACTION_REMOVED,
                'itemCount' => $removedQuoteTransfer->getItems()->count(),
                'grandTotal' => $removedQuoteTransfer->getTotals() ? $removedQuoteTransfer->getTotals()->getGrandTotal() : 0,
            ]);
        }

        // Add item with the updated quantity
        $addItemTransfer = (new ItemTransfer())
            ->setSku($sku)
            ->setQuantity($quantity);

        $addCartChangeTransfer = (new CartChangeTransfer())
            ->setQuote($removedQuoteTransfer)
            ->addItem($addItemTransfer);

        $addResponse = $this->cartFacade->addToCart($addCartChangeTransfer);

        if (!$addResponse->getIsSuccessful()) {
            $errors = [];

            foreach ($addResponse->getErrors() as $error) {
                $errors[] = $error->getMessage();
            }

            return (string)json_encode(['error' => 'Failed to add item with new quantity', 'details' => $errors]);
        }

        $quote = $addResponse->getQuoteTransfer();

        if ($quote === null) {
            return (string)json_encode(['error' => sprintf('Quote %d not found.', $idQuote)]);
        }

        $this->quoteFacade->updateQuote($quote);
        $this->quoteCustomerHydrator->drainFlashMessages();

        return (string)json_encode([
            'success' => true,
            'action' => static::ACTION_UPDATED,
            'itemCount' => $quote->getItems()->count(),
            'grandTotal' => $quote->getTotals() ? $quote->getTotals()->getGrandTotal() : 0,
        ]);
    }

    /**
     * {@inheritDoc}
     *
     * @param array<string, mixed> $arguments
     */
    public function manageVoucherCode(array $arguments): string
    {
        $idQuote = (int)($arguments['idQuote'] ?? 0);
        $voucherCode = (string)($arguments['voucherCode'] ?? '');
        $action = (string)($arguments['action'] ?? '');

        $quoteResponse = $this->quoteFacade->findQuoteById($idQuote);

        if (!$quoteResponse->getIsSuccessful()) {
            return (string)json_encode(['error' => sprintf('Quote %d not found.', $idQuote)]);
        }

        $quoteTransfer = $quoteResponse->getQuoteTransfer();

        if ($quoteTransfer === null) {
            return (string)json_encode(['error' => sprintf('Quote %d not found.', $idQuote)]);
        }

        $quote = $this->quoteCustomerHydrator->hydrateQuoteCustomer($quoteTransfer);

        $reloadResponse = $this->cartFacade->reloadItemsInQuote($quote);

        if ($reloadResponse->getIsSuccessful() && $reloadResponse->getQuoteTransfer() !== null) {
            $quote = $reloadResponse->getQuoteTransfer();
        }

        $cartCodeRequest = (new CartCodeRequestTransfer())
            ->setQuote($quote)
            ->setCartCode($voucherCode);

        $cartCodeResponse = $action === static::ACTION_REMOVE
            ? $this->cartCodeFacade->removeCartCode($cartCodeRequest)
            : $this->cartCodeFacade->addCartCode($cartCodeRequest);

        $messages = [];

        foreach ($cartCodeResponse->getMessages() as $message) {
            $messages[] = $message->getValue();
        }

        $resultQuote = $cartCodeResponse->getQuote();

        if ($resultQuote !== null) {
            $this->quoteFacade->updateQuote($resultQuote);
        }

        $this->quoteCustomerHydrator->drainFlashMessages();

        return (string)json_encode([
            'success' => $cartCodeResponse->getIsSuccessful(),
            'action' => $action,
            'messages' => $messages,
            'grandTotal' => $resultQuote && $resultQuote->getTotals() ? $resultQuote->getTotals()->getGrandTotal() : 0,
            'discountTotal' => $resultQuote && $resultQuote->getTotals() ? $resultQuote->getTotals()->getDiscountTotal() : 0,
        ]);
    }

    /**
     * {@inheritDoc}
     *
     * @param array<string, mixed> $arguments
     */
    public function setCartNote(array $arguments): string
    {
        $idQuote = (int)($arguments['idQuote'] ?? 0);
        $cartNote = (string)($arguments['cartNote'] ?? '');
        $customerReference = (string)($arguments['customerReference'] ?? '');
        $sku = (string)($arguments['sku'] ?? '');

        $customerTransfer = (new CustomerTransfer())->setCustomerReference($customerReference);

        if ($sku === '') {
            return $this->setQuoteNote($idQuote, $cartNote, $customerTransfer);
        }

        $groupKey = (string)($arguments['groupKey'] ?? '');

        return $this->setItemNote($idQuote, $sku, $cartNote, $customerTransfer, $groupKey);
    }

    protected function setQuoteNote(
        int $idQuote,
        string $cartNote,
        CustomerTransfer $customerTransfer,
    ): string {
        $requestTransfer = (new QuoteCartNoteRequestTransfer())
            ->setIdQuote($idQuote)
            ->setCartNote($cartNote)
            ->setCustomer($customerTransfer);

        $quoteResponse = $this->cartNoteFacade->setQuoteNote($requestTransfer);

        if (!$quoteResponse->getIsSuccessful()) {
            $errors = [];

            foreach ($quoteResponse->getErrors() as $error) {
                $errors[] = $error->getMessage();
            }

            return (string)json_encode(['error' => 'Failed to set cart note', 'details' => $errors]);
        }

        return (string)json_encode([
            'success' => true,
            'cartNote' => $cartNote,
        ]);
    }

    protected function setItemNote(
        int $idQuote,
        string $sku,
        string $cartNote,
        CustomerTransfer $customerTransfer,
        string $groupKey,
    ): string {
        $requestTransfer = (new QuoteItemCartNoteRequestTransfer())
            ->setIdQuote($idQuote)
            ->setSku($sku)
            ->setCartNote($cartNote)
            ->setCustomer($customerTransfer);

        if ($groupKey !== '') {
            $requestTransfer->setGroupKey($groupKey);
        }

        $quoteResponse = $this->cartNoteFacade->setQuoteItemNote($requestTransfer);

        if (!$quoteResponse->getIsSuccessful()) {
            $errors = [];

            foreach ($quoteResponse->getErrors() as $error) {
                $errors[] = $error->getMessage();
            }

            return (string)json_encode(['error' => 'Failed to set item note', 'details' => $errors]);
        }

        return (string)json_encode([
            'success' => true,
            'sku' => $sku,
            'cartNote' => $cartNote,
        ]);
    }
}
