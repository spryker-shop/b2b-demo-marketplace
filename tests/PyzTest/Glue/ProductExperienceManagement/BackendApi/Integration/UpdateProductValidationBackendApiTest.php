<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace PyzTest\Glue\ProductExperienceManagement\BackendApi\Integration;

use PyzTest\Glue\ProductExperienceManagement\AbstractProductExperienceManagementBackendApiTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Validation on `PATCH /products/{sku}`.
 *
 * @uses \SprykerFeature\Glue\ProductExperienceManagement\Api\Backend\Processor\ProductsBackendProcessor::processPatch()
 *
 * Auto-generated group annotations
 *
 * @group PyzTest
 * @group Glue
 * @group ProductExperienceManagement
 * @group BackendApi
 * @group Integration
 * @group UpdateProductValidationBackendApiTest
 * Add your own group annotations below this line
 */
class UpdateProductValidationBackendApiTest extends AbstractProductExperienceManagementBackendApiTestCase
{
    protected const string OTHER_ABSTRACT_SKU = 'pxm-some-other-abstract-sku';

    protected const int PATCHED_STOCK_QUANTITY = 7;

    public function testGivenEveryMalformedAttributeWhenPatchProductThenEachConstraintNamesItsProperty(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $sku = $this->tester->haveFullProduct()->getSkuOrFail();

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getProductUrl($sku),
            $this->tester->buildProductRequestBody($this->buildConstraintViolatingAttributes()),
        );

