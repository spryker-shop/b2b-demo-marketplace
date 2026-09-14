<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace PyzTest\Glue\CustomerExperienceManagement\BackendApi\Integration;

use PyzTest\Glue\CustomerExperienceManagement\AbstractCustomerExperienceManagementBackendApiTestCase;
use SprykerFeature\Glue\CustomerExperienceManagement\CustomerExperienceManagementConfig;
use Symfony\Component\HttpFoundation\Response;

/**
 * `GET /customers` over a booted GLUE_BACKEND kernel: free-text search, sorting, pagination,
 * attribute filtering, and both authorization layers.
 *
 * Ported from GetCollectionCustomersBackendJsonApiCest. The 403 case is new — the docker lane could
 * not reach the ACL refusal, because it authenticated a real operator whom the ACL always granted.
 *
 * Auto-generated group annotations
 *
 * @group PyzTest
 * @group Glue
 * @group CustomerExperienceManagement
 * @group BackendApi
 * @group Integration
 * @group GetCollectionCustomersBackendApiTest
 * Add your own group annotations below this line
 */
class GetCollectionCustomersBackendApiTest extends AbstractCustomerExperienceManagementBackendApiTestCase
{
    /**
     * The surrogate key must never reach a client: customers are addressed by reference alone.
     */
    protected const string UNSUPPORTED_SORT_FIELD = 'idCustomer';

    protected const int LISTED_CUSTOMER_COUNT = 2;

    public function testGivenAnAuthenticatedOperatorWhenSearchByFreeTextThenOnlyTheMatchingCustomersAreReturned(): void
    {
        // Arrange
        [$firstCustomerTransfer, $secondCustomerTransfer] = $this->tester->haveTwoListedCustomers();
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getCustomerCollectionUrl([
            'q' => $firstCustomerTransfer->getLastNameOrFail(),
        ]));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);

        $references = $this->getResourceIds($response);
        $this->assertCount(
            static::LISTED_CUSTOMER_COUNT,
            $references,
            'The free-text search isolates exactly the two customers this test created.',
        );
        $this->assertContains($firstCustomerTransfer->getCustomerReferenceOrFail(), $references);
        $this->assertContains($secondCustomerTransfer->getCustomerReferenceOrFail(), $references);
    }

    public function testGivenAnAuthenticatedOperatorWhenSearchCustomersThenTheInternalCustomerIdIsNotExposed(): void
    {
        // Arrange
        [$firstCustomerTransfer] = $this->tester->haveTwoListedCustomers();
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getCustomerCollectionUrl([
            'q' => $firstCustomerTransfer->getLastNameOrFail(),
        ]));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);

        $this->assertContains(
            $firstCustomerTransfer->getCustomerReferenceOrFail(),
            $this->getResourceIds($response),
        );
        $this->assertStringNotContainsString(
            static::UNSUPPORTED_SORT_FIELD,
            (string)$response->getContent(),
            'The surrogate key stays internal — customers are addressed by reference alone.',
        );
    }

    public function testGivenADescendingSortWhenSearchCustomersThenTheOrderIsReversed(): void
    {
        // Arrange
        [$firstCustomerTransfer, $secondCustomerTransfer] = $this->tester->haveTwoListedCustomers();
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getCustomerCollectionUrl([
            'q' => $firstCustomerTransfer->getLastNameOrFail(),
            'sort' => '-firstName',
        ]));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertSame(
            $secondCustomerTransfer->getCustomerReferenceOrFail(),
            $this->getResourceIds($response)[0] ?? null,
            'Descending sort puts the alphabetically last first name first.',
        );
    }

    public function testGivenAPageLimitWhenSearchCustomersThenOnePageAndItsPaginationMetadataAreReturned(): void
    {
        // Arrange
        [$firstCustomerTransfer] = $this->tester->haveTwoListedCustomers();
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getCustomerCollectionUrl([
            'q' => $firstCustomerTransfer->getLastNameOrFail(),
            'page' => ['limit' => 1],
            'sort' => 'firstName',
        ]));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);

        $payload = $this->decodeJsonApi($response);
        $this->assertCount(
            1,
            $payload[static::JSON_API_KEY_DATA],
            'A limit of one truncates the page to a single customer.',
        );

        $this->assertNoPaginationInsideMembers($response);

        $pagination = $this->getMetaPagination($response);
        $this->assertSame(
            static::LISTED_CUSTOMER_COUNT,
            $pagination[static::PAGINATION_KEY_NUM_FOUND],
            'The pagination metadata reports both matches.',
        );
        $this->assertSame(
            static::LISTED_CUSTOMER_COUNT,
            $pagination[static::PAGINATION_KEY_MAX_PAGE],
            'Two matches at one per page is two pages.',
        );
        $this->assertNotEmpty(
            $payload[static::JSON_API_KEY_LINKS][static::LINK_NEXT] ?? null,
            'The second page is reachable from the response links.',
        );
    }

    public function testGivenAnEmailFilterWhenSearchCustomersThenOnlyThatCustomerIsReturned(): void
    {
        // Arrange
        [$firstCustomerTransfer] = $this->tester->haveTwoListedCustomers();
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getCustomerCollectionUrl([
            'filter' => ['customers.email' => $firstCustomerTransfer->getEmailOrFail()],
        ]));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertSame(
            [$firstCustomerTransfer->getCustomerReferenceOrFail()],
            $this->getResourceIds($response),
        );
    }

    public function testGivenAnUnsupportedSortFieldWhenSearchCustomersThenItRespondsBadRequest(): void
    {
        // Arrange
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getCustomerCollectionUrl([
            'sort' => static::UNSUPPORTED_SORT_FIELD,
        ]));

        // Assert — the field reaches an SQL ORDER BY, so anything off the allow list is refused.
        $this->assertRespondsWithErrorCode(
            $response,
            Response::HTTP_BAD_REQUEST,
            CustomerExperienceManagementConfig::RESPONSE_CODE_INVALID_SORT_FIELD,
        );
    }

    public function testGivenNoAuthenticationWhenSearchCustomersThenItRespondsUnauthorized(): void
    {
        // Act — intentionally unauthenticated.
        $response = $this->handleApiRequest('GET', $this->tester->getCustomerCollectionUrl());

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_UNAUTHORIZED);
    }

    public function testGivenAnInvalidTokenWhenSearchCustomersThenItRespondsUnauthorized(): void
    {
        // Arrange
        $this->tester->actingWithInvalidToken();

        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getCustomerCollectionUrl());

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_UNAUTHORIZED);
    }

    public function testGivenAnOperatorTheAclRefusesWhenSearchCustomersThenItRespondsForbidden(): void
    {
        // Arrange
        $this->tester->actingAsUserWithoutAclAccess();

        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getCustomerCollectionUrl());

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_FORBIDDEN);
    }
}
