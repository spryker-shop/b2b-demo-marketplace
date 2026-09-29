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
 * `POST /products` over a booted GLUE_BACKEND kernel.
 *
 * Auto-generated group annotations
 *
 * @group PyzTest
 * @group Glue
 * @group ProductExperienceManagement
 * @group BackendApi
 * @group Integration
 * @group CreateProductBackendApiTest
 * Add your own group annotations below this line
 */
class CreateProductBackendApiTest extends AbstractProductExperienceManagementBackendApiTestCase
{
    public function testGivenFullPayloadOnAnExistingAbstractWhenCreateProductThenEveryConcreteRelationIsPersisted(): void
    {
        // Arrange
        $this->tester->actingAsUser();

        $abstractSku = $this->tester->haveFullProduct()->getAbstractSkuOrFail();
        $shipmentTypeUuid = $this->tester->haveShipmentType()->getUuidOrFail();
        $productClassKey = $this->tester->haveProductClass()->getKeyOrFail();
        $originalPriceTypeName = $this->havePriceTypeName(static::PRICE_TYPE_ORIGINAL);
        $sku = $this->tester->generateSku('pxm-concrete-');

        $attributes = $this->buildValidProductAttributes([
            static::ATTRIBUTE_SKU => $sku,
            static::ATTRIBUTE_ABSTRACT_SKU => $abstractSku,
            static::ATTRIBUTE_VALID_FROM => '2027-01-01 00:00:00',
            static::ATTRIBUTE_VALID_TO => '2027-12-31 00:00:00',
            static::ATTRIBUTE_PRICES => [
                $this->buildPrice(),
                $this->buildPrice(['priceTypeName' => $originalPriceTypeName]),
            ],
            static::ATTRIBUTE_STOCKS => [
                $this->buildStock(),
                $this->buildStock([
                    'stockName' => $this->haveSecondWarehouseName(),
                    'quantity' => 0,
                    'isNeverOutOfStock' => true,
                ]),
            ],
            static::ATTRIBUTE_IMAGE_SETS => [$this->buildImageSet(['name' => 'pxm-created-set'])],
            static::ATTRIBUTE_SHIPMENT_TYPE => [['uuid' => $shipmentTypeUuid]],
            static::ATTRIBUTE_PRODUCT_CLASS => [['key' => $productClassKey]],
        ]);

        // Act
        $response = $this->handleApiRequest(
            'POST',
            $this->tester->getProductCollectionUrl(),
            $this->tester->buildProductRequestBody($attributes),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_CREATED);

        $createdAttributes = $this->getResourceAttributes($response);
        $this->assertSame($sku, $createdAttributes[static::ATTRIBUTE_SKU] ?? null);
        $this->assertSame(
            $abstractSku,
            $createdAttributes[static::ATTRIBUTE_ABSTRACT_SKU] ?? null,
            'A supplied abstractSku attaches the concrete to that abstract instead of creating one.',
        );

        $defaultPrice = $this->findPrice($createdAttributes, $this->getPriceTypeName(), $this->getCurrencyCode(), $this->getStoreName());
        $this->assertNotNull($defaultPrice);
        $this->assertSame(static::DEFAULT_NET_AMOUNT, $defaultPrice['netAmount'] ?? null);
        $this->assertNotNull($this->findPrice($createdAttributes, $originalPriceTypeName, $this->getCurrencyCode(), $this->getStoreName()));

        $stock = $this->findStock($createdAttributes, $this->getWarehouseName());
        $this->assertNotNull($stock);
        $this->assertSame(static::DEFAULT_STOCK_QUANTITY, $stock['quantity'] ?? null);

        $neverOutOfStock = $this->findStock($createdAttributes, $this->haveSecondWarehouseName());
        $this->assertNotNull($neverOutOfStock);
        $this->assertTrue($neverOutOfStock['isNeverOutOfStock'] ?? false);

        $this->assertSame($attributes[static::ATTRIBUTE_VALID_FROM], $createdAttributes[static::ATTRIBUTE_VALID_FROM] ?? null);
        $this->assertSame($attributes[static::ATTRIBUTE_VALID_TO], $createdAttributes[static::ATTRIBUTE_VALID_TO] ?? null);

        $imageSet = $this->findImageSetByName($createdAttributes, 'pxm-created-set');
        $this->assertNotNull($imageSet);
        $this->assertSame('Default image, small', $imageSet['images'][0]['altTextSmall'] ?? null);
        $this->assertSame('Default image, large', $imageSet['images'][0]['altTextLarge'] ?? null);

        $localizedAttributes = $this->findLocalizedAttributes($createdAttributes, $this->getLocaleName());
        $this->assertNotNull($localizedAttributes);
        $this->assertSame(sprintf('Concrete %s', $sku), $localizedAttributes['name'] ?? null);
        $this->assertSame(sprintf('Description of %s', $sku), $localizedAttributes['description'] ?? null);
        $this->assertTrue($localizedAttributes['isSearchable'] ?? false);

        $this->assertNotNull($this->findShipmentType($createdAttributes, $shipmentTypeUuid));
        $this->assertNotNull($this->findProductClass($createdAttributes, $productClassKey));
        $this->assertSame(
            'blue',
            $createdAttributes[static::ATTRIBUTE_SUPER_ATTRIBUTE_VALUES][$this->getSuperAttributeKey()] ?? null,
        );
    }

    /**
     * @uses \SprykerFeature\Glue\ProductExperienceManagement\Api\Backend\Processor\ProductsBackendProcessor::executePostTransaction()
     */
    public function testGivenAnAlreadyTakenSkuWhenCreateProductThenRequestIsRejected(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $existingAttributes = $this->haveProductViaApi();
        $existingSku = (string)$existingAttributes[static::ATTRIBUTE_SKU];

        // Act
        $response = $this->handleApiRequest(
            'POST',
            $this->tester->getProductCollectionUrl(),
            $this->tester->buildProductRequestBody($this->buildValidProductAttributes([
                static::ATTRIBUTE_SKU => $existingSku,
                static::ATTRIBUTE_ABSTRACT_SKU => (string)$existingAttributes[static::ATTRIBUTE_ABSTRACT_SKU],
            ])),
        );

        // Assert
        $this->assertDomainValidationMessages($response, [
            sprintf('Concrete product with SKU "%s" already exists.', $existingSku),
        ]);
    }
}
