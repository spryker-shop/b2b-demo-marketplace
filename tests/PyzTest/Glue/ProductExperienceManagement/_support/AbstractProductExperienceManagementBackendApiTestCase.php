<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace PyzTest\Glue\ProductExperienceManagement;

use Generated\Shared\Transfer\StoreRelationTransfer;
use SprykerTest\ApiPlatform\Test\BackendApiTestCase;
use SprykerTest\ApiPlatform\Test\JsonApiResponseAssertionsTrait;
use Symfony\Component\HttpFoundation\Response;

/**
 * PXM-specific arrangement for the `/products` backend integration suite. The JSON:API envelope
 * lives in {@see JsonApiResponseAssertionsTrait}; what remains here is the product domain — the
 * attribute vocabulary, the relation finders, the lazily-seeded environment every write payload
 * needs, and the two validation assertions the consolidated payloads depend on.
 *
 * The environment accessors are lazy on purpose: the format-constraint (tier 1) test cases are
 * rejected by the Symfony validator before the processor runs, so they need no seeded warehouse,
 * price type or locale at all, and must not pay for one.
 */
abstract class AbstractProductExperienceManagementBackendApiTestCase extends BackendApiTestCase
{
    use JsonApiResponseAssertionsTrait;

    protected const string RESOURCE_TYPE = 'products';

    /**
     * Well-formed but unresolvable — passes the `Uuid` constraint so it reaches the domain
     * validators.
     */
    protected const string NON_EXISTENT_UUID = '00000000-0000-4000-8000-000000000000';

    protected const string UNKNOWN_SKU = 'pxm-backend-api-no-such-sku';

    protected const string READ_ONLY_JUNK = 'pxm-read-only-junk';

    protected const string READ_ONLY_JUNK_UUID = '11111111-2222-4333-8444-555555555555';

    protected const string UNKNOWN_STORE_NAME = 'PXM_UNKNOWN_STORE';

    protected const string UNKNOWN_WAREHOUSE_NAME = 'PXM_UNKNOWN_WAREHOUSE';

    protected const string UNKNOWN_PRICE_TYPE_NAME = 'PXM_UNKNOWN_PRICE_TYPE';

    protected const string UNKNOWN_PRODUCT_CLASS_KEY = 'pxm-unknown-product-class';

    protected const string UNKNOWN_BUNDLED_SKU = 'pxm-unknown-bundled-sku';

    protected const string UNKNOWN_LOCALE_NAME = 'zz_ZZ';

    protected const string ATTRIBUTE_SKU = 'sku';

    protected const string ATTRIBUTE_ABSTRACT_SKU = 'abstractSku';

    protected const string ATTRIBUTE_IS_ACTIVE = 'isActive';

    protected const string ATTRIBUTE_VALID_FROM = 'validFrom';

    protected const string ATTRIBUTE_VALID_TO = 'validTo';

    protected const string ATTRIBUTE_NEW_FROM = 'newFrom';

    protected const string ATTRIBUTE_NEW_TO = 'newTo';

    protected const string ATTRIBUTE_ATTRIBUTES = 'attributes';

    protected const string ATTRIBUTE_SUPER_ATTRIBUTE_VALUES = 'superAttributeValues';

    protected const string ATTRIBUTE_LOCALIZED_ATTRIBUTES = 'localizedAttributes';

    protected const string ATTRIBUTE_PRICES = 'prices';

    protected const string ATTRIBUTE_VOLUME_PRICES = 'volumePrices';

    protected const string ATTRIBUTE_IMAGE_SETS = 'imageSets';

    protected const string ATTRIBUTE_STOCKS = 'stocks';

    protected const string ATTRIBUTE_PRODUCT_BUNDLE = 'productBundle';

    protected const string ATTRIBUTE_PRODUCT_CLASS = 'productClass';

    protected const string ATTRIBUTE_SHIPMENT_TYPE = 'shipmentType';

    protected const string ATTRIBUTE_STORES = 'stores';

    protected const string ATTRIBUTE_CATEGORIES = 'categories';

