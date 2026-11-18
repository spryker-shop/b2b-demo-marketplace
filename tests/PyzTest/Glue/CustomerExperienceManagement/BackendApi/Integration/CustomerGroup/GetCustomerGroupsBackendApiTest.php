<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace PyzTest\Glue\CustomerExperienceManagement\BackendApi\Integration\CustomerGroup;

use Generated\Shared\Transfer\CustomerGroupTransfer;
use Symfony\Component\HttpFoundation\Response;

/**
 * Auto-generated group annotations
 *
 * @group PyzTest
 * @group Glue
 * @group CustomerExperienceManagement
 * @group BackendApi
 * @group Integration
 * @group CustomerGroup
 * @group GetCustomerGroupsBackendApiTest
 * Add your own group annotations below this line
 */
class GetCustomerGroupsBackendApiTest extends AbstractCustomerGroupBackendApiTestCase
{
    public function testReturnsSingleCustomerGroupByUuid(): void
    {
        // Arrange
        $customerGroupTransfer = $this->haveCustomerGroup([CustomerGroupTransfer::DESCRIPTION => 'Tier one']);

        // Act
        $response = $this->handleApiRequest(
            'GET',
            $this->tester->getCustomerGroupUrl($customerGroupTransfer->getUuidOrFail()),
        );

        // Assert
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode(), (string)$response->getContent());

