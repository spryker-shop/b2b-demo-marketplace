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
 * `GET /products/{sku}` over a booted GLUE_BACKEND kernel.
 *
 * Auto-generated group annotations
 *
 * @group PyzTest
 * @group Glue
 * @group ProductExperienceManagement
 * @group BackendApi
 * @group Integration
 * @group GetSingleProductBackendApiTest
 * Add your own group annotations below this line
 */
class GetSingleProductBackendApiTest extends AbstractProductExperienceManagementBackendApiTestCase
{
    public function testGivenFullyPopulatedProductWhenGetProductThenEveryAttributeGroupIsExpanded(): void
    {
        // Arrange
        $this->tester->actingAsUser();

        $shipmentTypeUuid = $this->tester->haveShipmentType()->getUuidOrFail();
        $productClassKey = $this->tester->haveProductClass()->getKeyOrFail();
        $taxSetUuid = $this->haveTaxSetUuid();
        $categoryUuid = $this->haveCategoryUuid();
        $storeName = $this->getStoreName();

        $attributes = $this->haveProductViaApi([
            static::ATTRIBUTE_PRICES => [
                $this->buildPrice(),
                $this->buildPrice(['priceTypeName' => $this->havePriceTypeName(static::PRICE_TYPE_ORIGINAL)]),
            ],
            static::ATTRIBUTE_STOCKS => [
                $this->buildStock(),
                $this->buildStock([
                    'stockName' => $this->haveSecondWarehouseName(),
                    'quantity' => 0,
                    'isNeverOutOfStock' => true,
                ]),
            ],
            static::ATTRIBUTE_IMAGE_SETS => [$this->buildImageSet()],
            static::ATTRIBUTE_SHIPMENT_TYPE => [['uuid' => $shipmentTypeUuid]],
            static::ATTRIBUTE_PRODUCT_CLASS => [['key' => $productClassKey]],
            static::ATTRIBUTE_TAX_SET => ['uuid' => $taxSetUuid],
            static::ATTRIBUTE_CATEGORIES => [['uuid' => $categoryUuid]],
            static::ATTRIBUTE_STORES => [$storeName],
        ]);
        $sku = (string)$attributes[static::ATTRIBUTE_SKU];

        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getProductUrl($sku));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);

        $document = $this->decodeJsonApi($response);
        $this->assertSame(static::RESOURCE_TYPE, $document[static::JSON_API_KEY_DATA][static::JSON_API_KEY_TYPE] ?? null);
        $this->assertSame($sku, $document[static::JSON_API_KEY_DATA][static::JSON_API_KEY_ID] ?? null);

        $readAttributes = $this->getResourceAttributes($response);
        $this->assertSame($sku, $readAttributes[static::ATTRIBUTE_SKU] ?? null);
        $this->assertTrue($readAttributes[static::ATTRIBUTE_IS_ACTIVE] ?? false);

        foreach (static::ALWAYS_SERIALISED_ATTRIBUTES as $attributeName) {
            $this->assertArrayHasKey($attributeName, $readAttributes, sprintf('The resource must always serialise "%s".', $attributeName));
        }

        $defaultPrice = $this->findPrice($readAttributes, $this->getPriceTypeName(), $this->getCurrencyCode(), $storeName);
        $this->assertNotNull($defaultPrice, 'The DEFAULT price written on create must be readable.');
        $this->assertSame(static::DEFAULT_NET_AMOUNT, $defaultPrice['netAmount'] ?? null);
        $this->assertSame(static::DEFAULT_GROSS_AMOUNT, $defaultPrice['grossAmount'] ?? null);
        $this->assertNotNull(
            $this->findPrice($readAttributes, static::PRICE_TYPE_ORIGINAL, $this->getCurrencyCode(), $storeName),
            'A second price type on the same product must not displace the first.',
        );

        $stock = $this->findStock($readAttributes, $this->getWarehouseName());
        $this->assertNotNull($stock, 'The stock written on create must be readable.');
        $this->assertSame(static::DEFAULT_STOCK_QUANTITY, $stock['quantity'] ?? null);
        $this->assertIsString($stock['uuid'] ?? null);

        $neverOutOfStock = $this->findStock($readAttributes, $this->haveSecondWarehouseName());
        $this->assertNotNull($neverOutOfStock);
        $this->assertTrue($neverOutOfStock['isNeverOutOfStock'] ?? false);

        $imageSet = $this->findImageSetByName($readAttributes, 'pxm-set');
        $this->assertNotNull($imageSet, 'The image set written on create must be readable.');
        $this->assertIsString($imageSet['uuid'] ?? null);
        $this->assertSame('Default image, small', $imageSet['images'][0]['altTextSmall'] ?? null);
        $this->assertSame('Default image, large', $imageSet['images'][0]['altTextLarge'] ?? null);

        $localizedAttributes = $this->findLocalizedAttributes($readAttributes, $this->getLocaleName());
        $this->assertNotNull($localizedAttributes);
        $this->assertSame(sprintf('Concrete %s', $sku), $localizedAttributes['name'] ?? null);

        $this->assertSame('blue', $readAttributes[static::ATTRIBUTE_ATTRIBUTES][$this->getSuperAttributeKey()] ?? null);
        $this->assertSame(
            'blue',
            $readAttributes[static::ATTRIBUTE_SUPER_ATTRIBUTE_VALUES][$this->getSuperAttributeKey()] ?? null,
            'superAttributeValues is derived from the attributes that are registered as super attributes.',
        );

        $productClass = $this->findProductClass($readAttributes, $productClassKey);
        $this->assertNotNull($productClass);
        $this->assertSame(['key', 'name'], array_keys($productClass), 'The productClass read model exposes exactly key and name.');

        $this->assertNotNull($this->findShipmentType($readAttributes, $shipmentTypeUuid));

        // Abstract-level data, read through the parent abstract.
        $this->assertContains($storeName, $readAttributes[static::ATTRIBUTE_STORES] ?? []);
        $this->assertSame($taxSetUuid, $readAttributes[static::ATTRIBUTE_TAX_SET]['uuid'] ?? null);
        $this->assertNotSame('', (string)($readAttributes[static::ATTRIBUTE_TAX_SET]['name'] ?? ''));

        $category = $this->findCategory($readAttributes, $categoryUuid);
        $this->assertNotNull($category, 'The category assigned on create must be readable on the concrete.');
        $this->assertNotSame('', (string)($category['categoryKey'] ?? ''));
        $this->assertIsArray($category['localizedAttributes'] ?? null);
    }

    public function testGivenAbstractSkuOmittedAndNoAbstractFieldsWhenGetProductThenAbstractLevelDataIsEmptyNotMissing(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $attributes = $this->haveProductViaApi();
        $sku = (string)$attributes[static::ATTRIBUTE_SKU];

        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getProductUrl($sku));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);

        $readAttributes = $this->getResourceAttributes($response);
        $this->assertSame(
            sprintf('%s-abstract', $sku),
            $readAttributes[static::ATTRIBUTE_ABSTRACT_SKU] ?? null,
            'An omitted abstractSku auto-creates the abstract under the configured pattern.',
        );
        $this->assertSame([], $readAttributes[static::ATTRIBUTE_STORES] ?? null);
        $this->assertSame([], $readAttributes[static::ATTRIBUTE_CATEGORIES] ?? null);
        $this->assertArrayNotHasKey(static::ATTRIBUTE_TAX_SET, $readAttributes, 'A null taxSet is omitted, not serialised as null.');
    }

    public function testGivenUnknownSkuWhenGetProductThenNotFoundIsReturned(): void
    {
        // Arrange
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getProductUrl(static::UNKNOWN_SKU));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_NOT_FOUND);
    }
}
