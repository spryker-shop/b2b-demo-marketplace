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
 * The abstract-level properties written through the concrete resource: `stores`, `taxSet`,
 * `categories`, `newFrom` and `newTo`.
 *
 * @uses \SprykerFeature\Glue\ProductExperienceManagement\Api\Backend\Processor\ProductsBackendProcessor::hasAbstractFields()
 *
 * Auto-generated group annotations
 *
 * @group PyzTest
 * @group Glue
 * @group ProductExperienceManagement
 * @group BackendApi
 * @group Integration
 * @group ProductAbstractWritesBackendApiTest
 * Add your own group annotations below this line
 */
class ProductAbstractWritesBackendApiTest extends AbstractProductExperienceManagementBackendApiTestCase
{
    public function testGivenEveryAbstractFieldOnCreateWhenGetProductThenAllOfThemAreReadBack(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $taxSetUuid = $this->haveTaxSetUuid();
        $categoryUuid = $this->haveCategoryUuid();
        $storeName = $this->getStoreName();

        // Act
        $createdAttributes = $this->haveProductViaApi([
            static::ATTRIBUTE_STORES => [$storeName],
            static::ATTRIBUTE_TAX_SET => ['uuid' => $taxSetUuid],
            static::ATTRIBUTE_CATEGORIES => [['uuid' => $categoryUuid]],
            static::ATTRIBUTE_NEW_FROM => '2027-01-01 00:00:00',
            static::ATTRIBUTE_NEW_TO => '2027-12-31 00:00:00',
        ]);
        $sku = (string)$createdAttributes[static::ATTRIBUTE_SKU];

        $readResponse = $this->handleApiRequest('GET', $this->tester->getProductUrl($sku));

        // Assert
        $this->assertRespondsWithStatus($readResponse, Response::HTTP_OK);
        $readAttributes = $this->getResourceAttributes($readResponse);

        foreach ([$createdAttributes, $readAttributes] as $attributes) {
            $this->assertContains($storeName, $attributes[static::ATTRIBUTE_STORES] ?? []);
            $this->assertSame($taxSetUuid, $attributes[static::ATTRIBUTE_TAX_SET]['uuid'] ?? null);
            $this->assertNotNull($this->findCategory($attributes, $categoryUuid));
            $this->assertSame('2027-01-01 00:00:00', $attributes[static::ATTRIBUTE_NEW_FROM] ?? null);
            $this->assertSame('2027-12-31 00:00:00', $attributes[static::ATTRIBUTE_NEW_TO] ?? null);
        }

        $this->assertNotSame('', (string)($readAttributes[static::ATTRIBUTE_TAX_SET]['name'] ?? ''));
        $category = $this->findCategory($readAttributes, $categoryUuid);
        $this->assertNotSame('', (string)($category['categoryKey'] ?? ''));
        $this->assertIsArray($category['localizedAttributes'] ?? null);
    }

    public function testGivenAPatchOfStoresTaxSetAndDatesWhenPatchProductThenTheyAreEditedAndOmittedCategoriesSurvive(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $storeNames = $this->tester->getExistingStoreNames();
        $firstTaxSetUuid = $this->haveTaxSetUuid();
        $secondTaxSetUuid = $this->haveTaxSetUuid();
        $categoryUuid = $this->haveCategoryUuid();

        $sku = (string)$this->haveProductViaApi([
            static::ATTRIBUTE_STORES => [$storeNames[0]],
            static::ATTRIBUTE_TAX_SET => ['uuid' => $firstTaxSetUuid],
            static::ATTRIBUTE_CATEGORIES => [['uuid' => $categoryUuid]],
        ])[static::ATTRIBUTE_SKU];

        $patchAttributes = [
            static::ATTRIBUTE_STORES => $storeNames,
            static::ATTRIBUTE_TAX_SET => ['uuid' => $secondTaxSetUuid],
            static::ATTRIBUTE_NEW_FROM => '2028-03-01 00:00:00',
            static::ATTRIBUTE_NEW_TO => '2028-04-01 00:00:00',
        ];

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getProductUrl($sku),
            $this->tester->buildProductRequestBody($patchAttributes),
        );
        $readResponse = $this->handleApiRequest('GET', $this->tester->getProductUrl($sku));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertRespondsWithStatus($readResponse, Response::HTTP_OK);

        $readAttributes = $this->getResourceAttributes($readResponse);

        foreach ($storeNames as $storeName) {
            $this->assertContains($storeName, $readAttributes[static::ATTRIBUTE_STORES] ?? []);
        }

        $this->assertSame($secondTaxSetUuid, $this->getResourceAttributes($response)[static::ATTRIBUTE_TAX_SET]['uuid'] ?? null);
        $this->assertSame($secondTaxSetUuid, $readAttributes[static::ATTRIBUTE_TAX_SET]['uuid'] ?? null, 'A taxSet patch replaces the previous one.');
        $this->assertSame($patchAttributes[static::ATTRIBUTE_NEW_FROM], $readAttributes[static::ATTRIBUTE_NEW_FROM] ?? null);
        $this->assertSame($patchAttributes[static::ATTRIBUTE_NEW_TO], $readAttributes[static::ATTRIBUTE_NEW_TO] ?? null);
        $this->assertNotNull(
            $this->findCategory($readAttributes, $categoryUuid),
            'A patch that does not name categories must not clear them.',
        );
    }

    /**
     * @uses \SprykerFeature\Glue\ProductExperienceManagement\Api\Backend\Model\Mapper\ProductAbstractMergeMapper::mergeCategoryUuids()
     */
    public function testGivenExplicitlyEmptyAbstractCollectionsWhenPatchProductThenNothingIsRemoved(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $storeName = $this->getStoreName();
        $categoryUuid = $this->haveCategoryUuid();

        $sku = (string)$this->haveProductViaApi([
            static::ATTRIBUTE_STORES => [$storeName],
            static::ATTRIBUTE_CATEGORIES => [['uuid' => $categoryUuid]],
        ])[static::ATTRIBUTE_SKU];

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getProductUrl($sku),
            $this->tester->buildProductRequestBody([
                static::ATTRIBUTE_CATEGORIES => [],
                static::ATTRIBUTE_STORES => [],
            ]),
        );
        $readResponse = $this->handleApiRequest('GET', $this->tester->getProductUrl($sku));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertRespondsWithStatus($readResponse, Response::HTTP_OK);

        $readAttributes = $this->getResourceAttributes($readResponse);
        $this->assertNotNull($this->findCategory($readAttributes, $categoryUuid), 'An empty categories array must not unassign.');
        $this->assertContains($storeName, $readAttributes[static::ATTRIBUTE_STORES] ?? [], 'An empty stores array must not unassign.');
    }
}
