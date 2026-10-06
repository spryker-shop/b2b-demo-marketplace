<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace PyzTest\Glue\ProductExperienceManagement\BackendApi\Integration;

use PyzTest\Glue\ProductExperienceManagement\AbstractProductExperienceManagementBackendApiTestCase;

/**
 * Validation on `POST /products`.
 *
 * Validation happens in three tiers, and only the first aggregates with the others:
 *
 * 1. Symfony constraints from `products.validation.yml`. They short-circuit the processor, so a
 *    request that trips one never reaches a domain validator. One case per constraint — they need
 *    no seeded data at all, which makes them cheap enough not to consolidate.
 * 2. Abstract-level domain validators, run by `createAbstractForConcrete()`.
 * 3. Concrete-level domain validators, run by `createProductCollection()`.
 *
 * On POST tier 2 throws before tier 3 is reached, so tiers 2 and 3 are one fat request each and
 * cannot be merged.
 *
 * @uses \SprykerFeature\Glue\ProductExperienceManagement\Api\Backend\Processor\ProductsBackendProcessor::executePostTransaction()
 *
 * Auto-generated group annotations
 *
 * @group PyzTest
 * @group Glue
 * @group ProductExperienceManagement
 * @group BackendApi
 * @group Integration
 * @group CreateProductValidationBackendApiTest
 * Add your own group annotations below this line
 */
class CreateProductValidationBackendApiTest extends AbstractProductExperienceManagementBackendApiTestCase
{
    protected const string UNKNOWN_ABSTRACT_SKU = 'pxm-unknown-abstract-sku';

    /**
     * @dataProvider constraintViolationDataProvider
     *
     * @param array<string, mixed> $override
     */
    public function testGivenAMalformedAttributeWhenCreateProductThenTheConstraintNamesIt(array $override, string $expectedPropertyPath): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $attributes = array_merge([static::ATTRIBUTE_SKU => $this->tester->generateSku('pxm-invalid-')], $override);

        // Act
        $response = $this->handleApiRequest(
            'POST',
            $this->tester->getProductCollectionUrl(),
            $this->tester->buildProductRequestBody($attributes),
        );

