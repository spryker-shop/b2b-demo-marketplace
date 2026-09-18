<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Glue\McpCommerce\Tool;

use Demo\Glue\McpCommerce\Invoker\StorefrontSubRequestInvokerInterface;
use Demo\Glue\McpCommerce\Invoker\StorefrontSubRequestResult;
use Generated\Shared\Transfer\McpIdentityTransfer;
use Spryker\Client\Store\StoreClientInterface;

/**
 * Adds a product to the authorizing customer's cart.
 *
 * Wraps the existing `carts` and cart `items` Storefront resources. Re-adding a SKU already in the
 * cart needs no merge logic here: `POST /carts/{cartId}/items` increases the existing item's quantity
 * natively, so the cart keeps one line per SKU. When the customer has no cart yet, one is created
 * through `POST /carts` first.
 *
 * An unknown SKU is rejected by the cart resource with a 422 that names the SKU, and because the
 * rejection happens inside the platform's own add-item operation the cart is left untouched.
 */
class AddToCartTool extends AbstractTool
{
    /**
     * @var string
     */
    protected const TOOL_NAME = 'add_to_cart';

    /**
     * @var string
     */
    protected const PATH_CARTS = '/carts';

    /**
     * @var string
     */
    protected const PATH_CART_ITEMS = '/carts/%s/items';

    /**
     * @var string
     */
    protected const ARGUMENT_SKU = 'sku';

    /**
     * @var string
     */
    protected const ARGUMENT_QUANTITY = 'quantity';

    /**
     * @var int
     */
    protected const DEFAULT_QUANTITY = 1;

    /**
     * @var string
     */
    protected const QUERY_PARAMETER_INCLUDE = 'include';

    /**
     * @var string
     */
    protected const INCLUDE_ITEMS = 'items';

    /**
     * @var string
     */
    protected const RESOURCE_TYPE_CARTS = 'carts';

    /**
     * @var string
     */
    protected const RESOURCE_TYPE_ITEMS = 'items';

    /**
     * @var string
     */
    protected const ATTRIBUTE_NAME = 'name';

    /**
     * @var string
     */
    protected const ATTRIBUTE_PRICE_MODE = 'priceMode';

    /**
     * @var string
     */
    protected const ATTRIBUTE_CURRENCY = 'currency';

    /**
     * @var string
     */
    protected const ATTRIBUTE_STORE = 'store';

    /**
     * @var string
     */
    protected const ATTRIBUTE_SKU = 'sku';

    /**
     * @var string
     */
    protected const ATTRIBUTE_QUANTITY = 'quantity';

    /**
     * @var string
     */
    protected const ATTRIBUTE_TOTALS = 'totals';

    /**
     * @var string
     */
    protected const TOTALS_KEY_GRAND_TOTAL = 'grandTotal';

    /**
     * @var string
     */
    protected const DEFAULT_CART_NAME = 'Assistant cart';

    /**
     * @var string
     */
    protected const DEFAULT_PRICE_MODE = 'GROSS_MODE';

    /**
     * @var string
     */
    protected const DEFAULT_CURRENCY = 'EUR';

    /**
     * @var string
     */
    protected const RESULT_KEY_CART_ID = 'cartId';

    /**
     * @var string
     */
    protected const RESULT_KEY_ITEM_COUNT = 'itemCount';

    /**
     * @var string
     */
    protected const RESULT_KEY_ITEMS = 'items';

    /**
     * @var string
     */
    protected const RESULT_KEY_CART_TOTAL = 'cartTotal';

    /**
     * @var string
     */
    protected const RESULT_KEY_SKU = 'sku';

    /**
     * @var string
     */
    protected const RESULT_KEY_QUANTITY = 'quantity';

    /**
     * @var string
     */
    protected const ERROR_MESSAGE_MISSING_SKU = 'A product SKU is required.';

    /**
     * @var string
     */
    protected const ERROR_MESSAGE_INVALID_QUANTITY = 'The quantity must be a positive whole number.';

    /**
     * Upper bound so an AI client cannot drive the cart into an absurd state (an unbounded quantity
     * produced a multi-billion-euro cart during QA). Chosen to sit far above any plausible B2B order
     * line while keeping the total representable.
     *
     * @var int
     */
    protected const MAX_QUANTITY = 10000;

    /**
     * @var string
     */
    protected const ERROR_MESSAGE_QUANTITY_TOO_LARGE = 'The quantity must not exceed %d.';

