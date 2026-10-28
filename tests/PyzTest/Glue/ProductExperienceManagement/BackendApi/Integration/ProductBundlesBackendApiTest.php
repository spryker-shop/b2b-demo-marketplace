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
 * Bundle assignment through `productBundle` on `POST /products`.
 *
 * Auto-generated group annotations
 *
 * @group PyzTest
 * @group Glue
 * @group ProductExperienceManagement
 * @group BackendApi
 * @group Integration
 * @group ProductBundlesBackendApiTest
 * Add your own group annotations below this line
 */
class ProductBundlesBackendApiTest extends AbstractProductExperienceManagementBackendApiTestCase
{
    protected const int BUNDLE_QUANTITY = 2;

    protected const int COMPONENT_STOCK_QUANTITY = 100;

    public function testGivenABundledProductWhenCreateBundleThenTheAssignmentIsEchoedAndPersistedAndTheComponentStaysStandalone(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $componentSku = $this->tester->haveFullProduct()->getSkuOrFail();
        $this->tester->haveStockProduct([
            'sku' => $componentSku,
            'stockType' => $this->getWarehouseName(),
            'quantity' => static::COMPONENT_STOCK_QUANTITY,
            'isNeverOutOfStock' => false,
        ]);

        // Act
        $componentResponse = $this->handleApiRequest('GET', $this->tester->getProductUrl($componentSku));

        // Assert
        $this->assertRespondsWithStatus($componentResponse, Response::HTTP_OK);

        $componentAttributes = $this->getResourceAttributes($componentResponse);
        $this->assertSame([], $componentAttributes[static::ATTRIBUTE_PRODUCT_BUNDLE] ?? null, 'A bundle component is not a bundle.');
        $this->assertSame(
            static::COMPONENT_STOCK_QUANTITY,
            $this->findStock($componentAttributes, $this->getWarehouseName())['quantity'] ?? null,
        );

        // Act
        $bundleSku = $this->tester->generateSku('pxm-bundle-');
        $createResponse = $this->handleApiRequest(
            'POST',
            $this->tester->getProductCollectionUrl(),
            $this->tester->buildProductRequestBody($this->buildValidProductAttributes([
                static::ATTRIBUTE_SKU => $bundleSku,
                static::ATTRIBUTE_STOCKS => null,
                static::ATTRIBUTE_PRODUCT_BUNDLE => [['sku' => $componentSku, 'quantity' => static::BUNDLE_QUANTITY]],
            ])),
        );

        // Assert
        $this->assertRespondsWithStatus($createResponse, Response::HTTP_CREATED);
        $this->assertSame(
            static::BUNDLE_QUANTITY,
            $this->findBundledProduct($this->getResourceAttributes($createResponse), $componentSku)['quantity'] ?? null,
        );

        $readResponse = $this->handleApiRequest('GET', $this->tester->getProductUrl($bundleSku));
        $this->assertRespondsWithStatus($readResponse, Response::HTTP_OK);
        $this->assertSame(
            static::BUNDLE_QUANTITY,
            $this->findBundledProduct($this->getResourceAttributes($readResponse), $componentSku)['quantity'] ?? null,
            'The assignment must survive a read, not only be echoed by the write.',
        );
    }

    /**
     * `productBundle` cannot share a request with a `stocks` write: `saveBundledProducts()` ends by
     * recomputing the bundle's own stock from its components.
     *
     * @uses \Spryker\Zed\ProductBundle\Business\ProductBundle\ProductBundleWriter::saveBundledProducts()
     */
    public function testGivenAnExistingProductWhenPatchAddsABundledProductThenTheAssignmentIsPersisted(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $componentSku = $this->tester->haveFullProduct()->getSkuOrFail();
        $this->tester->haveStockProduct([
            'sku' => $componentSku,
            'stockType' => $this->getWarehouseName(),
            'quantity' => static::COMPONENT_STOCK_QUANTITY,
            'isNeverOutOfStock' => false,
        ]);
        $sku = (string)$this->haveProductViaApi()[static::ATTRIBUTE_SKU];

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getProductUrl($sku),
            $this->tester->buildProductRequestBody([
                static::ATTRIBUTE_PRODUCT_BUNDLE => [['sku' => $componentSku, 'quantity' => static::BUNDLE_QUANTITY]],
            ]),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);

        $bundledProduct = $this->findBundledProduct($this->getResourceAttributes($response), $componentSku);
        $this->assertNotNull($bundledProduct, 'A bundle assignment must be writable on PATCH, not only on POST.');
        $this->assertSame(static::BUNDLE_QUANTITY, $bundledProduct['quantity'] ?? null);

        $readResponse = $this->handleApiRequest('GET', $this->tester->getProductUrl($sku));
        $this->assertRespondsWithStatus($readResponse, Response::HTTP_OK);
        $this->assertSame(
            static::BUNDLE_QUANTITY,
            $this->findBundledProduct($this->getResourceAttributes($readResponse), $componentSku)['quantity'] ?? null,
        );
    }
}