        // Assert
        $this->assertConstraintViolationsForPaths($response, [$expectedPropertyPath]);
    }

    /**
     * @return array<string, array{0: array<string, mixed>, 1: string}>
     */
    public static function constraintViolationDataProvider(): array
    {
        $validUrls = ['externalUrlSmall' => 'https://example.org/small.png', 'externalUrlLarge' => 'https://example.org/large.png'];

        return [
            'blank sku' => [[self::ATTRIBUTE_SKU => ''], 'sku'],
            'sku over the length limit' => [[self::ATTRIBUTE_SKU => str_repeat('a', 256)], 'sku'],
            'malformed validFrom' => [[self::ATTRIBUTE_VALID_FROM => 'not-a-date'], 'validFrom'],
            'malformed validTo' => [[self::ATTRIBUTE_VALID_TO => 'not-a-date'], 'validTo'],
            'malformed newFrom' => [[self::ATTRIBUTE_NEW_FROM => 'not-a-date'], 'newFrom'],
            'malformed newTo' => [[self::ATTRIBUTE_NEW_TO => 'not-a-date'], 'newTo'],
            'blank taxSet uuid' => [[self::ATTRIBUTE_TAX_SET => ['uuid' => '']], 'taxSet.uuid'],
            'malformed taxSet uuid' => [[self::ATTRIBUTE_TAX_SET => ['uuid' => 'not-a-uuid']], 'taxSet.uuid'],
            'blank store name' => [[self::ATTRIBUTE_STORES => ['']], 'stores.0'],
            'malformed category uuid' => [[self::ATTRIBUTE_CATEGORIES => [['uuid' => 'not-a-uuid']]], 'categories.0.uuid'],
            'blank productClass key' => [[self::ATTRIBUTE_PRODUCT_CLASS => [['key' => '']]], 'productClass.0.key'],
            'malformed shipmentType uuid' => [[self::ATTRIBUTE_SHIPMENT_TYPE => [['uuid' => 'not-a-uuid']]], 'shipmentType.0.uuid'],
            'blank price type name' => [[self::ATTRIBUTE_PRICES => [['priceTypeName' => '']]], 'prices.0.priceTypeName'],
            'blank price store name' => [[self::ATTRIBUTE_PRICES => [['storeName' => '']]], 'prices.0.storeName'],
            'malformed currency code' => [[self::ATTRIBUTE_PRICES => [['currencyCode' => 'XX']]], 'prices.0.currencyCode'],
            'negative net amount' => [[self::ATTRIBUTE_PRICES => [['netAmount' => -1]]], 'prices.0.netAmount'],
            'negative gross amount' => [[self::ATTRIBUTE_PRICES => [['grossAmount' => -1]]], 'prices.0.grossAmount'],
            'blank stock name' => [[self::ATTRIBUTE_STOCKS => [['stockName' => '']]], 'stocks.0.stockName'],
            'negative stock quantity' => [[self::ATTRIBUTE_STOCKS => [['stockName' => 'any', 'quantity' => -1]]], 'stocks.0.quantity'],
            'malformed imageSet uuid' => [[self::ATTRIBUTE_IMAGE_SETS => [['uuid' => 'not-a-uuid', 'localeName' => 'en_US']]], 'imageSets.0.uuid'],
            'blank imageSet locale name' => [[self::ATTRIBUTE_IMAGE_SETS => [['localeName' => '']]], 'imageSets.0.localeName'],
            'malformed small image url' => [
                [self::ATTRIBUTE_IMAGE_SETS => [['localeName' => 'en_US', 'images' => [['externalUrlSmall' => 'not-a-url']]]]],
                'imageSets.0.images.0.externalUrlSmall',
            ],
            'malformed large image url' => [
                [self::ATTRIBUTE_IMAGE_SETS => [['localeName' => 'en_US', 'images' => [['externalUrlLarge' => 'not-a-url']]]]],
                'imageSets.0.images.0.externalUrlLarge',
            ],
            'blank bundled sku' => [[self::ATTRIBUTE_PRODUCT_BUNDLE => [['sku' => '', 'quantity' => 1]]], 'productBundle.0.sku'],
            'non-positive bundle quantity' => [[self::ATTRIBUTE_PRODUCT_BUNDLE => [['sku' => 'any', 'quantity' => 0]]], 'productBundle.0.quantity'],
            'blank localized locale name' => [
                [self::ATTRIBUTE_LOCALIZED_ATTRIBUTES => [['localeName' => '', 'name' => 'Name']]],
                'localizedAttributes.0.localeName',
            ],
            'blank localized name' => [
                [self::ATTRIBUTE_LOCALIZED_ATTRIBUTES => [['localeName' => 'en_US', 'name' => '']]],
                'localizedAttributes.0.name',
            ],
            'valid urls with a malformed uuid still name only the uuid' => [
                [self::ATTRIBUTE_IMAGE_SETS => [array_merge(['uuid' => 'not-a-uuid', 'localeName' => 'en_US'], ['images' => [$validUrls]])]],
                'imageSets.0.uuid',
            ],
        ];
    }

    public function testGivenUnresolvableAbstractReferencesWhenCreateProductThenEveryAbstractValidatorReports(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $attributes = $this->buildValidProductAttributes([
            static::ATTRIBUTE_STORES => [static::UNKNOWN_STORE_NAME],
            static::ATTRIBUTE_TAX_SET => ['uuid' => static::NON_EXISTENT_UUID],
            static::ATTRIBUTE_CATEGORIES => [['uuid' => static::NON_EXISTENT_UUID]],
            static::ATTRIBUTE_NEW_FROM => '2027-12-31 00:00:00',
            static::ATTRIBUTE_NEW_TO => '2027-01-01 00:00:00',
            static::ATTRIBUTE_PRICES => [$this->buildPrice(['storeName' => static::UNKNOWN_STORE_NAME])],
        ]);

        // Act
        $response = $this->handleApiRequest(
            'POST',
            $this->tester->getProductCollectionUrl(),
            $this->tester->buildProductRequestBody($attributes),
        );

        // Assert
        $this->assertDomainValidationMessages($response, [
            sprintf('Store "%s" does not exist.', static::UNKNOWN_STORE_NAME),
            'Field "newFrom" must not be later than "newTo".',
            sprintf('Category with UUID "%s" does not exist.', static::NON_EXISTENT_UUID),
            sprintf('Tax set with UUID "%s" does not exist.', static::NON_EXISTENT_UUID),
            sprintf('Price #0 references unknown store "%s".', static::UNKNOWN_STORE_NAME),
        ]);
    }

    /**
     * @uses \SprykerFeature\Glue\ProductExperienceManagement\Api\Backend\Processor\ProductsBackendProcessor::hasAbstractFields()
     */
    public function testGivenUnresolvableConcreteReferencesWhenCreateProductThenEveryConcreteValidatorReports(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $uninstalledCurrencyCode = $this->requireUninstalledCurrencyCode();
        $localeName = $this->getLocaleName();
        $validImages = [
            [
                'externalUrlSmall' => 'https://example.org/small.png',
                'externalUrlLarge' => 'https://example.org/large.png',
                'sortOrder' => 0,
            ],
        ];

        $attributes = $this->buildValidProductAttributes([
            static::ATTRIBUTE_ABSTRACT_SKU => static::UNKNOWN_ABSTRACT_SKU,
            static::ATTRIBUTE_VALID_FROM => '2027-12-31 00:00:00',
            static::ATTRIBUTE_VALID_TO => '2027-01-01 00:00:00',
            static::ATTRIBUTE_LOCALIZED_ATTRIBUTES => [
                ['localeName' => static::UNKNOWN_LOCALE_NAME, 'name' => 'Unknown locale', 'isSearchable' => true],
            ],
            static::ATTRIBUTE_PRICES => [
                $this->buildPrice([
                    'priceTypeName' => static::UNKNOWN_PRICE_TYPE_NAME,
                    'currencyCode' => $uninstalledCurrencyCode,
                ]),
            ],
            static::ATTRIBUTE_STOCKS => [$this->buildStock(['stockName' => static::UNKNOWN_WAREHOUSE_NAME, 'quantity' => 1])],
            // Two sets so each failure is isolated: the first owns an unknown uuid with a valid
            // locale, the second has a valid shape with an uninstalled locale.
            static::ATTRIBUTE_IMAGE_SETS => [
                ['uuid' => static::NON_EXISTENT_UUID, 'localeName' => $localeName, 'images' => $validImages],
                ['localeName' => static::UNKNOWN_LOCALE_NAME, 'images' => $validImages],
            ],
            static::ATTRIBUTE_SHIPMENT_TYPE => [['uuid' => static::NON_EXISTENT_UUID]],
            static::ATTRIBUTE_PRODUCT_CLASS => [['key' => static::UNKNOWN_PRODUCT_CLASS_KEY]],
            static::ATTRIBUTE_PRODUCT_BUNDLE => [['sku' => static::UNKNOWN_BUNDLED_SKU, 'quantity' => 1]],
        ]);

        // Act
        $response = $this->handleApiRequest(
            'POST',
            $this->tester->getProductCollectionUrl(),
            $this->tester->buildProductRequestBody($attributes),
        );

        $this->assertDomainValidationMessages($response, [
            sprintf('Abstract product with SKU "%s" does not exist.', static::UNKNOWN_ABSTRACT_SKU),
            'Field "validFrom" must not be later than "validTo".',
            sprintf('Locale "%s" is not installed.', static::UNKNOWN_LOCALE_NAME),
            sprintf('Price #0 references unknown price type "%s".', static::UNKNOWN_PRICE_TYPE_NAME),
            sprintf('Price #0 references unknown currency "%s".', $uninstalledCurrencyCode),
            sprintf('Warehouse (stock) "%s" does not exist.', static::UNKNOWN_WAREHOUSE_NAME),
            sprintf('Image set with UUID "%s" does not exist for this product.', static::NON_EXISTENT_UUID),
            sprintf('Shipment type with UUID "%s" does not exist.', static::NON_EXISTENT_UUID),
            sprintf('Product class with key "%s" does not exist.', static::UNKNOWN_PRODUCT_CLASS_KEY),
            sprintf('Bundled concrete product with SKU "%s" does not exist.', static::UNKNOWN_BUNDLED_SKU),
        ]);
    }
}