    /**
     * @var string
     */
    protected const ERROR_MESSAGE_NO_CART = 'The cart could not be created for this customer.';

    /**
     * @var string
     */
    protected const ERROR_MESSAGE_NO_STORE = 'The current store could not be resolved.';

    public function __construct(
        StorefrontSubRequestInvokerInterface $storefrontSubRequestInvoker,
        protected readonly StoreClientInterface $storeClient,
    ) {
        parent::__construct($storefrontSubRequestInvoker);
    }

    public function getName(): string
    {
        return static::TOOL_NAME;
    }

    public function getDescription(): string
    {
        return 'Adds a product to the customer\'s cart at the requested quantity and returns the cart '
            . 'identifier, its items and the cart total. Adding a SKU that is already in the cart '
            . 'increases that item\'s quantity.';
    }

    /**
     * @return array<string, mixed>
     */
    public function getInputSchema(): array
    {
        return [
            static::SCHEMA_KEY_TYPE => static::SCHEMA_TYPE_OBJECT,
            static::SCHEMA_KEY_PROPERTIES => [
                static::ARGUMENT_SKU => [
                    static::SCHEMA_KEY_TYPE => static::SCHEMA_TYPE_STRING,
                    static::SCHEMA_KEY_DESCRIPTION => 'The concrete product SKU to add to the cart.',
                ],
                static::ARGUMENT_QUANTITY => [
                    static::SCHEMA_KEY_TYPE => static::SCHEMA_TYPE_INTEGER,
                    static::SCHEMA_KEY_DESCRIPTION => 'How many units to add. Defaults to 1.',
                ],
            ],
            static::SCHEMA_KEY_REQUIRED => [static::ARGUMENT_SKU],
        ];
    }

    /**
     * @param array<string, mixed> $arguments
     */
    public function execute(McpIdentityTransfer $mcpIdentityTransfer, array $arguments): ToolResult
    {
        $sku = $this->readStringArgument($arguments, static::ARGUMENT_SKU);

        if ($sku === '') {
            return ToolResult::createError(static::ERROR_MESSAGE_MISSING_SKU);
        }

        $quantity = $this->findIntegerArgument($arguments, static::ARGUMENT_QUANTITY, static::DEFAULT_QUANTITY);

        if ($quantity === null || $quantity < 1) {
            return ToolResult::createError(static::ERROR_MESSAGE_INVALID_QUANTITY);
        }

        if ($quantity > static::MAX_QUANTITY) {
            return ToolResult::createError(
                sprintf(static::ERROR_MESSAGE_QUANTITY_TOO_LARGE, static::MAX_QUANTITY),
            );
        }

        $identityClaims = $this->createIdentityClaims($mcpIdentityTransfer);
        $cartId = $this->resolveCartId($identityClaims);

        if ($cartId === null) {
            return ToolResult::createError(static::ERROR_MESSAGE_NO_CART);
        }

        return $this->addItem($cartId, $sku, $quantity, $identityClaims);
    }

    /**
     * @param array<string, mixed> $identityClaims
     */
    protected function addItem(string $cartId, string $sku, int $quantity, array $identityClaims): ToolResult
    {
        $storefrontSubRequestResult = $this->storefrontSubRequestInvoker->invoke(
            sprintf(static::PATH_CART_ITEMS, rawurlencode($cartId)),
            static::METHOD_POST,
            $identityClaims,
            $this->createAddItemBody($sku, $quantity),
            [static::QUERY_PARAMETER_INCLUDE => static::INCLUDE_ITEMS],
        );

        if (!$storefrontSubRequestResult->isSuccessful()) {
            return ToolResult::createError($this->extractErrorMessage($storefrontSubRequestResult));
        }

        return ToolResult::createSuccess($this->createCartSummary($cartId, $storefrontSubRequestResult));
    }

