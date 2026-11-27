<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace PyzTest\Glue\CustomerExperienceManagement\BackendApi\Integration\CustomerGroup;

use Generated\Shared\Transfer\CompanyTransfer;
use Generated\Shared\Transfer\CustomerGroupToCompanyAssignmentTransfer;
use Generated\Shared\Transfer\CustomerGroupToCompanyBusinessUnitAssignmentTransfer;
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
    protected const string FILTER_CUSTOMER_REFERENCE = 'filter[customer-groups.customerReference]';

    protected const string FILTER_COMPANY_UUID = 'filter[customer-groups.companyUuid]';

    protected const string FILTER_COMPANY_BUSINESS_UNIT_UUID = 'filter[customer-groups.companyBusinessUnitUuid]';

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

    public function testFiltersCustomerGroupsByCustomerReference(): void
    {
        // Arrange
        $customerTransfer = $this->tester->haveCustomer();
        $customerGroupTransfer = $this->haveCustomerGroupWithCustomers([$customerTransfer]);
        $this->haveCustomerGroupWithCustomers([$this->tester->haveCustomer()]);

        // Act
        $response = $this->handleApiRequest(
            'GET',
            $this->tester->getCustomerGroupCollectionUrl([
                static::FILTER_CUSTOMER_REFERENCE => $customerTransfer->getCustomerReferenceOrFail(),
            ]),
        );

        // Assert
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode(), (string)$response->getContent());
        $this->assertSame([$customerGroupTransfer->getUuidOrFail()], $this->getResourceIds($response));
    }

    public function testFiltersCustomerGroupsByCompanyUuid(): void
    {
        // Arrange
        $companyTransfer = $this->tester->haveCompany();
        $customerGroupTransfer = $this->haveCustomerGroupWithCompanies([$companyTransfer]);
        $this->haveCustomerGroupWithCompanies([$this->tester->haveCompany()]);

        // Act
        $response = $this->handleApiRequest(
            'GET',
            $this->tester->getCustomerGroupCollectionUrl([static::FILTER_COMPANY_UUID => $companyTransfer->getUuidOrFail()]),
        );

        // Assert
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode(), (string)$response->getContent());
        $this->assertSame([$customerGroupTransfer->getUuidOrFail()], $this->getResourceIds($response));
    }

    public function testCompanyUuidFilterDoesNotReturnTheGroupsOfItsBusinessUnits(): void
    {
        // Arrange
        $companyTransfer = $this->tester->haveCompany();
        $this->haveCustomerGroupWithCompanyBusinessUnits([$this->haveCompanyBusinessUnitOf($companyTransfer)]);

        // Act
        $response = $this->handleApiRequest(
            'GET',
            $this->tester->getCustomerGroupCollectionUrl([static::FILTER_COMPANY_UUID => $companyTransfer->getUuidOrFail()]),
        );

        // Assert
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode(), (string)$response->getContent());
        $this->assertSame([], $this->getResourceIds($response));
    }

    public function testFiltersCustomerGroupsByCompanyBusinessUnitUuid(): void
    {
        // Arrange
        $companyBusinessUnitTransfer = $this->haveCompanyBusinessUnitOf($this->tester->haveCompany());
        $customerGroupTransfer = $this->haveCustomerGroupWithCompanyBusinessUnits([$companyBusinessUnitTransfer]);
        $this->haveCustomerGroupWithCompanyBusinessUnits([$this->haveCompanyBusinessUnitOf($this->tester->haveCompany())]);

        // Act
        $response = $this->handleApiRequest(
            'GET',
            $this->tester->getCustomerGroupCollectionUrl([
                static::FILTER_COMPANY_BUSINESS_UNIT_UUID => $companyBusinessUnitTransfer->getUuidOrFail(),
            ]),
        );

        // Assert
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode(), (string)$response->getContent());
        $this->assertSame([$customerGroupTransfer->getUuidOrFail()], $this->getResourceIds($response));
    }

    public function testReturnsTheGroupsOfAnyOfSeveralCompanyUuids(): void
    {
        // Arrange
        $firstCompanyTransfer = $this->tester->haveCompany();
        $secondCompanyTransfer = $this->tester->haveCompany();
        $customerGroupUuids = [
            $this->haveCustomerGroupWithCompanies([$firstCompanyTransfer])->getUuidOrFail(),
            $this->haveCustomerGroupWithCompanies([$secondCompanyTransfer])->getUuidOrFail(),
            $this->haveCustomerGroupWithCompanies([$firstCompanyTransfer, $secondCompanyTransfer])->getUuidOrFail(),
        ];
        $this->haveCustomerGroupWithCompanies([$this->tester->haveCompany()]);

        // Act
        $response = $this->handleApiRequest(
            'GET',
            $this->tester->getCustomerGroupCollectionUrl([
                static::FILTER_COMPANY_UUID => [$firstCompanyTransfer->getUuidOrFail(), $secondCompanyTransfer->getUuidOrFail()],
            ]),
        );

        // Assert
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode(), (string)$response->getContent());
        $resourceIds = $this->getResourceIds($response);
        sort($resourceIds);
        sort($customerGroupUuids);
        $this->assertSame($customerGroupUuids, $resourceIds, 'A group assigned to both companies must be returned once.');
        $this->assertSame(3, $this->getMetaPagination($response)[static::PAGINATION_KEY_NUM_FOUND]);
    }

    public function testPagesThroughTheGroupsOfSeveralCompanyUuidsWithoutRepeatingASharedGroup(): void
    {
        // Arrange
        $firstCompanyTransfer = $this->tester->haveCompany();
        $secondCompanyTransfer = $this->tester->haveCompany();
        $namePrefix = uniqid('paged-', false);
        $expectedCustomerGroupUuids = [
            $this->haveCustomerGroup([
                CustomerGroupTransfer::NAME => sprintf('%s-a', $namePrefix),
                CustomerGroupTransfer::COMPANY_ASSIGNMENT => [
                    CustomerGroupToCompanyAssignmentTransfer::IDS_COMPANY_TO_ASSIGN => [
                        $firstCompanyTransfer->getIdCompanyOrFail(),
                        $secondCompanyTransfer->getIdCompanyOrFail(),
                    ],
                    CustomerGroupToCompanyAssignmentTransfer::IDS_COMPANY_TO_DE_ASSIGN => [],
                ],
            ])->getUuidOrFail(),
            $this->haveCustomerGroup([
                CustomerGroupTransfer::NAME => sprintf('%s-b', $namePrefix),
                CustomerGroupTransfer::COMPANY_ASSIGNMENT => $this->createCompanyAssignment($firstCompanyTransfer),
            ])->getUuidOrFail(),
            $this->haveCustomerGroup([
                CustomerGroupTransfer::NAME => sprintf('%s-c', $namePrefix),
                CustomerGroupTransfer::COMPANY_ASSIGNMENT => $this->createCompanyAssignment($secondCompanyTransfer),
            ])->getUuidOrFail(),
        ];
        $this->haveCustomerGroupWithCompanies([$this->tester->haveCompany()]);

        // Act
        $responses = [];

        foreach ([0, 1, 2] as $offset) {
            $responses[] = $this->handleApiRequest(
                'GET',
                $this->tester->getCustomerGroupCollectionUrl([
                    static::FILTER_COMPANY_UUID => [$firstCompanyTransfer->getUuidOrFail(), $secondCompanyTransfer->getUuidOrFail()],
                    'sort' => 'name',
                    'page' => ['limit' => 1, 'offset' => $offset],
                ]),
            );
        }

        // Assert
        $pagedCustomerGroupUuids = [];

        foreach ($responses as $response) {
            $this->assertSame(Response::HTTP_OK, $response->getStatusCode(), (string)$response->getContent());
            $pagination = $this->getMetaPagination($response);
            $this->assertSame(3, $pagination[static::PAGINATION_KEY_NUM_FOUND]);
            $this->assertSame(3, $pagination[static::PAGINATION_KEY_MAX_PAGE]);
            $pagedCustomerGroupUuids = array_merge($pagedCustomerGroupUuids, $this->getResourceIds($response));
        }

        $this->assertSame($expectedCustomerGroupUuids, $pagedCustomerGroupUuids);
    }

    public function testCombinesTheCompanyAndBusinessUnitFiltersWithAnd(): void
    {
        // Arrange
        $companyTransfer = $this->tester->haveCompany();
        $companyBusinessUnitTransfer = $this->haveCompanyBusinessUnitOf($companyTransfer);
        $customerGroupOfBothTransfer = $this->haveCustomerGroup([
            CustomerGroupTransfer::COMPANY_ASSIGNMENT => $this->createCompanyAssignment($companyTransfer),
            CustomerGroupTransfer::COMPANY_BUSINESS_UNIT_ASSIGNMENT => [
                CustomerGroupToCompanyBusinessUnitAssignmentTransfer::IDS_COMPANY_BUSINESS_UNIT_TO_ASSIGN => [
                    $companyBusinessUnitTransfer->getIdCompanyBusinessUnitOrFail(),
                ],
                CustomerGroupToCompanyBusinessUnitAssignmentTransfer::IDS_COMPANY_BUSINESS_UNIT_TO_DE_ASSIGN => [],
            ],
        ]);
        $this->haveCustomerGroupWithCompanies([$companyTransfer]);
        $this->haveCustomerGroupWithCompanyBusinessUnits([$companyBusinessUnitTransfer]);

        // Act
        $response = $this->handleApiRequest(
            'GET',
            $this->tester->getCustomerGroupCollectionUrl([
                static::FILTER_COMPANY_UUID => $companyTransfer->getUuidOrFail(),
                static::FILTER_COMPANY_BUSINESS_UNIT_UUID => $companyBusinessUnitTransfer->getUuidOrFail(),
            ]),
        );

        // Assert
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode(), (string)$response->getContent());
        $this->assertSame([$customerGroupOfBothTransfer->getUuidOrFail()], $this->getResourceIds($response));
    }

    public function testCombinesTheCustomerReferenceAndCompanyFiltersWithAnd(): void
    {
        // Arrange
        $companyTransfer = $this->tester->haveCompany();
        $customerTransfer = $this->tester->haveCustomer();
        $customerGroupOfBothTransfer = $this->haveCustomerGroupWithCustomers(
            [$customerTransfer],
            [CustomerGroupTransfer::COMPANY_ASSIGNMENT => $this->createCompanyAssignment($companyTransfer)],
        );
        $this->haveCustomerGroupWithCustomers([$customerTransfer]);
        $this->haveCustomerGroupWithCompanies([$companyTransfer]);

        // Act
        $response = $this->handleApiRequest(
            'GET',
            $this->tester->getCustomerGroupCollectionUrl([
                static::FILTER_CUSTOMER_REFERENCE => $customerTransfer->getCustomerReferenceOrFail(),
                static::FILTER_COMPANY_UUID => $companyTransfer->getUuidOrFail(),
            ]),
        );

        // Assert
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode(), (string)$response->getContent());
        $this->assertSame([$customerGroupOfBothTransfer->getUuidOrFail()], $this->getResourceIds($response));
    }

    public function testReturnsAnEmptyCollectionForAnUnknownCompanyUuid(): void
    {
        // Arrange
        $this->haveCustomerGroupWithCompanies([$this->tester->haveCompany()]);

        // Act
        $response = $this->handleApiRequest(
            'GET',
            $this->tester->getCustomerGroupCollectionUrl([static::FILTER_COMPANY_UUID => static::UNKNOWN_WELL_FORMED_UUID]),
        );

        // Assert
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode(), (string)$response->getContent());
        $this->assertSame([], $this->getResourceIds($response));
        $this->assertSame(0, $this->getMetaPagination($response)[static::PAGINATION_KEY_NUM_FOUND]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function createCompanyAssignment(CompanyTransfer $companyTransfer): array
    {
        return [
            CustomerGroupToCompanyAssignmentTransfer::IDS_COMPANY_TO_ASSIGN => [$companyTransfer->getIdCompanyOrFail()],
            CustomerGroupToCompanyAssignmentTransfer::IDS_COMPANY_TO_DE_ASSIGN => [],
        ];
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
