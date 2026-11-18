<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace PyzTest\Glue\CustomerExperienceManagement\BackendApi\Integration\CustomerAccess;

use PyzTest\Glue\CustomerExperienceManagement\AbstractCustomerExperienceManagementBackendApiTestCase;
use Spryker\Shared\CustomerAccess\CustomerAccessConfig;
use Symfony\Component\HttpFoundation\Response;

/**
 * Auto-generated group annotations
 *
 * @group PyzTest
 * @group Glue
 * @group CustomerExperienceManagement
 * @group BackendApi
 * @group Integration
 * @group CustomerAccess
 * @group CustomerAccessBackendApiTest
 * Add your own group annotations below this line
 */
class CustomerAccessBackendApiTest extends AbstractCustomerExperienceManagementBackendApiTestCase
{
    protected const string ATTRIBUTE_CONTENT_TYPE_ACCESS = 'contentTypeAccess';

    protected const string FIELD_CONTENT_TYPE = 'contentType';

    protected const string FIELD_IS_RESTRICTED = 'isRestricted';

    protected const string UNKNOWN_CONTENT_TYPE = 'cxm-not-a-configured-content-type';

    protected function setUp(): void
    {
        parent::setUp();

        $this->tester->actingAsUser();
    }

    public function testReturnsEveryConfiguredContentType(): void
    {
        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getCustomerAccessUrl());

        // Assert
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode(), (string)$response->getContent());

        $contentTypes = $this->getContentTypeAccessIndexedByContentType($response);
        $this->assertArrayHasKey(CustomerAccessConfig::CONTENT_TYPE_PRICE, $contentTypes);
        $this->assertArrayHasKey(CustomerAccessConfig::CONTENT_TYPE_WISHLIST, $contentTypes);
    }

    public function testUpdatesOnlyTheSentContentTypeAndKeepsTheRest(): void
    {
        // Arrange
        $stateBefore = $this->tester->getUnauthenticatedCustomerAccessState();
        $targetIsRestricted = !$stateBefore[CustomerAccessConfig::CONTENT_TYPE_PRICE];

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getCustomerAccessUrl(),
            $this->tester->buildCustomerAccessRequestBody([
                static::ATTRIBUTE_CONTENT_TYPE_ACCESS => [
                    [
                        static::FIELD_CONTENT_TYPE => CustomerAccessConfig::CONTENT_TYPE_PRICE,
                        static::FIELD_IS_RESTRICTED => $targetIsRestricted,
                    ],
                ],
            ]),
        );

        // Assert
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode(), (string)$response->getContent());

        $stateAfter = $this->tester->getUnauthenticatedCustomerAccessState();
        $this->assertSame($targetIsRestricted, $stateAfter[CustomerAccessConfig::CONTENT_TYPE_PRICE]);

        unset($stateBefore[CustomerAccessConfig::CONTENT_TYPE_PRICE], $stateAfter[CustomerAccessConfig::CONTENT_TYPE_PRICE]);
        $this->assertSame(
            $stateBefore,
            $stateAfter,
            'A content type left out of the payload must keep its current flag.',
        );
    }

    public function testResponseReturnsTheFullCurrentSetting(): void
    {
        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getCustomerAccessUrl(),
            $this->tester->buildCustomerAccessRequestBody([
                static::ATTRIBUTE_CONTENT_TYPE_ACCESS => [
                    [
                        static::FIELD_CONTENT_TYPE => CustomerAccessConfig::CONTENT_TYPE_PRICE,
                        static::FIELD_IS_RESTRICTED => true,
                    ],
                ],
            ]),
        );

        // Assert
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode(), (string)$response->getContent());
        $this->assertSame(
            $this->tester->getUnauthenticatedCustomerAccessState(),
            $this->getContentTypeAccessIndexedByContentType($response),
            'The response must describe every configured content type, not just the changed ones.',
        );
    }

    public function testRejectsUnknownContentTypeAndCreatesNoRow(): void
    {
        // Arrange
        $rowCountBefore = $this->tester->countUnauthenticatedCustomerAccessRows();
        $stateBefore = $this->tester->getUnauthenticatedCustomerAccessState();

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getCustomerAccessUrl(),
            $this->tester->buildCustomerAccessRequestBody([
                static::ATTRIBUTE_CONTENT_TYPE_ACCESS => [
                    [
                        static::FIELD_CONTENT_TYPE => static::UNKNOWN_CONTENT_TYPE,
                        static::FIELD_IS_RESTRICTED => true,
                    ],
                ],
            ]),
        );

        // Assert
        $this->assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $response->getStatusCode(), (string)$response->getContent());
        $this->assertSame(
            $rowCountBefore,
            $this->tester->countUnauthenticatedCustomerAccessRows(),
            'An unknown content type must be rejected, never inserted as a new row.',
        );
        $this->assertSame(
            $stateBefore,
            $this->tester->getUnauthenticatedCustomerAccessState(),
            'Nothing may be saved when the request is rejected.',
        );
    }

    public function testRejectsDuplicateContentType(): void
    {
        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getCustomerAccessUrl(),
            $this->tester->buildCustomerAccessRequestBody([
                static::ATTRIBUTE_CONTENT_TYPE_ACCESS => [
                    [
                        static::FIELD_CONTENT_TYPE => CustomerAccessConfig::CONTENT_TYPE_PRICE,
                        static::FIELD_IS_RESTRICTED => true,
                    ],
                    [
                        static::FIELD_CONTENT_TYPE => CustomerAccessConfig::CONTENT_TYPE_PRICE,
                        static::FIELD_IS_RESTRICTED => false,
                    ],
                ],
            ]),
        );

        // Assert
        $this->assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $response->getStatusCode(), (string)$response->getContent());
    }

    public function testRejectsEntryWithoutIsRestricted(): void
    {
        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getCustomerAccessUrl(),
            $this->tester->buildCustomerAccessRequestBody([
                static::ATTRIBUTE_CONTENT_TYPE_ACCESS => [
                    [static::FIELD_CONTENT_TYPE => CustomerAccessConfig::CONTENT_TYPE_PRICE],
                ],
            ]),
        );

        // Assert
        $this->assertSame(
            Response::HTTP_UNPROCESSABLE_ENTITY,
            $response->getStatusCode(),
            'isRestricted has no default; omitting it is an error, not a way to keep the current value.',
        );
    }

    /**
     * @return array<string, bool>
     */
    protected function getContentTypeAccessIndexedByContentType(Response $response): array
    {
        $contentTypes = [];

        foreach ($this->getResourceAttributes($response)[static::ATTRIBUTE_CONTENT_TYPE_ACCESS] as $entry) {
            $contentTypes[$entry[static::FIELD_CONTENT_TYPE]] = $entry[static::FIELD_IS_RESTRICTED];
        }

        return $contentTypes;
    }
}