    protected const string ATTRIBUTE_TAX_SET = 'taxSet';

    /**
     * Every attribute the resource always serialises. Nullable scalars (`abstractSku`, `isActive`,
     * `validFrom`, `validTo`, `taxSet`) are omitted when null and are therefore NOT in this list.
     *
     * @var array<string>
     */
    protected const array ALWAYS_SERIALISED_ATTRIBUTES = [
        self::ATTRIBUTE_SKU,
        self::ATTRIBUTE_ATTRIBUTES,
        self::ATTRIBUTE_SUPER_ATTRIBUTE_VALUES,
        self::ATTRIBUTE_LOCALIZED_ATTRIBUTES,
        self::ATTRIBUTE_PRICES,
        self::ATTRIBUTE_IMAGE_SETS,
        self::ATTRIBUTE_STOCKS,
        self::ATTRIBUTE_PRODUCT_BUNDLE,
        self::ATTRIBUTE_PRODUCT_CLASS,
        self::ATTRIBUTE_SHIPMENT_TYPE,
        self::ATTRIBUTE_STORES,
        self::ATTRIBUTE_CATEGORIES,
    ];

    protected const string PRICE_TYPE_DEFAULT = 'DEFAULT';

    protected const string PRICE_TYPE_ORIGINAL = 'ORIGINAL';

    protected const int DEFAULT_NET_AMOUNT = 8999;

    protected const int DEFAULT_GROSS_AMOUNT = 9999;

    protected const int DEFAULT_STOCK_QUANTITY = 42;

    protected ProductExperienceManagementBackendApiIntegrationTester $tester;

    protected ?string $warehouseName = null;

    protected ?string $priceTypeName = null;

    protected ?string $superAttributeKey = null;

    protected ?string $secondWarehouseName = null;

    protected ?string $storeName = null;

    /**
     * A warehouse wired to the acting store. Seeded on first use so tier-1 cases pay nothing.
     */
    protected function getWarehouseName(): string
    {
        if ($this->warehouseName === null) {
            $this->warehouseName = $this->haveWarehouseName('PXM Warehouse ');
        }

        return $this->warehouseName;
    }

    /**
     * A second warehouse, so "two stocks on one product" is expressible without reusing a name.
     */
    protected function haveSecondWarehouseName(): string
    {
        if ($this->secondWarehouseName === null) {
            $this->secondWarehouseName = $this->haveWarehouseName('PXM Warehouse 2 ');
        }

        return $this->secondWarehouseName;
    }

    /**
     * `haveStock()` persists a seeded store relation itself, so the warehouse and its store link
     * are one call.
     */
    protected function haveWarehouseName(string $prefix): string
    {
        $storeRelationTransfer = (new StoreRelationTransfer())
            ->setIdStores([$this->tester->getStoreTransferByName($this->getStoreName())->getIdStoreOrFail()]);

        return $this->tester
            ->haveStock(['name' => uniqid($prefix, false), 'storeRelation' => $storeRelationTransfer])
            ->getNameOrFail();
    }

    /**
     * A persisted tax set, identified by a uuid we choose. The Propel uuid behaviour only generates
     * one when the column is empty, so a seeded uuid survives the insert and needs no read-back.
     */
    protected function haveTaxSetUuid(): string
    {
        $uuid = $this->tester->generateUuid(uniqid('pxm-tax-set-', false));
        $this->tester->haveTaxSet(['uuid' => $uuid]);

        return $uuid;
    }

    /**
     * Composed here rather than in a helper module: `haveCategoryViaFacade()` comes from
     * CategoriesBackendApiHelper, and Codeception merges module methods into the actor, not into
     * each other — one module cannot call another's.
     *
     * @param array<string, mixed> $seed
     */
    protected function haveCategoryUuid(array $seed = []): string
    {
        $uuid = $seed['uuid'] ?? $this->tester->generateUuid(uniqid('pxm-category-', false));
        $this->tester->haveCategoryViaFacade(['uuid' => $uuid] + $seed);

        return $uuid;
    }