        $attributes = $this->getResourceAttributes($response);
        $this->assertSame($customerGroupTransfer->getNameOrFail(), $attributes[static::ATTRIBUTE_NAME]);
        $this->assertSame('Tier one', $attributes[static::ATTRIBUTE_DESCRIPTION]);
    }

    public function testReturnsNotFoundForUnknownUuid(): void
    {
        // Act
        $response = $this->handleApiRequest(
            'GET',
            $this->tester->getCustomerGroupUrl(static::UNKNOWN_WELL_FORMED_UUID),
        );

        // Assert
        $this->assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode(), (string)$response->getContent());
    }

    public function testFindsCustomerGroupByFreeTextSearch(): void
    {
        // Arrange
        $searchTerm = uniqid('needle', false);
        $customerGroupTransfer = $this->haveCustomerGroup([CustomerGroupTransfer::DESCRIPTION => sprintf('Contains %s in the description', $searchTerm)]);
        $this->haveCustomerGroup();

        // Act
        $response = $this->handleApiRequest(
            'GET',
            $this->tester->getCustomerGroupCollectionUrl(['q' => $searchTerm]),
        );

        // Assert
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode(), (string)$response->getContent());
        $this->assertSame([$customerGroupTransfer->getUuidOrFail()], $this->getResourceIds($response));
    }

    public function testFiltersCustomerGroupByNameCaseInsensitively(): void
    {
        // Arrange
        $customerGroupTransfer = $this->haveCustomerGroup();

        // Act
        $response = $this->handleApiRequest(
            'GET',
            $this->tester->getCustomerGroupCollectionUrl([
                'filter[customer-groups.name]' => strtoupper($customerGroupTransfer->getNameOrFail()),
            ]),
        );

        // Assert
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode(), (string)$response->getContent());
        $this->assertSame(
            [$customerGroupTransfer->getUuidOrFail()],
            $this->getResourceIds($response),
            'The name filter is documented as case-insensitive.',
        );
    }

    /**
     * @dataProvider provideCustomerGroupNameSortDirections
     *
     * @param array<int, int> $expectedOrder
     */
    public function testSortsCustomerGroupsByName(string $sort, array $expectedOrder): void
    {
        // Arrange
        $searchTerm = uniqid('sortname', false);
        $customerGroupTransfers = [
            $this->haveCustomerGroup([
                CustomerGroupTransfer::NAME => sprintf('%s-a', $searchTerm),
                CustomerGroupTransfer::DESCRIPTION => $searchTerm,
            ]),
            $this->haveCustomerGroup([
                CustomerGroupTransfer::NAME => sprintf('%s-b', $searchTerm),
                CustomerGroupTransfer::DESCRIPTION => $searchTerm,
            ]),
        ];

        // Act
        $response = $this->handleApiRequest(
            'GET',
            $this->tester->getCustomerGroupCollectionUrl(['q' => $searchTerm, 'sort' => $sort]),
        );

        // Assert
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode(), (string)$response->getContent());
        $this->assertSame(
            $this->mapToUuids($customerGroupTransfers, $expectedOrder),
            $this->getResourceIds($response),
        );
    }

    /**
     * @return array<string, array{string, array<int, int>}>
     */
    public function provideCustomerGroupNameSortDirections(): array
    {
        return [
            'ascending' => ['name', [0, 1]],
            'descending' => ['-name', [1, 0]],
        ];
    }

    /**
     * @dataProvider provideCustomerGroupCreatedAtSortDirections
     *
     * @param array<int, int> $expectedOrder
     */
    public function testSortsCustomerGroupsByCreatedAt(string $sort, array $expectedOrder): void
    {
        // Arrange
        $searchTerm = uniqid('sortcreated', false);
        $customerGroupTransfers = [
            $this->haveCustomerGroup([CustomerGroupTransfer::DESCRIPTION => $searchTerm]),
            $this->haveCustomerGroup([CustomerGroupTransfer::DESCRIPTION => $searchTerm]),
        ];
        $this->tester->setCustomerGroupCreatedAt($customerGroupTransfers[0]->getUuidOrFail(), '2020-01-01 00:00:00');
        $this->tester->setCustomerGroupCreatedAt($customerGroupTransfers[1]->getUuidOrFail(), '2021-01-01 00:00:00');

        // Act
        $response = $this->handleApiRequest(
            'GET',
            $this->tester->getCustomerGroupCollectionUrl(['q' => $searchTerm, 'sort' => $sort]),
        );

        // Assert
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode(), (string)$response->getContent());
        $this->assertSame(
            $this->mapToUuids($customerGroupTransfers, $expectedOrder),
            $this->getResourceIds($response),
        );
    }

    /**
     * @return array<string, array{string, array<int, int>}>
     */
    public function provideCustomerGroupCreatedAtSortDirections(): array
    {
        return [
            'ascending' => ['createdAt', [0, 1]],
            'descending' => ['-createdAt', [1, 0]],
        ];
    }

    public function testAppliesTheSecondSortFieldWhenTheFirstTies(): void
    {
        // Arrange
        $searchTerm = uniqid('sortboth', false);
        $customerGroupTransfers = [
            $this->haveCustomerGroup([
                CustomerGroupTransfer::NAME => sprintf('%s-a', $searchTerm),
                CustomerGroupTransfer::DESCRIPTION => $searchTerm,
            ]),
            $this->haveCustomerGroup([
                CustomerGroupTransfer::NAME => sprintf('%s-b', $searchTerm),
                CustomerGroupTransfer::DESCRIPTION => $searchTerm,
            ]),
        ];

        foreach ($customerGroupTransfers as $customerGroupTransfer) {
            $this->tester->setCustomerGroupCreatedAt($customerGroupTransfer->getUuidOrFail(), '2020-01-01 00:00:00');
        }

        // Act
        $response = $this->handleApiRequest(
            'GET',
            $this->tester->getCustomerGroupCollectionUrl(['q' => $searchTerm, 'sort' => 'createdAt,-name']),
        );

        // Assert
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode(), (string)$response->getContent());
        $this->assertSame(
            $this->mapToUuids($customerGroupTransfers, [1, 0]),
            $this->getResourceIds($response),
        );
    }

    public function testCombinesTheNameFilterWithTheFreeTextSearch(): void
    {
        // Arrange
        $searchTerm = uniqid('bothfilters', false);
        $matchingCustomerGroupTransfer = $this->haveCustomerGroup([
            CustomerGroupTransfer::NAME => sprintf('%s-wanted', $searchTerm),
            CustomerGroupTransfer::DESCRIPTION => $searchTerm,
        ]);
        $this->haveCustomerGroup([
            CustomerGroupTransfer::NAME => sprintf('%s-other', $searchTerm),
            CustomerGroupTransfer::DESCRIPTION => $searchTerm,
        ]);

        // Act
        $response = $this->handleApiRequest(
            'GET',
            $this->tester->getCustomerGroupCollectionUrl([
                'q' => $searchTerm,
                'filter[customer-groups.name]' => $matchingCustomerGroupTransfer->getNameOrFail(),
            ]),
        );

        // Assert
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode(), (string)$response->getContent());
        $this->assertSame(
            [$matchingCustomerGroupTransfer->getUuidOrFail()],
            $this->getResourceIds($response),
            'Both conditions must narrow the result; the free-text match alone returns two groups.',
        );
    }

    public function testReturnsBadRequestForUnsupportedSortField(): void
    {
        // Act
        $response = $this->handleApiRequest(
            'GET',
            $this->tester->getCustomerGroupCollectionUrl(['sort' => 'description']),
        );

        // Assert
        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode(), (string)$response->getContent());
    }

    public function testReportsPaginationInMeta(): void
    {
        // Arrange
        $searchTerm = uniqid('paged', false);
        $this->haveCustomerGroup([CustomerGroupTransfer::DESCRIPTION => $searchTerm]);
        $this->haveCustomerGroup([CustomerGroupTransfer::DESCRIPTION => $searchTerm]);

        // Act
        $response = $this->handleApiRequest(
            'GET',
            $this->tester->getCustomerGroupCollectionUrl(['q' => $searchTerm, 'page' => ['limit' => 1, 'offset' => 0]]),
        );

        // Assert
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode(), (string)$response->getContent());
        $this->assertCount(1, $this->getResourceIds($response));

        $pagination = $this->getMetaPagination($response);
        $this->assertSame(2, $pagination[static::PAGINATION_KEY_NUM_FOUND]);
        $this->assertSame(2, $pagination[static::PAGINATION_KEY_MAX_PAGE]);
    }

    public function testDoesNotExposeAssignmentOnTheGroupResource(): void
    {
        // Arrange
        $customerTransfer = $this->tester->haveCustomer();
        $customerGroupTransfer = $this->haveCustomerGroupWithCustomers([$customerTransfer]);

        // Act
        $response = $this->handleApiRequest(
            'GET',
            $this->tester->getCustomerGroupUrl($customerGroupTransfer->getUuidOrFail()),
        );

        // Assert
        $this->assertArrayNotHasKey(
            static::ATTRIBUTE_CUSTOMER_REFERENCES,
            $this->getResourceAttributes($response),
            'customerReferences is write-only and must never be serialized.',
        );
    }

    /**
     * @param array<int, \Generated\Shared\Transfer\CustomerGroupTransfer> $customerGroupTransfers
     * @param array<int, int> $order
     *
     * @return array<int, string>
     */
    protected function mapToUuids(array $customerGroupTransfers, array $order): array
    {
        $uuids = [];

        foreach ($order as $index) {
            $uuids[] = $customerGroupTransfers[$index]->getUuidOrFail();
        }

        return $uuids;
    }
}
