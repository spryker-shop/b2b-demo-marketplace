<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Glue\McpCommerce\Tool;

use Generated\Shared\Transfer\McpIdentityTransfer;

/**
 * Returns name, price and availability for a single concrete product.
 *
 * The `concrete-products` resource carries neither price nor availability — both live on dedicated
 * sub-resources — so this tool composes the three existing Storefront resources:
 * `/concrete-products/{sku}`, `/concrete-products/{sku}/concrete-product-prices` and
 * `/concrete-products/{sku}/concrete-product-availabilities`. Only the first is required: a product
 * without a readable price or availability record still yields a successful result with nulls, which
 * is more useful to an assistant than a hard failure.
 */
class ProductDetailTool extends AbstractTool
{
    /**
     * @var string
     */
    protected const TOOL_NAME = 'product_detail';

    /**
     * @var string
     */
    protected const PATH_CONCRETE_PRODUCT = '/concrete-products/%s';

    /**
     * @var string
     */
    protected const PATH_ABSTRACT_PRODUCT = '/abstract-products/%s';

    /**
     * @var string
     */
    protected const PATH_CONCRETE_PRODUCT_PRICES = '/concrete-products/%s/concrete-product-prices';

    /**
     * @var string
     */
    protected const PATH_CONCRETE_PRODUCT_AVAILABILITIES = '/concrete-products/%s/concrete-product-availabilities';

    /**
     * @var string
     */
    protected const ARGUMENT_SKU = 'sku';

    /**
     * @var string
     */
    protected const ATTRIBUTE_SKU = 'sku';

    /**
     * @var string
     */
    protected const ATTRIBUTE_NAME = 'name';

    /**
     * @var string
     */
    protected const ATTRIBUTE_DESCRIPTION = 'description';

    /**
     * @var string
     */
    protected const ATTRIBUTE_PRICE = 'price';

    /**
     * @var string
     */
    protected const ATTRIBUTE_AVAILABILITY = 'availability';

    /**
     * @var string
     */
    protected const ATTRIBUTE_QUANTITY = 'quantity';

    /**
     * @var string
     */
    protected const ATTRIBUTE_IS_NEVER_OUT_OF_STOCK = 'isNeverOutOfStock';

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
    protected const RESULT_KEY_DESCRIPTION = 'description';

    /**
     * @var string
     */
    protected const RESULT_KEY_PRICE = 'price';

    /**
     * @var string
     */
    protected const RESULT_KEY_IS_AVAILABLE = 'isAvailable';

    /**
     * @var string
     */
    protected const RESULT_KEY_AVAILABLE_QUANTITY = 'availableQuantity';

    /**
     * @var string
     */
    protected const ATTRIBUTE_MAP = 'attributeMap';

    /**
     * @var string
     */
    protected const ATTRIBUTE_MAP_KEY_PRODUCT_CONCRETE_IDS = 'product_concrete_ids';

    /**
     * @var string
     */
    protected const ERROR_MESSAGE_MISSING_SKU = 'A product SKU is required.';

    /**
     * @var string
     */
    protected const ERROR_MESSAGE_UNKNOWN_SKU = 'No product found for SKU "%s".';

    public function getName(): string
    {
        return static::TOOL_NAME;
    }

    public function getDescription(): string
    {
        return 'Returns the name, price and availability of a single product. Accepts either the '
            . 'concrete SKU (addToCartSku) or the abstract SKU returned by the product search.';
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
                    static::SCHEMA_KEY_DESCRIPTION => 'The concrete product SKU to look up.',
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

        $identityClaims = $this->createIdentityClaims($mcpIdentityTransfer);
        $concreteSku = $this->resolveConcreteSku($sku, $identityClaims);

        if ($concreteSku === null) {
            return ToolResult::createError(sprintf(static::ERROR_MESSAGE_UNKNOWN_SKU, $sku));
        }

        $productAttributes = $this->findConcreteProductAttributes($concreteSku, $identityClaims);

        if ($productAttributes === null) {
            return ToolResult::createError(sprintf(static::ERROR_MESSAGE_UNKNOWN_SKU, $sku));
        }

        return ToolResult::createSuccess(
            $this->createProductDetail($concreteSku, $productAttributes, $identityClaims),
        );
    }

