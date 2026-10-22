<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace PyzTest\Glue\CustomerExperienceManagement\BackendApi\Integration\CustomerGroup;

use SprykerFeature\Glue\CustomerExperienceManagement\CustomerExperienceManagementConfig;
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
 * @group CustomerGroupCompanyBusinessUnitsBackendApiTest
 * Add your own group annotations below this line
 */
class CustomerGroupCompanyBusinessUnitsBackendApiTest extends AbstractCustomerGroupBackendApiTestCase
{
    protected const string ATTRIBUTE_COMPANY_BUSINESS_UNIT_UUIDS = 'companyBusinessUnitUuids';

    protected const string ATTRIBUTE_COMPANY_UUIDS = 'companyUuids';

    protected const string ATTRIBUTE_ALL = 'all';

    protected const string RELATIONSHIP_COMPANY_BUSINESS_UNITS = 'company-business-units';

    protected const string RELATIONSHIP_COMPANIES = 'companies';

    protected const string FILTER_COMPANY_UUID = 'customer-group-company-business-units.companyUuid';

    protected const string FILTER_NAME = 'customer-group-company-business-units.name';

    protected const string NAME_ALPHA = 'Alpha Warehouse';

    protected const string NAME_BRAVO = 'Bravo Office';

    public function testListsTheBusinessUnitsOfTheGroupWithTheUuidAndNameOfTheUnitAndOfItsCompany(): void
    {
        // Arrange
        $firstCompanyTransfer = $this->tester->haveCompany();
        $secondCompanyTransfer = $this->tester->haveCompany();
        $firstCompanyBusinessUnitTransfer = $this->haveCompanyBusinessUnitOf($firstCompanyTransfer);
        $secondCompanyBusinessUnitTransfer = $this->haveCompanyBusinessUnitOf($secondCompanyTransfer);
        $uuid = $this->haveCustomerGroupWithCompanyBusinessUnits([$firstCompanyBusinessUnitTransfer, $secondCompanyBusinessUnitTransfer])
            ->getUuidOrFail();

        // Act
        $response = $this->tester->handleApiRequest('GET', $this->tester->getCustomerGroupCompanyBusinessUnitCollectionUrl($uuid));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertSame([
            $firstCompanyBusinessUnitTransfer->getUuidOrFail() => [
                static::ATTRIBUTE_COMPANY_BUSINESS_UNIT_UUID => $firstCompanyBusinessUnitTransfer->getUuidOrFail(),
                static::ATTRIBUTE_COMPANY_BUSINESS_UNIT_NAME => $firstCompanyBusinessUnitTransfer->getNameOrFail(),
                static::ATTRIBUTE_COMPANY_UUID => $firstCompanyTransfer->getUuidOrFail(),
                static::ATTRIBUTE_COMPANY_NAME => $firstCompanyTransfer->getNameOrFail(),
            ],
            $secondCompanyBusinessUnitTransfer->getUuidOrFail() => [
                static::ATTRIBUTE_COMPANY_BUSINESS_UNIT_UUID => $secondCompanyBusinessUnitTransfer->getUuidOrFail(),
                static::ATTRIBUTE_COMPANY_BUSINESS_UNIT_NAME => $secondCompanyBusinessUnitTransfer->getNameOrFail(),
                static::ATTRIBUTE_COMPANY_UUID => $secondCompanyTransfer->getUuidOrFail(),
                static::ATTRIBUTE_COMPANY_NAME => $secondCompanyTransfer->getNameOrFail(),
            ],
        ], array_column(
            $this->decodeJsonApi($response)[static::JSON_API_KEY_DATA],
            static::JSON_API_KEY_ATTRIBUTES,
            static::JSON_API_KEY_ID,
        ));
    }