        $this->assertConstraintViolationsForPaths($response, [
            'validFrom',
            'validTo',
            'newFrom',
            'newTo',
            'taxSet.uuid',
            'stores.0',
            'categories.0.uuid',
            'productClass.0.key',
            'shipmentType.0.uuid',
            'prices.0.priceTypeName',
            'prices.0.storeName',
            'prices.0.currencyCode',
            'prices.0.netAmount',
            'prices.0.grossAmount',
            'stocks.0.stockName',
            'stocks.0.quantity',
            'imageSets.0.uuid',
            'imageSets.0.localeName',
            'imageSets.0.images.0.externalUrlSmall',
            'imageSets.0.images.0.externalUrlLarge',
            'productBundle.0.sku',
            'productBundle.0.quantity',
            'localizedAttributes.0.localeName',
            'localizedAttributes.0.name',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildConstraintViolatingAttributes(): array
    {
        return [
            static::ATTRIBUTE_VALID_FROM => 'not-a-date',
            static::ATTRIBUTE_VALID_TO => 'not-a-date',
            static::ATTRIBUTE_NEW_FROM => 'not-a-date',
            static::ATTRIBUTE_NEW_TO => 'not-a-date',
            static::ATTRIBUTE_TAX_SET => ['uuid' => 'not-a-uuid'],
            static::ATTRIBUTE_STORES => [''],
            static::ATTRIBUTE_CATEGORIES => [['uuid' => 'not-a-uuid']],
            static::ATTRIBUTE_PRODUCT_CLASS => [['key' => '']],
            static::ATTRIBUTE_SHIPMENT_TYPE => [['uuid' => 'not-a-uuid']],
            static::ATTRIBUTE_PRICES => [
                [
                    'priceTypeName' => '',
                    'storeName' => '',
                    'currencyCode' => 'XX',
                    'netAmount' => -1,
                    'grossAmount' => -1,
                ],
            ],
            static::ATTRIBUTE_STOCKS => [['stockName' => '', 'quantity' => -1, 'isNeverOutOfStock' => false]],
            static::ATTRIBUTE_IMAGE_SETS => [
                [
                    'uuid' => 'not-a-uuid',
                    'localeName' => '',
                    'images' => [
                        ['externalUrlSmall' => 'not-a-url', 'externalUrlLarge' => 'not-a-url', 'sortOrder' => 0],
                    ],
                ],
            ],
            static::ATTRIBUTE_PRODUCT_BUNDLE => [['sku' => '', 'quantity' => 0]],
            static::ATTRIBUTE_LOCALIZED_ATTRIBUTES => [['localeName' => '', 'name' => '']],
        ];
    }

    public function testGivenUnresolvableConcreteReferencesWhenPatchProductThenEveryConcreteValidatorReportsAndNothingIsApplied(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $uninstalledCurrencyCode = $this->requireUninstalledCurrencyCode();
        $baselineAttributes = $this->haveProductViaApi();
        $sku = (string)$baselineAttributes[static::ATTRIBUTE_SKU];
        $unknownPriceIndex = count((array)$baselineAttributes[static::ATTRIBUTE_PRICES]);
        $localeName = $this->getLocaleName();
        $validImages = [
            [
                'externalUrlSmall' => 'https://example.org/small.png',
                'externalUrlLarge' => 'https://example.org/large.png',
                'sortOrder' => 0,
            ],
        ];

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getProductUrl($sku),
            $this->tester->buildProductRequestBody([
                static::ATTRIBUTE_ABSTRACT_SKU => static::OTHER_ABSTRACT_SKU,
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
                static::ATTRIBUTE_STOCKS => [
                    // Valid on purpose — it must NOT survive the rejected request.
                    $this->buildStock(['quantity' => static::PATCHED_STOCK_QUANTITY]),
                    $this->buildStock(['stockName' => static::UNKNOWN_WAREHOUSE_NAME, 'quantity' => 1]),
                ],
                static::ATTRIBUTE_IMAGE_SETS => [
                    ['uuid' => static::NON_EXISTENT_UUID, 'localeName' => $localeName, 'images' => $validImages],
                    ['localeName' => static::UNKNOWN_LOCALE_NAME, 'images' => $validImages],
                ],
                static::ATTRIBUTE_SHIPMENT_TYPE => [['uuid' => static::NON_EXISTENT_UUID]],
                static::ATTRIBUTE_PRODUCT_CLASS => [['key' => static::UNKNOWN_PRODUCT_CLASS_KEY]],
                static::ATTRIBUTE_PRODUCT_BUNDLE => [['sku' => static::UNKNOWN_BUNDLED_SKU, 'quantity' => 1]],
            ]),
        );

        // Assert
        $this->assertDomainValidationMessages($response, [
            sprintf('Field "abstractSku" is immutable and cannot be changed to "%s".', static::OTHER_ABSTRACT_SKU),
            'Field "validFrom" must not be later than "validTo".',
            sprintf('Locale "%s" is not installed.', static::UNKNOWN_LOCALE_NAME),
            sprintf('Price #%d references unknown price type "%s".', $unknownPriceIndex, static::UNKNOWN_PRICE_TYPE_NAME),
            sprintf('Price #%d references unknown currency "%s".', $unknownPriceIndex, $uninstalledCurrencyCode),
            sprintf('Warehouse (stock) "%s" does not exist.', static::UNKNOWN_WAREHOUSE_NAME),
            sprintf('Image set with UUID "%s" does not exist for this product.', static::NON_EXISTENT_UUID),
            sprintf('Shipment type with UUID "%s" does not exist.', static::NON_EXISTENT_UUID),
            sprintf('Product class with key "%s" does not exist.', static::UNKNOWN_PRODUCT_CLASS_KEY),
            sprintf('Bundled concrete product with SKU "%s" does not exist.', static::UNKNOWN_BUNDLED_SKU),
        ]);

        $readResponse = $this->handleApiRequest('GET', $this->tester->getProductUrl($sku));
        $this->assertRespondsWithStatus($readResponse, Response::HTTP_OK);

        $readAttributes = $this->getResourceAttributes($readResponse);
        $this->assertSame(
            static::DEFAULT_STOCK_QUANTITY,
            $this->findStock($readAttributes, $this->getWarehouseName())['quantity'] ?? null,
            'A rejected patch must not apply the fields that were valid.',
        );
        $this->assertSame([], $readAttributes[static::ATTRIBUTE_IMAGE_SETS] ?? null, 'A rejected image set must not be left behind.');
        $this->assertSame(
            sprintf('%s-abstract', $sku),
            $readAttributes[static::ATTRIBUTE_ABSTRACT_SKU] ?? null,
            'The parent abstract is immutable and must be unchanged.',
        );
    }

    public function testGivenUnresolvableAbstractReferencesWhenPatchProductThenEveryAbstractValidatorReports(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $sku = (string)$this->haveProductViaApi()[static::ATTRIBUTE_SKU];

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getProductUrl($sku),
            $this->tester->buildProductRequestBody([
                static::ATTRIBUTE_STORES => [static::UNKNOWN_STORE_NAME],
                static::ATTRIBUTE_TAX_SET => ['uuid' => static::NON_EXISTENT_UUID],
                static::ATTRIBUTE_CATEGORIES => [['uuid' => static::NON_EXISTENT_UUID]],
                static::ATTRIBUTE_NEW_FROM => '2027-12-31 00:00:00',
                static::ATTRIBUTE_NEW_TO => '2027-01-01 00:00:00',
            ]),
        );

        // Assert
        $this->assertDomainValidationMessages($response, [
            sprintf('Store "%s" does not exist.', static::UNKNOWN_STORE_NAME),
            sprintf('Tax set with UUID "%s" does not exist.', static::NON_EXISTENT_UUID),
            sprintf('Category with UUID "%s" does not exist.', static::NON_EXISTENT_UUID),
            'Field "newFrom" must not be later than "newTo".',
        ]);
    }
}
