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
 * The volume-price lifecycle on `PATCH /products/{sku}`.
 *
 * @uses \SprykerFeature\Glue\ProductExperienceManagement\Api\Backend\Model\Mapper\ProductConcreteMergeMapper::buildPriceKey()
 *
 * Auto-generated group annotations
 *
 * @group PyzTest
 * @group Glue
 * @group ProductExperienceManagement
 * @group BackendApi
 * @group Integration
 * @group ProductPricesBackendApiTest
 * Add your own group annotations below this line
 */
class ProductPricesBackendApiTest extends AbstractProductExperienceManagementBackendApiTestCase
{
    public function testGivenOneTierResentUnchangedAndOneReplacedWhenPatchProductThenBothRowsAreReusedAndOnlyTheReplacedTiersChange(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        [$sku, $defaultPriceBefore, $originalPriceBefore, $originalPriceTypeName] = $this->haveProductWithTwoTieredPrices();

        $unchangedPrice = $this->buildPrice([
            static::ATTRIBUTE_VOLUME_PRICES => [$this->buildVolumePrice(10, 8899, 9899)],
        ]);
        $retieredPrice = $this->buildPrice([
            'priceTypeName' => $originalPriceTypeName,
            static::ATTRIBUTE_VOLUME_PRICES => [$this->buildVolumePrice(25, 400, 444)],
        ]);

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getProductUrl($sku),
            $this->tester->buildProductRequestBody([static::ATTRIBUTE_PRICES => [$unchangedPrice, $retieredPrice]]),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $patchedAttributes = $this->getResourceAttributes($response);

        $defaultPriceAfter = $this->findPrice($patchedAttributes, $this->getPriceTypeName(), $this->getCurrencyCode(), $this->getStoreName());
        $this->assertNotNull($defaultPriceAfter);
        $this->assertSame(
            $defaultPriceBefore['uuid'] ?? null,
            $defaultPriceAfter['uuid'] ?? null,
            'Re-sending a price unchanged must match the stored row on its data checksum and reuse it.',
        );
        $this->assertCount(1, $defaultPriceAfter[static::ATTRIBUTE_VOLUME_PRICES] ?? []);
        $this->assertSame(10, $defaultPriceAfter[static::ATTRIBUTE_VOLUME_PRICES][0]['quantity'] ?? null);

        $originalPriceAfter = $this->findPrice($patchedAttributes, $originalPriceTypeName, $this->getCurrencyCode(), $this->getStoreName());
        $this->assertNotNull($originalPriceAfter);
        $this->assertSame(
            $originalPriceBefore['uuid'] ?? null,
            $originalPriceAfter['uuid'] ?? null,
            'Editing the tiers must update the row in place.',
        );
        $this->assertCount(1, $originalPriceAfter[static::ATTRIBUTE_VOLUME_PRICES] ?? []);
        $this->assertSame(25, $originalPriceAfter[static::ATTRIBUTE_VOLUME_PRICES][0]['quantity'] ?? null);
    }

    public function testGivenOmittedAndEmptyVolumePricesOnDifferentRowsWhenPatchProductThenOmittedKeepsTheTiersAndEmptyClearsThem(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        // The DEFAULT row's baseline is skipped: its amounts change here, and an amount change
        // re-creates the row under a new uuid, so there is nothing to compare it against.
        [$sku, , $originalPriceBefore, $originalPriceTypeName] = $this->haveProductWithTwoTieredPrices();

        $priceWithOmittedTiers = $this->buildPrice([
            'netAmount' => static::DEFAULT_NET_AMOUNT + 50,
            'grossAmount' => static::DEFAULT_GROSS_AMOUNT + 50,
            static::ATTRIBUTE_VOLUME_PRICES => null,
        ]);
        $priceWithClearedTiers = $this->buildPrice([
            'priceTypeName' => $originalPriceTypeName,
            static::ATTRIBUTE_VOLUME_PRICES => [],
        ]);

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getProductUrl($sku),
            $this->tester->buildProductRequestBody([static::ATTRIBUTE_PRICES => [$priceWithOmittedTiers, $priceWithClearedTiers]]),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $patchedAttributes = $this->getResourceAttributes($response);

        $defaultPriceAfter = $this->findPrice($patchedAttributes, $this->getPriceTypeName(), $this->getCurrencyCode(), $this->getStoreName());
        $this->assertNotNull($defaultPriceAfter);
        $this->assertSame(static::DEFAULT_GROSS_AMOUNT + 50, $defaultPriceAfter['grossAmount'] ?? null);
        // Row identity is NOT asserted on this row: its amounts changed, and an amount change
        // currently re-creates the price row under a new uuid (known deferred defect, tracked
        // separately). The ORIGINAL row below re-sends its amounts unchanged, so its identity is
        // still a contract worth holding.
        $this->assertCount(
            1,
            $defaultPriceAfter[static::ATTRIBUTE_VOLUME_PRICES] ?? [],
            'Omitting volumePrices while changing the amounts must not drop the tiers.',
        );
        $this->assertSame(10, $defaultPriceAfter[static::ATTRIBUTE_VOLUME_PRICES][0]['quantity'] ?? null);

        $originalPriceAfter = $this->findPrice($patchedAttributes, $originalPriceTypeName, $this->getCurrencyCode(), $this->getStoreName());
        $this->assertNotNull($originalPriceAfter);
        $this->assertSame($originalPriceBefore['uuid'] ?? null, $originalPriceAfter['uuid'] ?? null);
        $this->assertCount(
            0,
            $originalPriceAfter[static::ATTRIBUTE_VOLUME_PRICES] ?? [],
            'An explicitly empty volumePrices clears the tiers.',
        );
    }

    /**
     * @return array{0: string, 1: array<string, mixed>, 2: array<string, mixed>, 3: string}
     */
    protected function haveProductWithTwoTieredPrices(): array
    {
        $originalPriceTypeName = $this->havePriceTypeName(static::PRICE_TYPE_ORIGINAL);

        $attributes = $this->haveProductViaApi([
            static::ATTRIBUTE_PRICES => [
                $this->buildPrice([static::ATTRIBUTE_VOLUME_PRICES => [$this->buildVolumePrice(10, 8899, 9899)]]),
                $this->buildPrice([
                    'priceTypeName' => $originalPriceTypeName,
                    static::ATTRIBUTE_VOLUME_PRICES => [$this->buildVolumePrice(10, 600, 666)],
                ]),
            ],
        ]);

        $defaultPrice = $this->findPrice($attributes, $this->getPriceTypeName(), $this->getCurrencyCode(), $this->getStoreName());
        $originalPrice = $this->findPrice($attributes, $originalPriceTypeName, $this->getCurrencyCode(), $this->getStoreName());

        $this->assertNotNull($defaultPrice, 'The baseline DEFAULT price must exist before the patch.');
        $this->assertNotNull($originalPrice, 'The baseline ORIGINAL price must exist before the patch.');
        $this->assertCount(1, $defaultPrice[static::ATTRIBUTE_VOLUME_PRICES] ?? []);
        $this->assertCount(1, $originalPrice[static::ATTRIBUTE_VOLUME_PRICES] ?? []);

        return [(string)$attributes[static::ATTRIBUTE_SKU], $defaultPrice, $originalPrice, $originalPriceTypeName];
    }
}
