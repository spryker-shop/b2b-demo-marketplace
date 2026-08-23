<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Glue\McpCommerce\Tool;

use Generated\Shared\Transfer\McpIdentityTransfer;

/**
 * Searches the product catalog by free-text term.
 *
 * Wraps the existing `GET /catalog-search` Storefront resource, whose own default page size is 12,
 * so the "at most 12 products" contract is the platform's pagination rather than a cap applied here.
 * A term matching nothing is a successful search with an empty product list, never an error.
 */
class ProductSearchTool extends AbstractTool
{
    /**
     * @var string
     */
    protected const TOOL_NAME = 'product_search';

    /**
     * @var string
     */
    protected const PATH_CATALOG_SEARCH = '/catalog-search';

    /**
     * @var string
     */
    protected const ARGUMENT_QUERY = 'query';

    /**
     * @var string
     */
    protected const QUERY_PARAMETER_SEARCH_STRING = 'q';

    /**
     * @var string
     */
    protected const ATTRIBUTE_ABSTRACT_PRODUCTS = 'abstractProducts';

    /**
     * @var string
     */
    protected const ATTRIBUTE_PAGINATION = 'pagination';

    /**
     * @var string
     */
    protected const ATTRIBUTE_NUM_FOUND = 'numFound';

    /**
     * @var string
     */
    protected const PRODUCT_KEY_ABSTRACT_SKU = 'abstractSku';

    /**
     * @var string
     */
    protected const PRODUCT_KEY_ABSTRACT_NAME = 'abstractName';

    /**
     * @var string
     */
    protected const PRODUCT_KEY_PRICE = 'price';

    /**
     * @var string
     */
    protected const PRODUCT_KEY_ADD_TO_CART_SKU = 'addToCartSku';

    /**
     * @var string
     */
    protected const RESULT_KEY_QUERY = 'query';

    /**
     * @var string
     */
    protected const RESULT_KEY_PRODUCTS = 'products';

    /**
     * @var string
     */
    protected const RESULT_KEY_TOTAL_FOUND = 'totalFound';

    /**
     * @var string
     */
    protected const RESULT_KEY_SKU = 'sku';

    /**
     * @var string
     */
    protected const RESULT_KEY_NAME = 'name';

    /**
     * @var string
     */
    protected const RESULT_KEY_PRICE = 'price';

    /**
     * @var string
     */
    protected const RESULT_KEY_ADD_TO_CART_SKU = 'addToCartSku';

    /**
     * @var string
     */
    protected const ERROR_MESSAGE_MISSING_QUERY = 'A search term is required.';

    public function getName(): string
    {
        return static::TOOL_NAME;
    }

    public function getDescription(): string
    {
        return 'Searches the shop catalog for products matching a free-text term and returns up to 12 '
            . 'matches with their SKU, name and price. Use the returned addToCartSku when adding a '
            . 'product to the cart.';
    }

    /**
     * @return array<string, mixed>
     */
    public function getInputSchema(): array
    {
        return [
            static::SCHEMA_KEY_TYPE => static::SCHEMA_TYPE_OBJECT,
            static::SCHEMA_KEY_PROPERTIES => [
                static::ARGUMENT_QUERY => [
                    static::SCHEMA_KEY_TYPE => static::SCHEMA_TYPE_STRING,
                    static::SCHEMA_KEY_DESCRIPTION => 'What the customer is looking for, in plain language.',
                ],
            ],
            static::SCHEMA_KEY_REQUIRED => [static::ARGUMENT_QUERY],
        ];
    }

    /**
     * @param array<string, mixed> $arguments
     */
    public function execute(McpIdentityTransfer $mcpIdentityTransfer, array $arguments): ToolResult
    {
        $query = $this->readStringArgument($arguments, static::ARGUMENT_QUERY);

        if ($query === '') {
            return ToolResult::createError(static::ERROR_MESSAGE_MISSING_QUERY);
        }

        $storefrontSubRequestResult = $this->storefrontSubRequestInvoker->invoke(
            static::PATH_CATALOG_SEARCH,
            static::METHOD_GET,
            $this->createIdentityClaims($mcpIdentityTransfer),
            null,
            [static::QUERY_PARAMETER_SEARCH_STRING => $query],
        );

        if (!$storefrontSubRequestResult->isSuccessful()) {
            return ToolResult::createError($this->extractErrorMessage($storefrontSubRequestResult));
        }

        $attributes = $this->extractResourceAttributes($storefrontSubRequestResult);

        return ToolResult::createSuccess([
            static::RESULT_KEY_QUERY => $query,
            static::RESULT_KEY_TOTAL_FOUND => $this->extractTotalFound($attributes),
            static::RESULT_KEY_PRODUCTS => $this->mapProducts($attributes),
        ]);
    }

    /**
     * @param array<string, mixed> $attributes
     */
    protected function extractTotalFound(array $attributes): int
    {
        $pagination = $attributes[static::ATTRIBUTE_PAGINATION] ?? null;

        if (!is_array($pagination)) {
            return 0;
        }

        return (int)($pagination[static::ATTRIBUTE_NUM_FOUND] ?? 0);
    }

    /**
     * @param array<string, mixed> $attributes
     *
     * @return array<int, array<string, mixed>>
     */
    protected function mapProducts(array $attributes): array
    {
        $abstractProducts = $attributes[static::ATTRIBUTE_ABSTRACT_PRODUCTS] ?? null;

        if (!is_array($abstractProducts)) {
            return [];
        }

        $products = [];

        foreach ($abstractProducts as $abstractProduct) {
            if (!is_array($abstractProduct)) {
                continue;
            }

            $price = $abstractProduct[static::PRODUCT_KEY_PRICE] ?? null;

            // A result without a price is not actionable: the assistant cannot present it, and
            // offering it would invite an add-to-cart the customer cannot complete. Search indexes
            // can legitimately carry such entries (a freshly seeded environment indexes the catalog
            // before prices land), so they are omitted rather than surfaced as `price: null`.
            if ($price === null) {
                continue;
            }

            $products[] = [
                static::RESULT_KEY_SKU => (string)($abstractProduct[static::PRODUCT_KEY_ABSTRACT_SKU] ?? ''),
                static::RESULT_KEY_NAME => (string)($abstractProduct[static::PRODUCT_KEY_ABSTRACT_NAME] ?? ''),
                static::RESULT_KEY_PRICE => $price,
                static::RESULT_KEY_ADD_TO_CART_SKU => (string)($abstractProduct[static::PRODUCT_KEY_ADD_TO_CART_SKU] ?? ''),
            ];
        }

        return $products;
    }
}