    /**
     * Ensures a price type exists and returns its name; an already installed type is reused.
     */
    protected function havePriceTypeName(string $name): string
    {
        return $this->tester->havePriceType(['name' => $name])->getNameOrFail();
    }

    protected function getPriceTypeName(): string
    {
        if ($this->priceTypeName === null) {
            $this->priceTypeName = $this->havePriceTypeName(static::PRICE_TYPE_DEFAULT);
        }

        return $this->priceTypeName;
    }

    /**
     * `superAttributeValues` is derived server-side by intersecting the concrete's `attributes`
     * with the globally registered super-attribute keys.
     *
     * `is_super` is a column of `spy_product_attribute_key`, NOT of the management-attribute row,
     * and that is where the lookup filters it — so the flag belongs in the SECOND seed argument.
     * Both rows are required: the query joins management-attribute to key.
     *
     * @uses \Spryker\Zed\ProductAttribute\Persistence\ProductAttributeRepository::findSuperAttributesFromAttributesList()
     * @uses \Spryker\Zed\ProductAttribute\Business\Expander\ProductConcreteSuperAttributesExpander
     */
    protected function getSuperAttributeKey(): string
    {
        if ($this->superAttributeKey === null) {
            $superAttributeKey = uniqid('pxm_super_', false);
            $this->tester->haveProductManagementAttributeEntity([], ['key' => $superAttributeKey, 'is_super' => true]);
            $this->superAttributeKey = $superAttributeKey;
        }

        return $this->superAttributeKey;
    }

    /**
     * A currency the `Currency` constraint accepts but the shop has not installed, so a price
     * carrying it reaches the domain validator rather than being rejected a tier earlier.
     *
     * Skips instead of asserting nothing when every ICU currency is installed.
     */
    protected function requireUninstalledCurrencyCode(): string
    {
        $uninstalledCurrencyCode = $this->tester->getUninstalledCurrencyCode();

        if ($uninstalledCurrencyCode === null) {
            $this->markTestSkipped('Every ICU currency is installed, so no code can reach the domain currency validator.');
        }

        return $uninstalledCurrencyCode;
    }

    protected function getStoreName(): string
    {
        if ($this->storeName === null) {
            $this->storeName = $this->tester->getCurrentStoreName();
        }

        return $this->storeName;
    }

    protected function getCurrencyCode(): string
    {
        return $this->tester->getDefaultCurrencyCode($this->getStoreName());
    }

    protected function getLocaleName(): string
    {
        return $this->tester->getAvailableLocaleNames()[0];
    }

    /**
     * A complete, valid POST payload. Every key can be overridden; passing `null` for a key removes
     * it, which is how the "omitted vs empty" behaviours are expressed.
     *
     * @param array<string, mixed> $override
     *
     * @return array<string, mixed>
     */
    protected function buildValidProductAttributes(array $override = []): array
    {
        $sku = (string)($override[static::ATTRIBUTE_SKU] ?? $this->tester->generateSku('pxm-concrete-'));

        $attributes = [
            static::ATTRIBUTE_SKU => $sku,
            static::ATTRIBUTE_IS_ACTIVE => true,
            static::ATTRIBUTE_ATTRIBUTES => [$this->getSuperAttributeKey() => 'blue'],
            static::ATTRIBUTE_LOCALIZED_ATTRIBUTES => [
                [
                    'localeName' => $this->getLocaleName(),
                    'name' => sprintf('Concrete %s', $sku),
                    'description' => sprintf('Description of %s', $sku),
                    'isSearchable' => true,
                ],
            ],
            static::ATTRIBUTE_PRICES => [$this->buildPrice()],
            static::ATTRIBUTE_STOCKS => [$this->buildStock()],
        ];

        return $this->applyOverride($attributes, $override);
    }