    public function testFiltersTheBusinessUnitsByCompany(): void
    {
        // Arrange
        $firstCompanyTransfer = $this->tester->haveCompany();
        $firstCompanyBusinessUnitTransfer = $this->haveCompanyBusinessUnitOf($firstCompanyTransfer);
        $secondCompanyBusinessUnitTransfer = $this->haveCompanyBusinessUnitOf($firstCompanyTransfer);
        $uuid = $this->haveCustomerGroupWithCompanyBusinessUnits([
            $firstCompanyBusinessUnitTransfer,
            $secondCompanyBusinessUnitTransfer,
            $this->haveCompanyBusinessUnitOf($this->tester->haveCompany()),
        ])->getUuidOrFail();

        // Act
        $response = $this->tester->handleApiRequest(
            'GET',
            $this->tester->getCustomerGroupCompanyBusinessUnitCollectionUrl($uuid, [
                'filter' => [static::FILTER_COMPANY_UUID => $firstCompanyTransfer->getUuidOrFail()],
            ]),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertSame(
            [$firstCompanyBusinessUnitTransfer->getUuidOrFail(), $secondCompanyBusinessUnitTransfer->getUuidOrFail()],
            $this->getResourceIds($response),
        );
    }

    /**
     * @dataProvider unsupportedFilterDataProvider
     *
     * @param array<string, mixed> $filter
     */
    public function testRejectsAFilterOtherThanASingleCompanyUuid(array $filter): void
    {
        // Arrange
        $uuid = $this->haveCustomerGroup()->getUuidOrFail();

        // Act
        $response = $this->tester->handleApiRequest(
            'GET',
            $this->tester->getCustomerGroupCompanyBusinessUnitCollectionUrl($uuid, ['filter' => $filter]),
        );

        // Assert
        $this->assertRespondsWithErrorCode(
            $response,
            Response::HTTP_BAD_REQUEST,
            CustomerExperienceManagementConfig::RESPONSE_CODE_UNSUPPORTED_FILTER_FIELD,
        );
    }

    /**
     * @return array<string, array<array<string, mixed>>>
     */
    public static function unsupportedFilterDataProvider(): array
    {
        return [
            'another property' => [[static::FILTER_NAME => static::NAME_ALPHA]],
            'several company uuids' => [[static::FILTER_COMPANY_UUID => [static::UNKNOWN_WELL_FORMED_UUID]]],
        ];
    }

    public function testSearchesTheBusinessUnitNameIgnoringCase(): void
    {
        // Arrange
        $companyTransfer = $this->tester->haveCompany();
        $alphaCompanyBusinessUnitTransfer = $this->haveCompanyBusinessUnitOf($companyTransfer, static::NAME_ALPHA);
        $uuid = $this->haveCustomerGroupWithCompanyBusinessUnits([
            $alphaCompanyBusinessUnitTransfer,
            $this->haveCompanyBusinessUnitOf($companyTransfer, static::NAME_BRAVO),
        ])->getUuidOrFail();

        // Act
        $response = $this->tester->handleApiRequest(
            'GET',
            $this->tester->getCustomerGroupCompanyBusinessUnitCollectionUrl($uuid, ['q' => 'ALPHA']),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertSame([$alphaCompanyBusinessUnitTransfer->getUuidOrFail()], $this->getResourceIds($response));
    }

    public function testSortsByNameInAStableOrderAcrossPages(): void
    {
        // Arrange
        $companyTransfer = $this->tester->haveCompany();
        $bravoCompanyBusinessUnitTransfer = $this->haveCompanyBusinessUnitOf($companyTransfer, static::NAME_BRAVO);
        $firstAlphaCompanyBusinessUnitTransfer = $this->haveCompanyBusinessUnitOf($companyTransfer, static::NAME_ALPHA);
        $secondAlphaCompanyBusinessUnitTransfer = $this->haveCompanyBusinessUnitOf($companyTransfer, static::NAME_ALPHA);
        $uuid = $this->haveCustomerGroupWithCompanyBusinessUnits([
            $bravoCompanyBusinessUnitTransfer,
            $firstAlphaCompanyBusinessUnitTransfer,
            $secondAlphaCompanyBusinessUnitTransfer,
        ])->getUuidOrFail();
        $collectionUrlBuilder = fn (array $query): string => $this->tester->getCustomerGroupCompanyBusinessUnitCollectionUrl($uuid, $query);

        // Act
        $ascendingIds = $this->getIdsOfTwoPages($collectionUrlBuilder, 'name');
        $descendingIds = $this->getIdsOfTwoPages($collectionUrlBuilder, '-name');

        // Assert
        $this->assertSame([
            $firstAlphaCompanyBusinessUnitTransfer->getUuidOrFail(),
            $secondAlphaCompanyBusinessUnitTransfer->getUuidOrFail(),
            $bravoCompanyBusinessUnitTransfer->getUuidOrFail(),
        ], $ascendingIds);
        $this->assertSame([
            $bravoCompanyBusinessUnitTransfer->getUuidOrFail(),
            $secondAlphaCompanyBusinessUnitTransfer->getUuidOrFail(),
            $firstAlphaCompanyBusinessUnitTransfer->getUuidOrFail(),
        ], $descendingIds);
    }

    public function testRejectsAnUnsupportedSortField(): void
    {
        // Arrange
        $uuid = $this->haveCustomerGroup()->getUuidOrFail();

        // Act
        $response = $this->tester->handleApiRequest(
            'GET',
            $this->tester->getCustomerGroupCompanyBusinessUnitCollectionUrl($uuid, ['sort' => static::ATTRIBUTE_COMPANY_NAME]),
        );

        // Assert
        $this->assertRespondsWithErrorCode(
            $response,
            Response::HTTP_BAD_REQUEST,
            CustomerExperienceManagementConfig::RESPONSE_CODE_INVALID_SORT_FIELD,
        );
    }

    public function testAlignsAnOffsetThatIsNotAMultipleOfTheLimitToTheStartOfItsPage(): void
    {
        // Arrange
        $companyTransfer = $this->tester->haveCompany();
        $companyBusinessUnitTransfers = [
            $this->haveCompanyBusinessUnitOf($companyTransfer),
            $this->haveCompanyBusinessUnitOf($companyTransfer),
            $this->haveCompanyBusinessUnitOf($companyTransfer),
        ];
        $uuid = $this->haveCustomerGroupWithCompanyBusinessUnits($companyBusinessUnitTransfers)->getUuidOrFail();

        // Act
        $response = $this->tester->handleApiRequest(
            'GET',
            $this->tester->getCustomerGroupCompanyBusinessUnitCollectionUrl($uuid, ['page' => ['limit' => 2, 'offset' => 1]]),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertSame(
            [$companyBusinessUnitTransfers[0]->getUuidOrFail(), $companyBusinessUnitTransfers[1]->getUuidOrFail()],
            $this->getResourceIds($response),
        );
        $this->assertSame(3, $this->getMetaPagination($response)[static::PAGINATION_KEY_NUM_FOUND]);
        $this->assertSame(1, $this->getMetaPagination($response)[static::PAGINATION_KEY_CURRENT_PAGE]);
        $this->assertSame(2, $this->findLinkedPageOffset($response, static::LINK_NEXT));
        $this->assertNull($this->findLinkedPageOffset($response, static::LINK_PREV));
    }

    public function testLinksTheOtherPagesFromTheLastPage(): void
    {
        // Arrange
        $companyTransfer = $this->tester->haveCompany();
        $companyBusinessUnitTransfers = [
            $this->haveCompanyBusinessUnitOf($companyTransfer),
            $this->haveCompanyBusinessUnitOf($companyTransfer),
            $this->haveCompanyBusinessUnitOf($companyTransfer),
        ];
        $uuid = $this->haveCustomerGroupWithCompanyBusinessUnits($companyBusinessUnitTransfers)->getUuidOrFail();

        // Act
        $response = $this->tester->handleApiRequest(
            'GET',
            $this->tester->getCustomerGroupCompanyBusinessUnitCollectionUrl($uuid, ['page' => ['limit' => 2, 'offset' => 2]]),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertSame([$companyBusinessUnitTransfers[2]->getUuidOrFail()], $this->getResourceIds($response));
        $this->assertSame(2, $this->getMetaPagination($response)[static::PAGINATION_KEY_CURRENT_PAGE]);
        $this->assertSame(2, $this->getMetaPagination($response)[static::PAGINATION_KEY_MAX_PAGE]);
        $this->assertSame(0, $this->findLinkedPageOffset($response, static::LINK_FIRST));
        $this->assertSame(0, $this->findLinkedPageOffset($response, static::LINK_PREV));
        $this->assertSame(2, $this->findLinkedPageOffset($response, static::LINK_LAST));
        $this->assertNull($this->findLinkedPageOffset($response, static::LINK_NEXT));
    }

    public function testReturnsNotFoundForAnUnknownGroup(): void
    {
        // Act
        $response = $this->tester->handleApiRequest(
            'GET',
            $this->tester->getCustomerGroupCompanyBusinessUnitCollectionUrl(static::UNKNOWN_WELL_FORMED_UUID),
        );

        // Assert
        $this->assertRespondsWithErrorCode(
            $response,
            Response::HTTP_NOT_FOUND,
            CustomerExperienceManagementConfig::RESPONSE_CODE_CUSTOMER_GROUP_NOT_FOUND,
        );
    }

    public function testDoesNotReadOneBusinessUnitMembership(): void
    {
        // Arrange
        $companyBusinessUnitTransfer = $this->haveCompanyBusinessUnitOf($this->tester->haveCompany());
        $uuid = $this->haveCustomerGroupWithCompanyBusinessUnits([$companyBusinessUnitTransfer])->getUuidOrFail();

        // Act
        $response = $this->tester->handleApiRequest(
            'GET',
            $this->tester->getCustomerGroupCompanyBusinessUnitUrl($uuid, $companyBusinessUnitTransfer->getUuidOrFail()),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_METHOD_NOT_ALLOWED);
    }

    public function testIncludesEachBusinessUnitAndItsCompanyWithASharedCompanyIncludedOnce(): void
    {
        // Arrange
        $firstCompanyTransfer = $this->tester->haveCompany();
        $secondCompanyTransfer = $this->tester->haveCompany();
        $companyBusinessUnitTransfers = [
            $this->haveCompanyBusinessUnitOf($firstCompanyTransfer),
            $this->haveCompanyBusinessUnitOf($firstCompanyTransfer),
            $this->haveCompanyBusinessUnitOf($secondCompanyTransfer),
        ];
        $uuid = $this->haveCustomerGroupWithCompanyBusinessUnits($companyBusinessUnitTransfers)->getUuidOrFail();

        // Act
        $response = $this->tester->handleApiRequest(
            'GET',
            $this->tester->getCustomerGroupCompanyBusinessUnitCollectionUrl($uuid, [
                'include' => sprintf('%s,%s', static::RELATIONSHIP_COMPANY_BUSINESS_UNITS, static::RELATIONSHIP_COMPANIES),
            ]),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $expectedCompanyUuids = [
            $firstCompanyTransfer->getUuidOrFail(),
            $firstCompanyTransfer->getUuidOrFail(),
            $secondCompanyTransfer->getUuidOrFail(),
        ];

        foreach ($this->decodeJsonApi($response)[static::JSON_API_KEY_DATA] as $index => $member) {
            $this->assertSame([$member[static::JSON_API_KEY_ID]], $this->getRelationshipIds($member, static::RELATIONSHIP_COMPANY_BUSINESS_UNITS));
            $this->assertSame([$expectedCompanyUuids[$index]], $this->getRelationshipIds($member, static::RELATIONSHIP_COMPANIES));
        }

        $this->assertSame(
            [$firstCompanyTransfer->getUuidOrFail(), $secondCompanyTransfer->getUuidOrFail()],
            array_column($this->findIncludedResources($response, static::RELATIONSHIP_COMPANIES), static::JSON_API_KEY_ID),
        );
        $this->assertCount(3, $this->findIncludedResources($response, static::RELATIONSHIP_COMPANY_BUSINESS_UNITS));
    }

    public function testAssignsBusinessUnitsOfTwoCompanies(): void
    {
        // Arrange
        $uuid = $this->haveCustomerGroup()->getUuidOrFail();
        $firstCompanyBusinessUnitUuid = $this->haveCompanyBusinessUnitOf($this->tester->haveCompany())->getUuidOrFail();
        $secondCompanyBusinessUnitUuid = $this->haveCompanyBusinessUnitOf($this->tester->haveCompany())->getUuidOrFail();

        // Act
        $response = $this->assignCompanyBusinessUnits($uuid, [$firstCompanyBusinessUnitUuid, $secondCompanyBusinessUnitUuid]);

        // Assert
        $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode(), (string)$response->getContent());
        $this->assertSame(
            [$firstCompanyBusinessUnitUuid, $secondCompanyBusinessUnitUuid],
            $this->tester->getAssignedCompanyBusinessUnitUuids($uuid),
        );
    }

    public function testRejectsTheWholeAssignmentAndListsEveryUnknownBusinessUnitUuid(): void
    {
        // Arrange
        $uuid = $this->haveCustomerGroup()->getUuidOrFail();
        $companyBusinessUnitUuid = $this->haveCompanyBusinessUnitOf($this->tester->haveCompany())->getUuidOrFail();

        // Act
        $response = $this->assignCompanyBusinessUnits($uuid, [$companyBusinessUnitUuid, static::UNKNOWN_WELL_FORMED_UUID]);

        // Assert
        $this->assertJsonApiError(
            $response,
            Response::HTTP_UNPROCESSABLE_ENTITY,
            CustomerExperienceManagementConfig::RESPONSE_CODE_CUSTOMER_GROUP_COMPANY_BUSINESS_UNIT_NOT_FOUND,
            sprintf(CustomerExperienceManagementConfig::RESPONSE_DETAILS_CUSTOMER_GROUP_COMPANY_BUSINESS_UNIT_NOT_FOUND, static::UNKNOWN_WELL_FORMED_UUID),
        );
        $this->assertSame([], $this->tester->getAssignedCompanyBusinessUnitUuids($uuid));
    }

    /**
     * @dataProvider invalidUuidListDataProvider
     *
     * @param list<string> $companyBusinessUnitUuids
     */
    public function testRejectsAListTheValidationFileDoesNotAllow(array $companyBusinessUnitUuids): void
    {
        // Arrange
        $uuid = $this->haveCustomerGroup()->getUuidOrFail();

        // Act
        $response = $this->assignCompanyBusinessUnits($uuid, $companyBusinessUnitUuids);

        // Assert
        $this->assertRespondsWithErrorCode($response, Response::HTTP_UNPROCESSABLE_ENTITY, static::RESPONSE_CODE_FRAMEWORK_VALIDATION);
        $this->assertSame([], $this->tester->getAssignedCompanyBusinessUnitUuids($uuid));
    }

    /**
     * @return array<string, array<list<string>>>
     */
    public static function invalidUuidListDataProvider(): array
    {
        return [
            'more than 1000 uuids' => [static::generateUuids(static::MAXIMUM_UUIDS + 1)],
            'a duplicate' => [[static::UNKNOWN_WELL_FORMED_UUID, static::UNKNOWN_WELL_FORMED_UUID]],
            'a value that is not a uuid' => [[static::MALFORMED_UUID]],
        ];
    }

    public function testReturnsNotFoundWhenAssigningToAnUnknownGroup(): void
    {
        // Act
        $response = $this->assignCompanyBusinessUnits(
            static::UNKNOWN_WELL_FORMED_UUID,
            [$this->haveCompanyBusinessUnitOf($this->tester->haveCompany())->getUuidOrFail()],
        );

        // Assert
        $this->assertRespondsWithErrorCode(
            $response,
            Response::HTTP_NOT_FOUND,
            CustomerExperienceManagementConfig::RESPONSE_CODE_CUSTOMER_GROUP_NOT_FOUND,
        );
    }

    /**
     * @dataProvider bodyWithoutAListDataProvider
     *
     * @param array<string, mixed> $attributes
     */
    public function testRejectsAnAssignBodyWithoutAList(array $attributes): void
    {
        // Arrange
        $uuid = $this->haveCustomerGroup()->getUuidOrFail();

        // Act
        $response = $this->handleApiRequest(
            'POST',
            $this->tester->getCustomerGroupCompanyBusinessUnitCollectionUrl($uuid),
            $this->tester->buildCustomerGroupCompanyBusinessUnitsRequestBody($attributes),
        );

        // Assert
        $this->assertRespondsWithErrorCode($response, Response::HTTP_UNPROCESSABLE_ENTITY, static::RESPONSE_CODE_FRAMEWORK_VALIDATION);
        $this->assertSame([], $this->tester->getAssignedCompanyBusinessUnitUuids($uuid));
    }

    /**
     * @return array<string, array<array<string, mixed>>>
     */
    public static function bodyWithoutAListDataProvider(): array
    {
        return [
            'no attributes' => [[]],
            'an empty list' => [[static::ATTRIBUTE_COMPANY_BUSINESS_UNIT_UUIDS => []]],
            'the former all flag' => [[static::ATTRIBUTE_ALL => true]],
        ];
    }

    public function testReplacesTheBusinessUnitsOfTheGroupWithTheSentList(): void
    {
        // Arrange
        $companyTransfer = $this->tester->haveCompany();
        $keptCompanyBusinessUnitTransfer = $this->haveCompanyBusinessUnitOf($companyTransfer);
        $removedCompanyBusinessUnitTransfer = $this->haveCompanyBusinessUnitOf($companyTransfer);
        $addedCompanyBusinessUnitTransfer = $this->haveCompanyBusinessUnitOf($this->tester->haveCompany());
        $uuid = $this->haveCustomerGroupWithCompanyBusinessUnits([$keptCompanyBusinessUnitTransfer, $removedCompanyBusinessUnitTransfer])
            ->getUuidOrFail();

        // Act
        $response = $this->replaceCompanyBusinessUnits($uuid, [
            static::ATTRIBUTE_COMPANY_BUSINESS_UNIT_UUIDS => [
                $addedCompanyBusinessUnitTransfer->getUuidOrFail(),
                $keptCompanyBusinessUnitTransfer->getUuidOrFail(),
            ],
        ]);

        // Assert
        $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode(), (string)$response->getContent());
        $this->assertSame(
            [$keptCompanyBusinessUnitTransfer->getUuidOrFail(), $addedCompanyBusinessUnitTransfer->getUuidOrFail()],
            $this->tester->getAssignedCompanyBusinessUnitUuids($uuid),
        );
    }

    public function testReplacingWithAnEmptyListRemovesEveryBusinessUnitAndRepeatingItChangesNothing(): void
    {
        // Arrange
        $companyTransfer = $this->tester->haveCompany();
        $uuid = $this->haveCustomerGroupWithCompanyBusinessUnits([
            $this->haveCompanyBusinessUnitOf($companyTransfer),
            $this->haveCompanyBusinessUnitOf($companyTransfer),
        ])->getUuidOrFail();

        // Act
        $firstResponse = $this->replaceCompanyBusinessUnits($uuid, [static::ATTRIBUTE_COMPANY_BUSINESS_UNIT_UUIDS => []]);
        $secondResponse = $this->replaceCompanyBusinessUnits($uuid, [static::ATTRIBUTE_COMPANY_BUSINESS_UNIT_UUIDS => []]);

        // Assert
        $this->assertSame(Response::HTTP_NO_CONTENT, $firstResponse->getStatusCode(), (string)$firstResponse->getContent());
        $this->assertSame(Response::HTTP_NO_CONTENT, $secondResponse->getStatusCode(), (string)$secondResponse->getContent());
        $this->assertSame([], $this->tester->getAssignedCompanyBusinessUnitUuids($uuid));
    }

    public function testRejectsAReplaceWithoutAList(): void
    {
        // Arrange
        $companyBusinessUnitTransfer = $this->haveCompanyBusinessUnitOf($this->tester->haveCompany());
        $uuid = $this->haveCustomerGroupWithCompanyBusinessUnits([$companyBusinessUnitTransfer])->getUuidOrFail();

        // Act
        $response = $this->replaceCompanyBusinessUnits($uuid, []);

        // Assert
        $this->assertRespondsWithErrorCode($response, Response::HTTP_UNPROCESSABLE_ENTITY, static::RESPONSE_CODE_FRAMEWORK_VALIDATION);
        $this->assertSame([$companyBusinessUnitTransfer->getUuidOrFail()], $this->tester->getAssignedCompanyBusinessUnitUuids($uuid));
    }

    /**
     * @dataProvider invalidUuidListDataProvider
     *
     * @param list<string> $companyBusinessUnitUuids
     */
    public function testRejectsAReplaceListTheValidationFileDoesNotAllow(array $companyBusinessUnitUuids): void
    {
        // Arrange
        $companyBusinessUnitTransfer = $this->haveCompanyBusinessUnitOf($this->tester->haveCompany());
        $uuid = $this->haveCustomerGroupWithCompanyBusinessUnits([$companyBusinessUnitTransfer])->getUuidOrFail();

        // Act
        $response = $this->replaceCompanyBusinessUnits($uuid, [static::ATTRIBUTE_COMPANY_BUSINESS_UNIT_UUIDS => $companyBusinessUnitUuids]);

        // Assert
        $this->assertRespondsWithErrorCode($response, Response::HTTP_UNPROCESSABLE_ENTITY, static::RESPONSE_CODE_FRAMEWORK_VALIDATION);
        $this->assertSame([$companyBusinessUnitTransfer->getUuidOrFail()], $this->tester->getAssignedCompanyBusinessUnitUuids($uuid));
    }

    public function testRejectsTheWholeReplaceAndListsEveryUnknownBusinessUnitUuid(): void
    {
        // Arrange
        $companyTransfer = $this->tester->haveCompany();
        $memberCompanyBusinessUnitTransfer = $this->haveCompanyBusinessUnitOf($companyTransfer);
        $uuid = $this->haveCustomerGroupWithCompanyBusinessUnits([$memberCompanyBusinessUnitTransfer])->getUuidOrFail();

        // Act
        $response = $this->replaceCompanyBusinessUnits($uuid, [
            static::ATTRIBUTE_COMPANY_BUSINESS_UNIT_UUIDS => [$this->haveCompanyBusinessUnitOf($companyTransfer)->getUuidOrFail(), static::UNKNOWN_WELL_FORMED_UUID],
        ]);

        // Assert
        $this->assertJsonApiError(
            $response,
            Response::HTTP_UNPROCESSABLE_ENTITY,
            CustomerExperienceManagementConfig::RESPONSE_CODE_CUSTOMER_GROUP_COMPANY_BUSINESS_UNIT_NOT_FOUND,
            sprintf(CustomerExperienceManagementConfig::RESPONSE_DETAILS_CUSTOMER_GROUP_COMPANY_BUSINESS_UNIT_NOT_FOUND, static::UNKNOWN_WELL_FORMED_UUID),
        );
        $this->assertSame([$memberCompanyBusinessUnitTransfer->getUuidOrFail()], $this->tester->getAssignedCompanyBusinessUnitUuids($uuid));
    }

    public function testReturnsNotFoundWhenReplacingTheBusinessUnitsOfAnUnknownGroup(): void
    {
        // Act
        $response = $this->replaceCompanyBusinessUnits(static::UNKNOWN_WELL_FORMED_UUID, [static::ATTRIBUTE_COMPANY_BUSINESS_UNIT_UUIDS => []]);

        // Assert
        $this->assertRespondsWithErrorCode(
            $response,
            Response::HTTP_NOT_FOUND,
            CustomerExperienceManagementConfig::RESPONSE_CODE_CUSTOMER_GROUP_NOT_FOUND,
        );
    }

    public function testRemovesOneBusinessUnitFromTheGroup(): void
    {
        // Arrange
        $companyTransfer = $this->tester->haveCompany();
        $removedCompanyBusinessUnitTransfer = $this->haveCompanyBusinessUnitOf($companyTransfer);
        $keptCompanyBusinessUnitTransfer = $this->haveCompanyBusinessUnitOf($companyTransfer);
        $uuid = $this->haveCustomerGroupWithCompanyBusinessUnits([$removedCompanyBusinessUnitTransfer, $keptCompanyBusinessUnitTransfer])
            ->getUuidOrFail();
        $otherUuid = $this->haveCustomerGroupWithCompanyBusinessUnits([$removedCompanyBusinessUnitTransfer])->getUuidOrFail();

        // Act
        $response = $this->removeCompanyBusinessUnit($uuid, $removedCompanyBusinessUnitTransfer->getUuidOrFail());

        // Assert
        $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode(), (string)$response->getContent());
        $this->assertSame([$keptCompanyBusinessUnitTransfer->getUuidOrFail()], $this->tester->getAssignedCompanyBusinessUnitUuids($uuid));
        $this->assertSame([$removedCompanyBusinessUnitTransfer->getUuidOrFail()], $this->tester->getAssignedCompanyBusinessUnitUuids($otherUuid));
    }

    /**
     * @dataProvider isExistingBusinessUnitDataProvider
     */
    public function testReturnsNotFoundWhenRemovingABusinessUnitThatIsNotAssigned(bool $isExistingBusinessUnit): void
    {
        // Arrange
        $companyTransfer = $this->tester->haveCompany();
        $memberCompanyBusinessUnitTransfer = $this->haveCompanyBusinessUnitOf($companyTransfer);
        $uuid = $this->haveCustomerGroupWithCompanyBusinessUnits([$memberCompanyBusinessUnitTransfer])->getUuidOrFail();
        $companyBusinessUnitUuid = $isExistingBusinessUnit
            ? $this->haveCompanyBusinessUnitOf($companyTransfer)->getUuidOrFail()
            : static::UNKNOWN_WELL_FORMED_UUID;

        // Act
        $response = $this->removeCompanyBusinessUnit($uuid, $companyBusinessUnitUuid);

        // Assert
        $this->assertJsonApiError(
            $response,
            Response::HTTP_NOT_FOUND,
            CustomerExperienceManagementConfig::RESPONSE_CODE_CUSTOMER_GROUP_COMPANY_BUSINESS_UNIT_ASSIGNMENT_NOT_FOUND,
            sprintf(
                CustomerExperienceManagementConfig::RESPONSE_DETAILS_CUSTOMER_GROUP_COMPANY_BUSINESS_UNIT_ASSIGNMENT_NOT_FOUND,
                $companyBusinessUnitUuid,
                $uuid,
            ),
        );
        $this->assertSame([$memberCompanyBusinessUnitTransfer->getUuidOrFail()], $this->tester->getAssignedCompanyBusinessUnitUuids($uuid));
    }

    /**
     * @return array<string, array<bool>>
     */
    public static function isExistingBusinessUnitDataProvider(): array
    {
        return [
            'an existing business unit' => [true],
            'an unknown business unit' => [false],
        ];
    }

    public function testReturnsNotFoundWhenRemovingABusinessUnitFromAnUnknownGroup(): void
    {
        // Act
        $response = $this->removeCompanyBusinessUnit(
            static::UNKNOWN_WELL_FORMED_UUID,
            $this->haveCompanyBusinessUnitOf($this->tester->haveCompany())->getUuidOrFail(),
        );

        // Assert
        $this->assertRespondsWithErrorCode(
            $response,
            Response::HTTP_NOT_FOUND,
            CustomerExperienceManagementConfig::RESPONSE_CODE_CUSTOMER_GROUP_NOT_FOUND,
        );
    }

    public function testAssigningABusinessUnitDoesNotAssignItsCompany(): void
    {
        // Arrange
        $uuid = $this->haveCustomerGroup()->getUuidOrFail();
        $companyBusinessUnitUuid = $this->haveCompanyBusinessUnitOf($this->tester->haveCompany())->getUuidOrFail();

        // Act
        $this->assignCompanyBusinessUnits($uuid, [$companyBusinessUnitUuid]);

        // Assert
        $this->assertSame([], $this->tester->getAssignedCompanyUuids($uuid));
    }

    public function testRemovingACompanyKeepsTheBusinessUnitsOfThatCompany(): void
    {
        // Arrange
        $companyTransfer = $this->tester->haveCompany();
        $companyBusinessUnitUuid = $this->haveCompanyBusinessUnitOf($companyTransfer)->getUuidOrFail();
        $uuid = $this->haveCustomerGroupWithCompanies([$companyTransfer])->getUuidOrFail();
        $this->assignCompanyBusinessUnits($uuid, [$companyBusinessUnitUuid]);

        // Act
        $response = $this->handleApiRequest(
            'DELETE',
            $this->tester->getCustomerGroupCompanyUrl($uuid, $companyTransfer->getUuidOrFail()),
        );

        // Assert
        $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode(), (string)$response->getContent());
        $this->assertSame([], $this->tester->getAssignedCompanyUuids($uuid));
        $this->assertSame([$companyBusinessUnitUuid], $this->tester->getAssignedCompanyBusinessUnitUuids($uuid));
    }

    public function testRemovingTheLastBusinessUnitOfACompanyKeepsTheCompanyMembership(): void
    {
        // Arrange
        $companyTransfer = $this->tester->haveCompany();
        $companyBusinessUnitUuid = $this->haveCompanyBusinessUnitOf($companyTransfer)->getUuidOrFail();
        $uuid = $this->haveCustomerGroupWithCompanies([$companyTransfer])->getUuidOrFail();
        $this->assignCompanyBusinessUnits($uuid, [$companyBusinessUnitUuid]);

        // Act
        $response = $this->removeCompanyBusinessUnit($uuid, $companyBusinessUnitUuid);

        // Assert
        $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode(), (string)$response->getContent());
        $this->assertSame([], $this->tester->getAssignedCompanyBusinessUnitUuids($uuid));
        $this->assertSame([$companyTransfer->getUuidOrFail()], $this->tester->getAssignedCompanyUuids($uuid));
    }

