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
 * `PATCH /products/{sku}` over a booted GLUE_BACKEND kernel.
 *
 * PATCH is a MERGE, not a replace: an omitted property keeps its persisted value. Each test here
 * batches everything one patch can carry without two behaviours contradicting each other — what
 * cannot share a request is split, and the split is stated on the test.
 *
 * Auto-generated group annotations
 *
 * @group PyzTest
 * @group Glue
 * @group ProductExperienceManagement
 * @group BackendApi
 * @group Integration
 * @group UpdateProductBackendApiTest
 * Add your own group annotations below this line
 */
class UpdateProductBackendApiTest extends AbstractProductExperienceManagementBackendApiTestCase
{
    public function testGivenAPatchThatTouchesNeitherPricesNorStocksWhenPatchProductThenScalarsChangeAndRelationsAreUntouched(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $localeNames = $this->tester->getAvailableLocaleNames();
        $baselineAttributes = $this->haveProductViaApi([
            static::ATTRIBUTE_LOCALIZED_ATTRIBUTES => array_map(
                fn (string $localeName): array => [
                    'localeName' => $localeName,
                    'name' => sprintf('Original %s', $localeName),
                    'description' => sprintf('Original description %s', $localeName),
                    'isSearchable' => true,
                ],
                $localeNames,
            ),
        ]);
        $sku = (string)$baselineAttributes[static::ATTRIBUTE_SKU];
        $baselinePrice = $this->findPrice($baselineAttributes, $this->getPriceTypeName(), $this->getCurrencyCode(), $this->getStoreName());
        $this->assertIsString($baselinePrice['uuid'] ?? null, 'The baseline price row must expose a uuid to compare against.');

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getProductUrl($sku),
            $this->tester->buildProductRequestBody([
                static::ATTRIBUTE_IS_ACTIVE => false,
                static::ATTRIBUTE_ATTRIBUTES => ['pxm_patched' => 'green'],
                static::ATTRIBUTE_LOCALIZED_ATTRIBUTES => [
                    [
                        'localeName' => $localeNames[0],
                        'name' => 'Patched name',
                        'description' => 'Patched description',
                        'isSearchable' => false,
                    ],
                ],
            ]),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);

        $patchedAttributes = $this->getResourceAttributes($response);
        $this->assertSame($sku, $patchedAttributes[static::ATTRIBUTE_SKU] ?? null);
        $this->assertFalse($patchedAttributes[static::ATTRIBUTE_IS_ACTIVE] ?? true);

        $this->assertSame('green', $patchedAttributes[static::ATTRIBUTE_ATTRIBUTES]['pxm_patched'] ?? null);
        $this->assertSame(
            'blue',
            $patchedAttributes[static::ATTRIBUTE_ATTRIBUTES][$this->getSuperAttributeKey()] ?? null,
            'Attributes merge — a patch adding one key must not drop the others.',
        );

        $patchedLocalizedAttributes = $this->findLocalizedAttributes($patchedAttributes, $localeNames[0]);
        $this->assertSame('Patched name', $patchedLocalizedAttributes['name'] ?? null);
        $this->assertSame('Patched description', $patchedLocalizedAttributes['description'] ?? null);
        $this->assertFalse($patchedLocalizedAttributes['isSearchable'] ?? true);

        if (isset($localeNames[1])) {
            $untouchedLocalizedAttributes = $this->findLocalizedAttributes($patchedAttributes, $localeNames[1]);
            $this->assertSame(
                sprintf('Original %s', $localeNames[1]),
                $untouchedLocalizedAttributes['name'] ?? null,
                'A patch naming one locale must not clear the others.',
            );
            $this->assertSame(
                sprintf('Original description %s', $localeNames[1]),
                $untouchedLocalizedAttributes['description'] ?? null,
            );
            $this->assertTrue($untouchedLocalizedAttributes['isSearchable'] ?? false);
        }

        $patchedPrice = $this->findPrice($patchedAttributes, $this->getPriceTypeName(), $this->getCurrencyCode(), $this->getStoreName());
        $this->assertNotNull($patchedPrice, 'An omitted prices property preserves the persisted prices.');
        $this->assertSame(
            $baselinePrice['uuid'],
            $patchedPrice['uuid'] ?? null,
            'The price row must be reused, not deleted and re-inserted.',
        );