    /**
     * @param array<string, mixed> $override
     *
     * @return array<string, mixed>
     */
    protected function buildPrice(array $override = []): array
    {
        return $this->applyOverride([
            'priceTypeName' => $this->getPriceTypeName(),
            'storeName' => $this->getStoreName(),
            'currencyCode' => $this->getCurrencyCode(),
            'netAmount' => static::DEFAULT_NET_AMOUNT,
            'grossAmount' => static::DEFAULT_GROSS_AMOUNT,
        ], $override);
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildVolumePrice(int $quantity, int $netPrice, int $grossPrice): array
    {
        return ['quantity' => $quantity, 'netPrice' => $netPrice, 'grossPrice' => $grossPrice];
    }

    /**
     * @param array<string, mixed> $override
     *
     * @return array<string, mixed>
     */
    protected function buildStock(array $override = []): array
    {
        return $this->applyOverride([
            'stockName' => $this->getWarehouseName(),
            'quantity' => static::DEFAULT_STOCK_QUANTITY,
            'isNeverOutOfStock' => false,
        ], $override);
    }

    /**
     * @param array<string, mixed> $override
     *
     * @return array<string, mixed>
     */
    protected function buildImageSet(array $override = []): array
    {
        return $this->applyOverride([
            'name' => 'pxm-set',
            'localeName' => $this->getLocaleName(),
            'images' => [
                [
                    'externalUrlSmall' => 'https://example.com/images/small/default.jpg',
                    'externalUrlLarge' => 'https://example.com/images/large/default.jpg',
                    'altTextSmall' => 'Default image, small',
                    'altTextLarge' => 'Default image, large',
                    'sortOrder' => 0,
                ],
            ],
        ], $override);
    }

    /**
     * Merges an override over a base payload, treating an explicit `null` as "remove this key" so a
     * caller can express an omitted property — which is a distinct behaviour from an empty one.
     *
     * @param array<string, mixed> $base
     * @param array<string, mixed> $override
     *
     * @return array<string, mixed>
     */
    protected function applyOverride(array $base, array $override): array
    {
        foreach ($override as $key => $value) {
            if ($value === null) {
                unset($base[$key]);

                continue;
            }

            $base[$key] = $value;
        }

        return $base;
    }

    /**
     * Provisions a product through the API as the acting operator and returns the created
     * attributes. One POST seeds the concrete AND its abstract with every relation the resource
     * accepts, so it is the primary arrange primitive of this suite.
     *
     * @param array<string, mixed> $override
     *
     * @return array<string, mixed>
     */
    protected function haveProductViaApi(array $override = []): array
    {
        $attributes = $this->buildValidProductAttributes($override);

        $response = $this->handleApiRequest(
            'POST',
            $this->tester->getProductCollectionUrl(),
            $this->tester->buildProductRequestBody($attributes),
        );

        $this->assertSame(
            Response::HTTP_CREATED,
            $response->getStatusCode(),
            sprintf('Could not provision the product under test: %s', (string)$response->getContent()),
        );

        return $this->getResourceAttributes($response);
    }

    /**
     * Collection members keyed by their own sku — the shape the cross-wiring regression reads.
     *
     * @return array<string, array<string, mixed>>
     */
    protected function getMemberAttributesBySku(Response $response): array
    {
        $attributesBySku = [];

        foreach ($this->getJsonApiMembers($response, static::JSON_API_KEY_DATA) as $member) {
            $attributes = (array)($member[static::JSON_API_KEY_ATTRIBUTES] ?? []);
            $attributesBySku[(string)($attributes[static::ATTRIBUTE_SKU] ?? '')] = $attributes;
        }

        return $attributesBySku;
    }

    /**
     * Locates one price row by the triple the writer keys rows on.
     *
     * @uses \SprykerFeature\Glue\ProductExperienceManagement\Api\Backend\Model\Mapper\ProductConcreteMergeMapper::buildPriceKey()
     *
     * @param array<string, mixed> $attributes
     *
     * @return array<string, mixed>|null
     */
    protected function findPrice(array $attributes, string $priceTypeName, string $currencyCode, string $storeName): ?array
    {
        foreach ($this->filterPrices($attributes, $priceTypeName, $currencyCode, $storeName) as $price) {
            return $price;
        }

        return null;
    }

    /**
     * Exactly one row per triple is the no-duplicate contract.
     *
     * @param array<string, mixed> $attributes
     */
    protected function countPrices(array $attributes, string $priceTypeName, string $currencyCode, string $storeName): int
    {
        return count($this->filterPrices($attributes, $priceTypeName, $currencyCode, $storeName));
    }

    /**
     * @param array<string, mixed> $attributes
     *
     * @return array<int, array<string, mixed>>
     */
    protected function filterPrices(array $attributes, string $priceTypeName, string $currencyCode, string $storeName): array
    {
        $prices = [];

        foreach ((array)($attributes[static::ATTRIBUTE_PRICES] ?? []) as $price) {
            if (
                !(($price['priceTypeName'] ?? null) === $priceTypeName)
                || !(($price['currencyCode'] ?? null) === $currencyCode)
                || !(($price['storeName'] ?? null) === $storeName)
            ) {
                continue;
            }

            $prices[] = (array)$price;
        }

        return $prices;
    }

    /**
     * @param array<string, mixed> $attributes
     *
     * @return array<string, mixed>|null
     */
    protected function findStock(array $attributes, string $stockName): ?array
    {
        return $this->findEntryByField((array)($attributes[static::ATTRIBUTE_STOCKS] ?? []), 'stockName', $stockName);
    }

    /**
     * @param array<string, mixed> $attributes
     *
     * @return array<string, mixed>|null
     */
    protected function findImageSetByUuid(array $attributes, string $uuid): ?array
    {
        return $this->findEntryByField((array)($attributes[static::ATTRIBUTE_IMAGE_SETS] ?? []), 'uuid', $uuid);
    }

    /**
     * @param array<string, mixed> $attributes
     *
     * @return array<string, mixed>|null
     */
    protected function findImageSetByName(array $attributes, string $name): ?array
    {
        return $this->findEntryByField((array)($attributes[static::ATTRIBUTE_IMAGE_SETS] ?? []), 'name', $name);
    }

    /**
     * @param array<string, mixed> $attributes
     *
     * @return array<string, mixed>|null
     */
    protected function findLocalizedAttributes(array $attributes, string $localeName): ?array
    {
        return $this->findEntryByField((array)($attributes[static::ATTRIBUTE_LOCALIZED_ATTRIBUTES] ?? []), 'localeName', $localeName);
    }

    /**
     * @param array<string, mixed> $attributes
     *
     * @return array<string, mixed>|null
     */
    protected function findCategory(array $attributes, string $uuid): ?array
    {
        return $this->findEntryByField((array)($attributes[static::ATTRIBUTE_CATEGORIES] ?? []), 'uuid', $uuid);
    }

    /**
     * @param array<string, mixed> $attributes
     *
     * @return array<string, mixed>|null
     */
    protected function findShipmentType(array $attributes, string $uuid): ?array
    {
        return $this->findEntryByField((array)($attributes[static::ATTRIBUTE_SHIPMENT_TYPE] ?? []), 'uuid', $uuid);
    }

    /**
     * @param array<string, mixed> $attributes
     *
     * @return array<string, mixed>|null
     */
    protected function findProductClass(array $attributes, string $key): ?array
    {
        return $this->findEntryByField((array)($attributes[static::ATTRIBUTE_PRODUCT_CLASS] ?? []), 'key', $key);
    }

    /**
     * @param array<string, mixed> $attributes
     *
     * @return array<string, mixed>|null
     */
    protected function findBundledProduct(array $attributes, string $sku): ?array
    {
        return $this->findEntryByField((array)($attributes[static::ATTRIBUTE_PRODUCT_BUNDLE] ?? []), 'sku', $sku);
    }

    /**
     * @param array<int, mixed> $entries
     *
     * @return array<string, mixed>|null
     */
    protected function findEntryByField(array $entries, string $field, string $value): ?array
    {
        foreach ($entries as $entry) {
            if (($entry[$field] ?? null) === $value) {
                return (array)$entry;
            }
        }

        return null;
    }

    /**
     * The property paths named by a tier-1 (Symfony constraint) rejection.
     *
     * Two shapes are accepted because the document depends on how many violations the request
     * produced: {@see \Spryker\ApiPlatform\EventSubscriber\GlueApiExceptionSubscriber::normalizeValidationErrorFormat()}
     * rewrites a SINGLE newline-joined error into one entry per violation with the detail
     * `path => message`, while a document that already carries one entry per violation keeps API
     * Platform's own `source.pointer`. Reading both keeps a consolidated payload asserting the same
     * thing as a single-constraint one.
     *
     * @return array<string>
     */
    protected function getErrorPropertyPaths(Response $response): array
    {
        $propertyPaths = [];

        foreach ($this->getJsonApiMembers($response, static::JSON_API_KEY_ERRORS) as $error) {
            $detail = (string)($error[static::JSON_API_KEY_DETAIL] ?? '');
            $separatorPosition = strpos($detail, ' => ');

            if ($separatorPosition !== false) {
                $propertyPaths[] = substr($detail, 0, $separatorPosition);

                continue;
            }

            $pointer = (string)($error['source']['pointer'] ?? '');

            if ($pointer === '') {
                continue;
            }

            $propertyPaths[] = $this->normalizePointerToPropertyPath($pointer);
        }

        return $propertyPaths;
    }

    /**
     * `data/attributes/prices/0/currencyCode` -> `prices.0.currencyCode`, the notation the
     * normalized single-error shape uses.
     */
    protected function normalizePointerToPropertyPath(string $pointer): string
    {
        $segments = array_values(array_filter(explode('/', $pointer), static fn (string $segment): bool => $segment !== ''));

        if (($segments[0] ?? null) === 'data') {
            array_shift($segments);
        }

        if (($segments[0] ?? null) === 'attributes') {
            array_shift($segments);
        }

        return implode('.', $segments);
    }

    /**
     * Asserts a tier-1 rejection names exactly the given property paths.
     *
     * @param array<string> $expectedPropertyPaths
     */
    protected function assertConstraintViolationsForPaths(Response $response, array $expectedPropertyPaths): void
    {
        $body = (string)$response->getContent();

        $this->assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $response->getStatusCode(), $body);

        $this->assertValidationErrorCode($response);

        $actualPropertyPaths = $this->getErrorPropertyPaths($response);

        foreach ($expectedPropertyPaths as $expectedPropertyPath) {
            $this->assertContains(
                $expectedPropertyPath,
                $actualPropertyPaths,
                sprintf('Expected a constraint violation on "%s". Body: %s', $expectedPropertyPath, $body),
            );
        }
    }