    public function testReplacingTheBusinessUnitsWithAnEmptyListKeepsTheCompaniesAndTheOtherGroups(): void
    {
        // Arrange
        $companyTransfer = $this->tester->haveCompany();
        $companyBusinessUnitTransfer = $this->haveCompanyBusinessUnitOf($companyTransfer);
        $uuid = $this->haveCustomerGroupWithCompanies([$companyTransfer])->getUuidOrFail();
        $otherUuid = $this->haveCustomerGroupWithCompanyBusinessUnits([$companyBusinessUnitTransfer])->getUuidOrFail();
        $this->assignCompanyBusinessUnits($uuid, [$companyBusinessUnitTransfer->getUuidOrFail()]);

        // Act
        $response = $this->replaceCompanyBusinessUnits($uuid, [static::ATTRIBUTE_COMPANY_BUSINESS_UNIT_UUIDS => []]);

        // Assert
        $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode(), (string)$response->getContent());
        $this->assertSame([], $this->tester->getAssignedCompanyBusinessUnitUuids($uuid));
        $this->assertSame([$companyTransfer->getUuidOrFail()], $this->tester->getAssignedCompanyUuids($uuid));
        $this->assertSame([$companyBusinessUnitTransfer->getUuidOrFail()], $this->tester->getAssignedCompanyBusinessUnitUuids($otherUuid));
    }

    public function testReplacingTheCompaniesWithAnEmptyListKeepsTheBusinessUnitsAndTheOtherGroups(): void
    {
        // Arrange
        $companyTransfer = $this->tester->haveCompany();
        $companyBusinessUnitUuid = $this->haveCompanyBusinessUnitOf($companyTransfer)->getUuidOrFail();
        $uuid = $this->haveCustomerGroupWithCompanies([$companyTransfer])->getUuidOrFail();
        $otherUuid = $this->haveCustomerGroupWithCompanies([$companyTransfer])->getUuidOrFail();
        $this->assignCompanyBusinessUnits($uuid, [$companyBusinessUnitUuid]);

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getCustomerGroupCompanyCollectionUrl($uuid),
            $this->tester->buildCustomerGroupCompaniesRequestBody([static::ATTRIBUTE_COMPANY_UUIDS => []]),
        );

        // Assert
        $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode(), (string)$response->getContent());
        $this->assertSame([], $this->tester->getAssignedCompanyUuids($uuid));
        $this->assertSame([$companyBusinessUnitUuid], $this->tester->getAssignedCompanyBusinessUnitUuids($uuid));
        $this->assertSame([$companyTransfer->getUuidOrFail()], $this->tester->getAssignedCompanyUuids($otherUuid));
    }

    public function testRespondsUnauthorizedWithoutAValidToken(): void
    {
        // Arrange
        $uuid = $this->haveCustomerGroup()->getUuidOrFail();
        $this->tester->actingWithInvalidToken();

        // Act
        $response = $this->tester->handleApiRequest('GET', $this->tester->getCustomerGroupCompanyBusinessUnitCollectionUrl($uuid));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_UNAUTHORIZED);
    }

    public function testRespondsForbiddenWithoutAccessToTheResource(): void
    {
        // Arrange
        $uuid = $this->haveCustomerGroup()->getUuidOrFail();
        $this->tester->actingAsUserWithoutAclAccess();

        // Act
        $response = $this->tester->handleApiRequest('GET', $this->tester->getCustomerGroupCompanyBusinessUnitCollectionUrl($uuid));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_FORBIDDEN);
    }

    /**
     * @param list<string> $companyBusinessUnitUuids
     */
    protected function assignCompanyBusinessUnits(string $uuid, array $companyBusinessUnitUuids): Response
    {
        return $this->handleApiRequest(
            'POST',
            $this->tester->getCustomerGroupCompanyBusinessUnitCollectionUrl($uuid),
            $this->tester->buildCustomerGroupCompanyBusinessUnitsRequestBody([static::ATTRIBUTE_COMPANY_BUSINESS_UNIT_UUIDS => $companyBusinessUnitUuids]),
        );
    }

    /**
     * @param array<string, mixed> $attributes
     */
    protected function replaceCompanyBusinessUnits(string $uuid, array $attributes): Response
    {
        return $this->handleApiRequest(
            'PATCH',
            $this->tester->getCustomerGroupCompanyBusinessUnitCollectionUrl($uuid),
            $this->tester->buildCustomerGroupCompanyBusinessUnitsRequestBody($attributes),
        );
    }

    protected function removeCompanyBusinessUnit(string $uuid, string $companyBusinessUnitUuid): Response
    {
        return $this->handleApiRequest('DELETE', $this->tester->getCustomerGroupCompanyBusinessUnitUrl($uuid, $companyBusinessUnitUuid));
    }

    /**
     * @param array<string, mixed> $member
     *
     * @return list<string>
     */
    protected function getRelationshipIds(array $member, string $relationshipName): array
    {
        return array_column(
            $member[static::JSON_API_KEY_RELATIONSHIPS][$relationshipName][static::JSON_API_KEY_DATA] ?? [],
            static::JSON_API_KEY_ID,
        );
    }
}
