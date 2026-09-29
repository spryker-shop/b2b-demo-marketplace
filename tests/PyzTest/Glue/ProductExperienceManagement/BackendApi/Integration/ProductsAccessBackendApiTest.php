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
 * Authentication, authorization and request-envelope rejections on `/products`.
 *
 * Auto-generated group annotations
 *
 * @group PyzTest
 * @group Glue
 * @group ProductExperienceManagement
 * @group BackendApi
 * @group Integration
 * @group ProductsAccessBackendApiTest
 * Add your own group annotations below this line
 */
class ProductsAccessBackendApiTest extends AbstractProductExperienceManagementBackendApiTestCase
{
    public function testGivenNoAuthenticationWhenCallingAnyOperationThenUnauthorizedIsReturned(): void
    {
        // Arrange
        $body = $this->tester->buildProductRequestBody([static::ATTRIBUTE_IS_ACTIVE => false]);

        // Act & Assert
        $this->assertRespondsWithStatus(
            $this->handleApiRequest('GET', $this->tester->getProductUrl(static::UNKNOWN_SKU)),
            Response::HTTP_UNAUTHORIZED,
        );
        $this->assertRespondsWithStatus(
            $this->handleApiRequest('GET', $this->tester->getProductCollectionUrl()),
            Response::HTTP_UNAUTHORIZED,
        );
        $this->assertRespondsWithStatus(
            $this->handleApiRequest('POST', $this->tester->getProductCollectionUrl(), $body),
            Response::HTTP_UNAUTHORIZED,
        );
        $this->assertRespondsWithStatus(
            $this->handleApiRequest('PATCH', $this->tester->getProductUrl(static::UNKNOWN_SKU), $body),
            Response::HTTP_UNAUTHORIZED,
        );
    }

    public function testGivenAnInvalidTokenWhenGetCollectionThenUnauthorizedIsReturned(): void
    {
        // Arrange
        $this->tester->actingWithInvalidToken();

        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getProductCollectionUrl());

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_UNAUTHORIZED);
    }

    public function testGivenAnOperatorWithoutAclAccessWhenGetCollectionThenForbiddenIsReturned(): void
    {
        // Arrange
        $this->tester->actingAsUserWithoutAclAccess();

        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getProductCollectionUrl());

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_FORBIDDEN);
    }

    /**
     * @uses \Spryker\ApiPlatform\EventSubscriber\GlueApiExceptionSubscriber
     */
    public function testGivenAMalformedRequestEnvelopeWhenWritingThenBadRequestIsReturned(): void
    {
        // Arrange
        $this->tester->actingAsUser();

        // Act & Assert
        $this->assertRespondsWithStatus(
            $this->handleApiRequest(
                'POST',
                $this->tester->getProductCollectionUrl(),
                $this->tester->buildRawBody([static::ATTRIBUTE_SKU => $this->tester->generateSku('pxm-flat-')]),
            ),
            Response::HTTP_BAD_REQUEST,
        );

        // Act & Assert
        $this->assertRespondsWithStatus(
            $this->handleApiRequest(
                'PATCH',
                $this->tester->getProductCollectionTrailingSlashUrl(),
                $this->tester->buildProductRequestBody([static::ATTRIBUTE_IS_ACTIVE => false]),
            ),
            Response::HTTP_BAD_REQUEST,
        );
    }
}