    /**
     * Asserts a tier-2/tier-3 rejection carries exactly the given messages.
     *
     * The exact count is not decoration: a consolidated payload asserts many validators in one
     * request, and a validator that starts returning early would otherwise shrink the message set
     * without failing a `assertContains`-only test.
     *
     * @param array<string> $expectedMessages
     */
    protected function assertDomainValidationMessages(Response $response, array $expectedMessages): void
    {
        $body = (string)$response->getContent();

        $this->assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $response->getStatusCode(), $body);

        $this->assertValidationErrorCode($response);

        $actualDetails = $this->getErrorDetails($response);

        foreach ($expectedMessages as $expectedMessage) {
            $this->assertContains(
                $expectedMessage,
                $actualDetails,
                sprintf('Expected the validation message "%s". Body: %s', $expectedMessage, $body),
            );
        }

        $this->assertCount(
            count($expectedMessages),
            $actualDetails,
            sprintf('The response carried validation messages the test does not assert. Body: %s', $body),
        );
    }

    /**
     * Both tiers answer with the same validation error code, so every entry of a rejection carries
     * it — a document mixing codes means something other than validation also failed.
     */
    protected function assertValidationErrorCode(Response $response): void
    {
        $errorCodes = $this->getErrorCodes($response);

        $this->assertNotSame([], $errorCodes, (string)$response->getContent());

        foreach ($errorCodes as $errorCode) {
            $this->assertSame(static::RESPONSE_CODE_VALIDATION, $errorCode, (string)$response->getContent());
        }
    }
}