    /**
     * Accepts either SKU kind the client can hold.
     *
     * The product search reports an abstract `sku` alongside the concrete `addToCartSku`, so an AI
     * client following the search results naturally arrives here with an abstract SKU. A concrete
     * lookup is tried first; on a miss the SKU is treated as abstract and mapped to its first
     * concrete variant through the abstract product's attribute map.
     *
     * @param array<string, mixed> $identityClaims
     */
    protected function resolveConcreteSku(string $sku, array $identityClaims): ?string
    {
        if ($this->findConcreteProductAttributes($sku, $identityClaims) !== null) {
            return $sku;
        }

        return $this->findFirstConcreteSkuOfAbstractProduct($sku, $identityClaims);
    }

    /**
     * @param array<string, mixed> $identityClaims
     *
     * @return array<string, mixed>|null
     */
    protected function findConcreteProductAttributes(string $concreteSku, array $identityClaims): ?array
    {
        $productResult = $this->storefrontSubRequestInvoker->invoke(
            sprintf(static::PATH_CONCRETE_PRODUCT, rawurlencode($concreteSku)),
            static::METHOD_GET,
            $identityClaims,
        );

        if (!$productResult->isSuccessful()) {
            return null;
        }

        $productAttributes = $this->extractResourceAttributes($productResult);

        return $productAttributes === [] ? null : $productAttributes;
    }

    /**
     * @param array<string, mixed> $identityClaims
     */
    protected function findFirstConcreteSkuOfAbstractProduct(string $abstractSku, array $identityClaims): ?string
    {
        $abstractProductResult = $this->storefrontSubRequestInvoker->invoke(
            sprintf(static::PATH_ABSTRACT_PRODUCT, rawurlencode($abstractSku)),
            static::METHOD_GET,
            $identityClaims,
        );

        if (!$abstractProductResult->isSuccessful()) {
            return null;
        }

        $attributeMap = $this->extractResourceAttributes($abstractProductResult)[static::ATTRIBUTE_MAP] ?? null;

        if (!is_array($attributeMap)) {
            return null;
        }

        $concreteSkus = $attributeMap[static::ATTRIBUTE_MAP_KEY_PRODUCT_CONCRETE_IDS] ?? null;

        if (!is_array($concreteSkus) || $concreteSkus === []) {
            return null;
        }

        $firstConcreteSku = (string)reset($concreteSkus);

        return $firstConcreteSku === '' ? null : $firstConcreteSku;
    }

    /**
     * @param array<string, mixed> $productAttributes
     * @param array<string, mixed> $identityClaims
     *
     * @return array<string, mixed>
     */
    protected function createProductDetail(string $sku, array $productAttributes, array $identityClaims): array
    {
        $priceAttributes = $this->fetchAttributes(static::PATH_CONCRETE_PRODUCT_PRICES, $sku, $identityClaims);
        $availabilityAttributes = $this->fetchAttributes(
            static::PATH_CONCRETE_PRODUCT_AVAILABILITIES,
            $sku,
            $identityClaims,
        );

        return [
            static::RESULT_KEY_SKU => (string)($productAttributes[static::ATTRIBUTE_SKU] ?? $sku),
            static::RESULT_KEY_NAME => (string)($productAttributes[static::ATTRIBUTE_NAME] ?? ''),
            static::RESULT_KEY_DESCRIPTION => (string)($productAttributes[static::ATTRIBUTE_DESCRIPTION] ?? ''),
            static::RESULT_KEY_PRICE => $priceAttributes[static::ATTRIBUTE_PRICE] ?? null,
            static::RESULT_KEY_IS_AVAILABLE => $this->extractIsAvailable($availabilityAttributes),
            static::RESULT_KEY_AVAILABLE_QUANTITY => $availabilityAttributes[static::ATTRIBUTE_QUANTITY] ?? null,
        ];
    }

    /**
     * @param array<string, mixed> $identityClaims
     *
     * @return array<string, mixed>
     */
    protected function fetchAttributes(string $pathTemplate, string $sku, array $identityClaims): array
    {
        $storefrontSubRequestResult = $this->storefrontSubRequestInvoker->invoke(
            sprintf($pathTemplate, rawurlencode($sku)),
            static::METHOD_GET,
            $identityClaims,
        );

        if (!$storefrontSubRequestResult->isSuccessful()) {
            return [];
        }

        return $this->extractResourceAttributes($storefrontSubRequestResult);
    }

    /**
     * @param array<string, mixed> $availabilityAttributes
     */
    protected function extractIsAvailable(array $availabilityAttributes): ?bool
    {
        if ($availabilityAttributes === []) {
            return null;
        }

        if (($availabilityAttributes[static::ATTRIBUTE_IS_NEVER_OUT_OF_STOCK] ?? false) === true) {
            return true;
        }

        return (bool)($availabilityAttributes[static::ATTRIBUTE_AVAILABILITY] ?? false);
    }
}
