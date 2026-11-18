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
 * @group CustomerGroupCompanyAssignmentBackendApiTest
 * Add your own group annotations below this line
 */
class CustomerGroupCompanyAssignmentBackendApiTest extends AbstractCustomerGroupBackendApiTestCase
{
    protected const string ATTRIBUTE_COMPANY_UUIDS = 'companyUuids';

    protected const string ATTRIBUTE_ALL = 'all';

    protected const string SECOND_UNKNOWN_WELL_FORMED_UUID = '11111111-2222-4333-8444-555555555556';

    public function testAssignsCompaniesToTheGroup(): void
    {
        // Arrange
        $uuid = $this->haveCustomerGroup()->getUuidOrFail();
        $firstCompanyTransfer = $this->tester->haveCompany();
        $secondCompanyTransfer = $this->tester->haveCompany();

        // Act
        $response = $this->assignCompanies($uuid, [$firstCompanyTransfer->getUuidOrFail(), $secondCompanyTransfer->getUuidOrFail()]);

        // Assert
        $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode(), (string)$response->getContent());
        $this->assertSame(
            [$firstCompanyTransfer->getUuidOrFail(), $secondCompanyTransfer->getUuidOrFail()],
            $this->tester->getAssignedCompanyUuids($uuid),
        );
    }

    public function testAssigningTheSameCompaniesAgainChangesNothing(): void
    {
        // Arrange
        $uuid = $this->haveCustomerGroup()->getUuidOrFail();
        $companyUuid = $this->tester->haveCompany()->getUuidOrFail();
        $this->assignCompanies($uuid, [$companyUuid]);

        // Act
        $response = $this->assignCompanies($uuid, [$companyUuid]);

        // Assert
        $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode(), (string)$response->getContent());
        $this->assertSame([$companyUuid], $this->tester->getAssignedCompanyUuids($uuid));
    }

    public function testAssigningKeepsTheCompaniesAssignedInTheBackOffice(): void
    {
        // Arrange
        $backOfficeCompanyTransfer = $this->tester->haveCompany();
        $uuid = $this->haveCustomerGroupWithCompanies([$backOfficeCompanyTransfer])->getUuidOrFail();
        $companyUuid = $this->tester->haveCompany()->getUuidOrFail();

        // Act
        $response = $this->assignCompanies($uuid, [$companyUuid]);

        // Assert
        $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode(), (string)$response->getContent());
        $this->assertSame([$backOfficeCompanyTransfer->getUuidOrFail(), $companyUuid], $this->tester->getAssignedCompanyUuids($uuid));
    }

    public function testRejectsTheWholeAssignmentAndListsEveryUnknownUuid(): void
    {
        // Arrange
        $uuid = $this->haveCustomerGroup()->getUuidOrFail();
        $companyUuid = $this->tester->haveCompany()->getUuidOrFail();

        // Act
        $response = $this->assignCompanies(
            $uuid,
            [static::UNKNOWN_WELL_FORMED_UUID, $companyUuid, static::SECOND_UNKNOWN_WELL_FORMED_UUID],
        );

        // Assert
        $this->assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $response->getStatusCode(), (string)$response->getContent());
        $this->assertSame([
            [
                CustomerExperienceManagementConfig::RESPONSE_CODE_CUSTOMER_GROUP_COMPANY_NOT_FOUND,
                sprintf(CustomerExperienceManagementConfig::RESPONSE_DETAILS_CUSTOMER_GROUP_COMPANY_NOT_FOUND, static::UNKNOWN_WELL_FORMED_UUID),
            ],
            [
                CustomerExperienceManagementConfig::RESPONSE_CODE_CUSTOMER_GROUP_COMPANY_NOT_FOUND,
                sprintf(CustomerExperienceManagementConfig::RESPONSE_DETAILS_CUSTOMER_GROUP_COMPANY_NOT_FOUND, static::SECOND_UNKNOWN_WELL_FORMED_UUID),
            ],
        ], $this->getErrorCodesAndDetails($response));
        $this->assertSame([], $this->tester->getAssignedCompanyUuids($uuid));
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
            $this->tester->getCustomerGroupCompanyCollectionUrl($uuid),
            $this->tester->buildCustomerGroupCompaniesRequestBody($attributes),
        );

        // Assert
        $this->assertRespondsWithErrorCode($response, Response::HTTP_UNPROCESSABLE_ENTITY, static::RESPONSE_CODE_FRAMEWORK_VALIDATION);
        $this->assertSame([], $this->tester->getAssignedCompanyUuids($uuid));
    }

    /**
     * @return array<string, array<array<string, mixed>>>
     */
    public static function bodyWithoutAListDataProvider(): array
    {
        return [
            'no attributes' => [[]],
            'an empty list' => [[static::ATTRIBUTE_COMPANY_UUIDS => []]],
            'the former all flag' => [[static::ATTRIBUTE_ALL => true]],
        ];
    }

    /**
     * @dataProvider invalidUuidListDataProvider
     *
     * @param list<string> $companyUuids
     */
    public function testRejectsAnAssignListTheValidationFileDoesNotAllow(array $companyUuids): void
    {
        // Arrange
        $uuid = $this->haveCustomerGroup()->getUuidOrFail();

        // Act
        $response = $this->assignCompanies($uuid, $companyUuids);

        // Assert
        $this->assertRespondsWithErrorCode($response, Response::HTTP_UNPROCESSABLE_ENTITY, static::RESPONSE_CODE_FRAMEWORK_VALIDATION);
        $this->assertSame([], $this->tester->getAssignedCompanyUuids($uuid));
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
        $response = $this->assignCompanies(static::UNKNOWN_WELL_FORMED_UUID, [$this->tester->haveCompany()->getUuidOrFail()]);

        // Assert
        $this->assertRespondsWithErrorCode(
            $response,
            Response::HTTP_NOT_FOUND,
            CustomerExperienceManagementConfig::RESPONSE_CODE_CUSTOMER_GROUP_NOT_FOUND,
        );
    }

    public function testReplacesTheCompaniesOfTheGroupWithTheSentList(): void
    {
        // Arrange
        $keptCompanyTransfer = $this->tester->haveCompany();
        $removedCompanyTransfer = $this->tester->haveCompany();
        $addedCompanyTransfer = $this->tester->haveCompany();
        $uuid = $this->haveCustomerGroupWithCompanies([$keptCompanyTransfer, $removedCompanyTransfer])->getUuidOrFail();
        $otherUuid = $this->haveCustomerGroupWithCompanies([$removedCompanyTransfer])->getUuidOrFail();

        // Act
        $response = $this->replaceCompanies($uuid, [
            static::ATTRIBUTE_COMPANY_UUIDS => [$addedCompanyTransfer->getUuidOrFail(), $keptCompanyTransfer->getUuidOrFail()],
        ]);

        // Assert
        $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode(), (string)$response->getContent());
        $this->assertSame(
            [$keptCompanyTransfer->getUuidOrFail(), $addedCompanyTransfer->getUuidOrFail()],
            $this->tester->getAssignedCompanyUuids($uuid),
        );
        $this->assertSame([$removedCompanyTransfer->getUuidOrFail()], $this->tester->getAssignedCompanyUuids($otherUuid));
    }

    public function testReplacingWithAnEmptyListRemovesEveryCompanyAndRepeatingItChangesNothing(): void
    {
        // Arrange
        $customerGroupTransfer = $this->haveCustomerGroupWithCompanies([$this->tester->haveCompany(), $this->tester->haveCompany()]);
        $uuid = $customerGroupTransfer->getUuidOrFail();

        // Act
        $firstResponse = $this->replaceCompanies($uuid, [static::ATTRIBUTE_COMPANY_UUIDS => []]);
        $secondResponse = $this->replaceCompanies($uuid, [static::ATTRIBUTE_COMPANY_UUIDS => []]);

        // Assert
        $this->assertSame(Response::HTTP_NO_CONTENT, $firstResponse->getStatusCode(), (string)$firstResponse->getContent());
        $this->assertSame(Response::HTTP_NO_CONTENT, $secondResponse->getStatusCode(), (string)$secondResponse->getContent());
        $this->assertSame([], $this->tester->getAssignedCompanyUuids($uuid));
        $this->assertSame(1, $this->tester->countCustomerGroupsWithName($customerGroupTransfer->getNameOrFail()));
    }

    /**
     * @dataProvider replaceBodyWithoutAListDataProvider
     *
     * @param array<string, mixed> $attributes
     */
    public function testRejectsAReplaceWithoutAList(array $attributes): void
    {
        // Arrange
        $companyTransfer = $this->tester->haveCompany();
        $uuid = $this->haveCustomerGroupWithCompanies([$companyTransfer])->getUuidOrFail();

        // Act
        $response = $this->replaceCompanies($uuid, $attributes);

        // Assert
        $this->assertRespondsWithErrorCode($response, Response::HTTP_UNPROCESSABLE_ENTITY, static::RESPONSE_CODE_FRAMEWORK_VALIDATION);
        $this->assertSame([$companyTransfer->getUuidOrFail()], $this->tester->getAssignedCompanyUuids($uuid));
    }

    /**
     * @return array<string, array<array<string, mixed>>>
     */
    public static function replaceBodyWithoutAListDataProvider(): array
    {
        return [
            'no attributes' => [[]],
            'the former all flag' => [[static::ATTRIBUTE_ALL => true]],
        ];
    }

    /**
     * @dataProvider invalidUuidListDataProvider
     *
     * @param list<string> $companyUuids
     */
    public function testRejectsAReplaceListTheValidationFileDoesNotAllow(array $companyUuids): void
    {
        // Arrange
        $companyTransfer = $this->tester->haveCompany();
        $uuid = $this->haveCustomerGroupWithCompanies([$companyTransfer])->getUuidOrFail();

        // Act
        $response = $this->replaceCompanies($uuid, [static::ATTRIBUTE_COMPANY_UUIDS => $companyUuids]);

        // Assert
        $this->assertRespondsWithErrorCode($response, Response::HTTP_UNPROCESSABLE_ENTITY, static::RESPONSE_CODE_FRAMEWORK_VALIDATION);
        $this->assertSame([$companyTransfer->getUuidOrFail()], $this->tester->getAssignedCompanyUuids($uuid));
    }

    public function testRejectsTheWholeReplaceAndListsEveryUnknownUuid(): void
    {
        // Arrange
        $memberCompanyTransfer = $this->tester->haveCompany();
        $uuid = $this->haveCustomerGroupWithCompanies([$memberCompanyTransfer])->getUuidOrFail();

        // Act
        $response = $this->replaceCompanies($uuid, [
            static::ATTRIBUTE_COMPANY_UUIDS => [$this->tester->haveCompany()->getUuidOrFail(), static::UNKNOWN_WELL_FORMED_UUID],
        ]);

        // Assert
        $this->assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $response->getStatusCode(), (string)$response->getContent());
        $this->assertSame([
            [
                CustomerExperienceManagementConfig::RESPONSE_CODE_CUSTOMER_GROUP_COMPANY_NOT_FOUND,
                sprintf(CustomerExperienceManagementConfig::RESPONSE_DETAILS_CUSTOMER_GROUP_COMPANY_NOT_FOUND, static::UNKNOWN_WELL_FORMED_UUID),
            ],
        ], $this->getErrorCodesAndDetails($response));
        $this->assertSame([$memberCompanyTransfer->getUuidOrFail()], $this->tester->getAssignedCompanyUuids($uuid));
    }

    public function testReturnsNotFoundWhenReplacingTheCompaniesOfAnUnknownGroup(): void
    {
        // Act
        $response = $this->replaceCompanies(static::UNKNOWN_WELL_FORMED_UUID, [static::ATTRIBUTE_COMPANY_UUIDS => []]);

        // Assert
        $this->assertRespondsWithErrorCode(
            $response,
            Response::HTTP_NOT_FOUND,
            CustomerExperienceManagementConfig::RESPONSE_CODE_CUSTOMER_GROUP_NOT_FOUND,
        );
    }

    public function testRemovesOneCompanyFromTheGroup(): void
    {
        // Arrange
        $removedCompanyTransfer = $this->tester->haveCompany();
        $keptCompanyTransfer = $this->tester->haveCompany();
        $uuid = $this->haveCustomerGroupWithCompanies([$removedCompanyTransfer, $keptCompanyTransfer])->getUuidOrFail();
        $otherUuid = $this->haveCustomerGroupWithCompanies([$removedCompanyTransfer])->getUuidOrFail();

        // Act
        $response = $this->removeCompany($uuid, $removedCompanyTransfer->getUuidOrFail());

        // Assert
        $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode(), (string)$response->getContent());
        $this->assertSame([$keptCompanyTransfer->getUuidOrFail()], $this->tester->getAssignedCompanyUuids($uuid));
        $this->assertSame([$removedCompanyTransfer->getUuidOrFail()], $this->tester->getAssignedCompanyUuids($otherUuid));
    }

    /**
     * @dataProvider isExistingCompanyDataProvider
     */
    public function testReturnsNotFoundWhenRemovingACompanyThatIsNotAssigned(bool $isExistingCompany): void
    {
        // Arrange
        $memberCompanyTransfer = $this->tester->haveCompany();
        $uuid = $this->haveCustomerGroupWithCompanies([$memberCompanyTransfer])->getUuidOrFail();
        $companyUuid = $isExistingCompany ? $this->tester->haveCompany()->getUuidOrFail() : static::UNKNOWN_WELL_FORMED_UUID;

        // Act
        $response = $this->removeCompany($uuid, $companyUuid);

        // Assert
        $this->assertJsonApiError(
            $response,
            Response::HTTP_NOT_FOUND,
            CustomerExperienceManagementConfig::RESPONSE_CODE_CUSTOMER_GROUP_COMPANY_ASSIGNMENT_NOT_FOUND,
            sprintf(CustomerExperienceManagementConfig::RESPONSE_DETAILS_CUSTOMER_GROUP_COMPANY_ASSIGNMENT_NOT_FOUND, $companyUuid, $uuid),
        );
        $this->assertSame([$memberCompanyTransfer->getUuidOrFail()], $this->tester->getAssignedCompanyUuids($uuid));
    }

    /**
     * @return array<string, array<bool>>
     */
    public static function isExistingCompanyDataProvider(): array
    {
        return [
            'an existing company' => [true],
            'an unknown company' => [false],
        ];
    }

    public function testReturnsNotFoundWhenRemovingACompanyFromAnUnknownGroup(): void
    {
        // Act
        $response = $this->removeCompany(static::UNKNOWN_WELL_FORMED_UUID, $this->tester->haveCompany()->getUuidOrFail());

        // Assert
        $this->assertRespondsWithErrorCode(
            $response,
            Response::HTTP_NOT_FOUND,
            CustomerExperienceManagementConfig::RESPONSE_CODE_CUSTOMER_GROUP_NOT_FOUND,
        );
    }

    public function testDeletingTheGroupRemovesItsCompanyMemberships(): void
    {
        // Arrange
        $companyTransfer = $this->tester->haveCompany();
        $customerGroupTransfer = $this->haveCustomerGroupWithCompanies([$companyTransfer]);

        // Act
        $response = $this->handleApiRequest('DELETE', $this->tester->getCustomerGroupUrl($customerGroupTransfer->getUuidOrFail()));

        // Assert
        $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode(), (string)$response->getContent());
        $this->assertSame(0, $this->tester->countMembershipsOfCustomerGroup($customerGroupTransfer->getIdCustomerGroupOrFail()));
        $this->assertNotSame([], $this->tester->getCompanyRow($companyTransfer->getIdCompanyOrFail()));
    }

    public function testMembershipChangesChangeNeitherTheCompanyNorTheGroup(): void
    {
        // Arrange
        $customerTransfer = $this->tester->haveCustomer();
        $customerGroupTransfer = $this->haveCustomerGroupWithCustomers([$customerTransfer]);
        $uuid = $customerGroupTransfer->getUuidOrFail();
        $this->tester->setCustomerGroupCreatedAt($uuid, '2020-01-01 10:00:00');
        $companyTransfer = $this->tester->haveCompany();
        $companyRow = $this->tester->getCompanyRow($companyTransfer->getIdCompanyOrFail());
        $customerGroupRow = $this->tester->getCustomerGroupRow($uuid);

        // Act
        $responses = [
            $this->assignCompanies($uuid, [$companyTransfer->getUuidOrFail()]),
            $this->replaceCompanies($uuid, [static::ATTRIBUTE_COMPANY_UUIDS => [$companyTransfer->getUuidOrFail()]]),
            $this->removeCompany($uuid, $companyTransfer->getUuidOrFail()),
        ];

        // Assert
        foreach ($responses as $response) {
            $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode(), (string)$response->getContent());
        }

        $this->assertSame($companyRow, $this->tester->getCompanyRow($companyTransfer->getIdCompanyOrFail()));
        $this->assertSame($customerGroupRow, $this->tester->getCustomerGroupRow($uuid));
        $this->assertSame([$customerTransfer->getCustomerReferenceOrFail()], $this->tester->getAssignedCustomerReferences($uuid));
    }

    /**
     * @param list<string> $companyUuids
     */
    protected function assignCompanies(string $uuid, array $companyUuids): Response
    {
        return $this->handleApiRequest(
            'POST',
            $this->tester->getCustomerGroupCompanyCollectionUrl($uuid),
            $this->tester->buildCustomerGroupCompaniesRequestBody([static::ATTRIBUTE_COMPANY_UUIDS => $companyUuids]),
        );
    }

    /**
     * @param array<string, mixed> $attributes
     */
    protected function replaceCompanies(string $uuid, array $attributes): Response
    {
        return $this->handleApiRequest(
            'PATCH',
            $this->tester->getCustomerGroupCompanyCollectionUrl($uuid),
            $this->tester->buildCustomerGroupCompaniesRequestBody($attributes),
        );
    }

    protected function removeCompany(string $uuid, string $companyUuid): Response
    {
        return $this->handleApiRequest('DELETE', $this->tester->getCustomerGroupCompanyUrl($uuid, $companyUuid));
    }

    /**
     * @return list<array{0: string, 1: string}>
     */
    protected function getErrorCodesAndDetails(Response $response): array
    {
        return array_map(
            fn (array $error): array => [(string)$error[static::JSON_API_KEY_CODE], (string)$error[static::JSON_API_KEY_DETAIL]],
            $this->decodeJsonApi($response)[static::JSON_API_KEY_ERRORS],
        );
    }
}