    /**
     * @return array<string, mixed>
     */
    protected function createAddItemBody(string $sku, int $quantity): array
    {
        return [
            static::PAYLOAD_KEY_DATA => [
                static::PAYLOAD_KEY_TYPE => static::RESOURCE_TYPE_ITEMS,
                static::PAYLOAD_KEY_ATTRIBUTES => [
                    static::ATTRIBUTE_SKU => $sku,
                    static::ATTRIBUTE_QUANTITY => $quantity,
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function createCartSummary(
        string $cartId,
        StorefrontSubRequestResult $storefrontSubRequestResult,
    ): array {
        $cartAttributes = $this->extractResourceAttributes($storefrontSubRequestResult);
        $items = $this->mapItems($storefrontSubRequestResult);

        return [
            static::RESULT_KEY_CART_ID => $cartId,
            static::RESULT_KEY_ITEM_COUNT => count($items),
            static::RESULT_KEY_ITEMS => $items,
            static::RESULT_KEY_CART_TOTAL => $this->extractGrandTotal($cartAttributes),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function mapItems(StorefrontSubRequestResult $storefrontSubRequestResult): array
    {
        $items = [];

        foreach ($this->extractIncludedByType($storefrontSubRequestResult, static::RESOURCE_TYPE_ITEMS) as $resource) {
            $attributes = $resource[static::PAYLOAD_KEY_ATTRIBUTES] ?? null;

            if (!is_array($attributes)) {
                continue;
            }

            $items[] = [
                static::RESULT_KEY_SKU => (string)($attributes[static::ATTRIBUTE_SKU] ?? ''),
                static::RESULT_KEY_QUANTITY => (int)($attributes[static::ATTRIBUTE_QUANTITY] ?? 0),
            ];
        }

        return $items;
    }

    /**
     * @param array<string, mixed> $cartAttributes
     */
    protected function extractGrandTotal(array $cartAttributes): ?int
    {
        $totals = $cartAttributes[static::ATTRIBUTE_TOTALS] ?? null;

        if (!is_array($totals) || !isset($totals[static::TOTALS_KEY_GRAND_TOTAL])) {
            return null;
        }

        return (int)$totals[static::TOTALS_KEY_GRAND_TOTAL];
    }

    /**
     * Returns the customer's existing cart, creating one when the customer has none yet.
     *
     * @param array<string, mixed> $identityClaims
     */
    protected function resolveCartId(array $identityClaims): ?string
    {
        $existingCartId = $this->findFirstCartId($identityClaims);

        if ($existingCartId !== null) {
            return $existingCartId;
        }

        return $this->createCartId($identityClaims);
    }

    /**
     * @param array<string, mixed> $identityClaims
     */
    protected function findFirstCartId(array $identityClaims): ?string
    {
        $storefrontSubRequestResult = $this->storefrontSubRequestInvoker->invoke(
            static::PATH_CARTS,
            static::METHOD_GET,
            $identityClaims,
        );

        if (!$storefrontSubRequestResult->isSuccessful()) {
            return null;
        }

        foreach ($this->extractResourceCollection($storefrontSubRequestResult) as $resource) {
            $cartId = $resource[static::PAYLOAD_KEY_ID] ?? null;

            if (is_string($cartId) && $cartId !== '') {
                return $cartId;
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $identityClaims
     */
    protected function createCartId(array $identityClaims): ?string
    {
        $storeName = $this->findStoreName();

        if ($storeName === null) {
            return null;
        }

        $storefrontSubRequestResult = $this->storefrontSubRequestInvoker->invoke(
            static::PATH_CARTS,
            static::METHOD_POST,
            $identityClaims,
            $this->createCartBody($storeName),
        );

        if (!$storefrontSubRequestResult->isSuccessful()) {
            return null;
        }

        $data = $storefrontSubRequestResult->getPayload()[static::PAYLOAD_KEY_DATA] ?? null;
        $cartId = is_array($data) ? ($data[static::PAYLOAD_KEY_ID] ?? null) : null;

        return is_string($cartId) && $cartId !== '' ? $cartId : null;
    }

    /**
     * @return array<string, mixed>
     */
    protected function createCartBody(string $storeName): array
    {
        return [
            static::PAYLOAD_KEY_DATA => [
                static::PAYLOAD_KEY_TYPE => static::RESOURCE_TYPE_CARTS,
                static::PAYLOAD_KEY_ATTRIBUTES => [
                    static::ATTRIBUTE_NAME => static::DEFAULT_CART_NAME,
                    static::ATTRIBUTE_PRICE_MODE => static::DEFAULT_PRICE_MODE,
                    static::ATTRIBUTE_CURRENCY => static::DEFAULT_CURRENCY,
                    static::ATTRIBUTE_STORE => $storeName,
                ],
            ],
        ];
    }

    /**
     * Returns the name of the store the current request resolved to, or null when it cannot be
     * determined. `POST /carts` both requires the attribute and rejects any value other than the
     * current store, so it must be read from the shop rather than hardcoded.
     */
    protected function findStoreName(): ?string
    {
        $storeName = $this->storeClient->getCurrentStore()->getName();

        return $storeName !== null && $storeName !== '' ? $storeName : null;
    }
}