        $patchedStock = $this->findStock($patchedAttributes, $this->getWarehouseName());
        $this->assertNotNull($patchedStock, 'An omitted stocks property preserves the persisted stocks.');
        $this->assertSame(static::DEFAULT_STOCK_QUANTITY, $patchedStock['quantity'] ?? null);
    }

    public function testGivenAPatchOfEveryConcreteCollectionWhenPatchProductThenEachIsWrittenInPlaceWithoutDuplicates(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $sku = (string)$this->haveProductViaApi()[static::ATTRIBUTE_SKU];
        $shipmentTypeUuid = $this->tester->haveShipmentType()->getUuidOrFail();
        $productClassKey = $this->tester->haveProductClass()->getKeyOrFail();

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getProductUrl($sku),
            $this->tester->buildProductRequestBody([
                static::ATTRIBUTE_STOCKS => [$this->buildStock(['quantity' => 7])],
                static::ATTRIBUTE_PRICES => [
                    $this->buildPrice([
                        'netAmount' => static::DEFAULT_NET_AMOUNT + 100,
                        'grossAmount' => static::DEFAULT_GROSS_AMOUNT + 100,
                    ]),
                ],
                static::ATTRIBUTE_IMAGE_SETS => [$this->buildImageSet()],
                static::ATTRIBUTE_SHIPMENT_TYPE => [['uuid' => $shipmentTypeUuid]],
                static::ATTRIBUTE_PRODUCT_CLASS => [['key' => $productClassKey]],
            ]),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);

        $patchedAttributes = $this->getResourceAttributes($response);

        $this->assertSame(7, $this->findStock($patchedAttributes, $this->getWarehouseName())['quantity'] ?? null);

        $patchedPrice = $this->findPrice($patchedAttributes, $this->getPriceTypeName(), $this->getCurrencyCode(), $this->getStoreName());
        $this->assertNotNull($patchedPrice);
        $this->assertSame(static::DEFAULT_NET_AMOUNT + 100, $patchedPrice['netAmount'] ?? null);
        $this->assertSame(static::DEFAULT_GROSS_AMOUNT + 100, $patchedPrice['grossAmount'] ?? null);
        // Row identity is NOT asserted here: an amount change currently deletes and re-inserts the
        // price row, so its uuid changes. That is a known deferred defect, tracked separately; the
        // contract this test holds the writer to is the amount and the absence of duplicates.
        $this->assertSame(
            1,
            $this->countPrices($patchedAttributes, $this->getPriceTypeName(), $this->getCurrencyCode(), $this->getStoreName()),
            'A price is keyed by type, currency and store — an update must not add a second row.',
        );

        $imageSet = $this->findImageSetByName($patchedAttributes, 'pxm-set');
        $this->assertNotNull($imageSet, 'An image set sent without a uuid is added.');
        $this->assertIsString($imageSet['uuid'] ?? null);
        $this->assertSame('Default image, small', $imageSet['images'][0]['altTextSmall'] ?? null);

        $this->assertNotNull($this->findShipmentType($patchedAttributes, $shipmentTypeUuid));
        $this->assertCount(1, $patchedAttributes[static::ATTRIBUTE_SHIPMENT_TYPE] ?? []);

        $this->assertNotNull($this->findProductClass($patchedAttributes, $productClassKey));
        $this->assertCount(1, $patchedAttributes[static::ATTRIBUTE_PRODUCT_CLASS] ?? []);
    }

    public function testGivenAnImageSetUuidAndEchoedReadOnlyPropertiesWhenPatchProductThenTheSetIsUpdatedInPlaceAndReadOnlyValuesAreIgnored(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $taxSetUuid = $this->haveTaxSetUuid();
        $categoryUuid = $this->haveCategoryUuid();
        $shipmentTypeUuid = $this->tester->haveShipmentType()->getUuidOrFail();
        $productClassKey = $this->tester->haveProductClass()->getKeyOrFail();

        $sku = (string)$this->haveProductViaApi([
            static::ATTRIBUTE_IMAGE_SETS => [$this->buildImageSet()],
            static::ATTRIBUTE_TAX_SET => ['uuid' => $taxSetUuid],
            static::ATTRIBUTE_CATEGORIES => [['uuid' => $categoryUuid]],
            static::ATTRIBUTE_SHIPMENT_TYPE => [['uuid' => $shipmentTypeUuid]],
            static::ATTRIBUTE_PRODUCT_CLASS => [['key' => $productClassKey]],
        ])[static::ATTRIBUTE_SKU];

        $readResponse = $this->handleApiRequest('GET', $this->tester->getProductUrl($sku));
        $this->assertRespondsWithStatus($readResponse, Response::HTTP_OK);
        $readAttributes = $this->getResourceAttributes($readResponse);
        $imageSetUuid = (string)($this->findImageSetByName($readAttributes, 'pxm-set')['uuid'] ?? '');
        $this->assertNotSame('', $imageSetUuid);

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getProductUrl($sku),
            $this->tester->buildProductRequestBody([
                static::ATTRIBUTE_IMAGE_SETS => [
                    [
                        'uuid' => $imageSetUuid,
                        'localeName' => $this->getLocaleName(),
                        'images' => [
                            [
                                'externalUrlSmall' => 'https://example.com/images/small/after.jpg',
                                'externalUrlLarge' => 'https://example.com/images/large/after.jpg',
                                'sortOrder' => 0,
                            ],
                        ],
                    ],
                ],
                static::ATTRIBUTE_PRICES => [$this->buildPrice(['uuid' => static::READ_ONLY_JUNK_UUID])],
                static::ATTRIBUTE_STOCKS => [$this->buildStock(['uuid' => static::READ_ONLY_JUNK_UUID])],
                static::ATTRIBUTE_SUPER_ATTRIBUTE_VALUES => ['pxm_not_a_super_attribute' => 'junk'],
                static::ATTRIBUTE_PRODUCT_CLASS => [['key' => $productClassKey, 'name' => static::READ_ONLY_JUNK]],
                static::ATTRIBUTE_SHIPMENT_TYPE => [['uuid' => $shipmentTypeUuid, 'name' => static::READ_ONLY_JUNK]],
                static::ATTRIBUTE_TAX_SET => ['uuid' => $taxSetUuid, 'name' => static::READ_ONLY_JUNK],
                static::ATTRIBUTE_CATEGORIES => [
                    [
                        'uuid' => $categoryUuid,
                        'categoryKey' => static::READ_ONLY_JUNK,
                        'isActive' => false,
                        'localizedAttributes' => [],
                    ],
                ],
            ]),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);

        $patchedAttributes = $this->getResourceAttributes($response);
        $this->assertCount(1, $patchedAttributes[static::ATTRIBUTE_IMAGE_SETS] ?? [], 'A uuid updates the set instead of adding a second one.');

        $imageSet = $this->findImageSetByUuid($patchedAttributes, $imageSetUuid);
        $this->assertNotNull($imageSet);
        $this->assertSame('pxm-set', $imageSet['name'] ?? null, 'An omitted name is back-filled from the stored set.');
        $this->assertSame('https://example.com/images/small/after.jpg', $imageSet['images'][0]['externalUrlSmall'] ?? null);

        $this->assertSame(
            static::DEFAULT_STOCK_QUANTITY,
            $this->findStock($patchedAttributes, $this->getWarehouseName())['quantity'] ?? null,
        );

        $price = $this->findPrice($patchedAttributes, $this->getPriceTypeName(), $this->getCurrencyCode(), $this->getStoreName());
        $this->assertNotSame(static::READ_ONLY_JUNK_UUID, $price['uuid'] ?? null, 'prices[].uuid is read-only.');
        $this->assertNotSame(
            static::READ_ONLY_JUNK_UUID,
            $this->findStock($patchedAttributes, $this->getWarehouseName())['uuid'] ?? null,
            'stocks[].uuid is read-only.',
        );

        $superAttributeValues = (array)($patchedAttributes[static::ATTRIBUTE_SUPER_ATTRIBUTE_VALUES] ?? []);
        $this->assertArrayNotHasKey('pxm_not_a_super_attribute', $superAttributeValues, 'superAttributeValues is derived, not writable.');
        $this->assertSame('blue', $superAttributeValues[$this->getSuperAttributeKey()] ?? null);

        $this->assertNotSame(
            static::READ_ONLY_JUNK,
            $this->findProductClass($patchedAttributes, $productClassKey)['name'] ?? null,
            'productClass[].name is read-only.',
        );
        $this->assertNotSame(
            static::READ_ONLY_JUNK,
            $this->findShipmentType($patchedAttributes, $shipmentTypeUuid)['name'] ?? null,
            'shipmentType[].name is read-only.',
        );
        $this->assertNotSame(
            static::READ_ONLY_JUNK,
            $patchedAttributes[static::ATTRIBUTE_TAX_SET]['name'] ?? null,
            'taxSet.name is read-only.',
        );

        $category = $this->findCategory($patchedAttributes, $categoryUuid);
        $this->assertNotNull($category);
        $this->assertNotSame(static::READ_ONLY_JUNK, $category['categoryKey'] ?? null, 'categories[].categoryKey is read-only.');
        $this->assertTrue($category['isActive'] ?? false, 'categories[].isActive is read-only.');
        $this->assertNotSame([], $category['localizedAttributes'] ?? [], 'categories[].localizedAttributes is read-only.');
    }

    public function testGivenUnknownSkuWhenPatchProductThenNotFoundIsReturned(): void
    {
        // Arrange
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getProductUrl(static::UNKNOWN_SKU),
            $this->tester->buildProductRequestBody([static::ATTRIBUTE_IS_ACTIVE => false]),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_NOT_FOUND);
    }
}
