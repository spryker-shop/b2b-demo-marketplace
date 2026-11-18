<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace PyzTest\Glue\CustomerExperienceManagement\BackendApi\Integration\CompanyUser;

use Generated\Shared\Transfer\CompanyUserTransfer;
use PyzTest\Glue\CustomerExperienceManagement\AbstractCustomerExperienceManagementBackendApiTestCase;
use SprykerFeature\Glue\CustomerExperienceManagement\CustomerExperienceManagementConfig;
use Symfony\Component\HttpFoundation\Response;

/**
 * `GET /company-users` over a booted GLUE_BACKEND kernel: free-text search, sorting, pagination,
 * attribute filtering, the customers relationship, and both authorization layers.
 *
 * Modelled on GetCollectionCustomersBackendApiTest. Every fixture company user is created under a
 * company this test owns, and the free-text term is unique per method, so the assertions hold
 * against the shared development database. *
 * Auto-generated group annotations
 *
 * @group PyzTest
 * @group Glue
 * @group CustomerExperienceManagement
 * @group BackendApi
 * @group Integration
 * @group CompanyUser
 * @group GetCollectionCompanyUsersBackendApiTest
 * Add your own group annotations below this line
 */
class GetCollectionCompanyUsersBackendApiTest extends AbstractCustomerExperienceManagementBackendApiTestCase
{
    protected const string UNSUPPORTED_SORT_FIELD = 'idCompanyUser';

    protected const string SORT_FIELD_IS_ACTIVE = 'isActive';

    protected const string SORT_FIELD_IS_DEFAULT = 'isDefault';

    protected const int LISTED_COMPANY_USER_COUNT = 2;

    protected const string SORT_FIELD_CUSTOMER_FIRST_NAME = 'customerFirstName';

    protected const string SORT_FIELD_CUSTOMER_FIRST_NAME_DESCENDING = '-customerFirstName';

    /**
     * The two listed customers sort the same way by first name and by email, so the two fields
     * disagree: applying both in order puts the alphabetically first customer first, while applying
     * only the trailing one reverses the pair. That makes the leading field's effect observable.
     */
    protected const string SORT_FIELDS_FIRST_NAME_THEN_EMAIL_DESCENDING = 'customerFirstName,-customerEmail';

    protected const string INCLUDE_CUSTOMERS = 'customers';

    protected const string FILTER_CUSTOMER_REFERENCE = 'company-users.customerReference';

    protected const string FILTER_COMPANY_UUID = 'company-users.companyUuid';

    protected const string FILTER_COMPANY_BUSINESS_UNIT_UUID = 'company-users.companyBusinessUnitUuid';

    protected const string FILTER_IS_ACTIVE = 'company-users.isActive';

    public function testGivenAnAuthenticatedOperatorWhenSearchByFreeTextThenOnlyTheMatchingCompanyUsersAreReturned(): void
    {
        // Arrange
        [$firstCompanyUserTransfer, $secondCompanyUserTransfer, $listedLastName] = $this->haveTwoListedCompanyUsers();
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getCompanyUserCollectionUrl([
            'q' => $listedLastName,
        ]));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);

        $uuids = $this->getResourceIds($response);
        $this->assertCount(
            static::LISTED_COMPANY_USER_COUNT,
            $uuids,
            'The free-text search isolates exactly the two company users this test created.',
        );
        $this->assertContains($firstCompanyUserTransfer->getUuidOrFail(), $uuids);
        $this->assertContains($secondCompanyUserTransfer->getUuidOrFail(), $uuids);
    }

    public function testGivenAnAuthenticatedOperatorWhenSearchCompanyUsersThenTheInternalCompanyUserIdIsNotExposed(): void
    {
        // Arrange
        [, , $listedLastName] = $this->haveTwoListedCompanyUsers();
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getCompanyUserCollectionUrl([
            'q' => $listedLastName,
        ]));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertStringNotContainsString(
            static::UNSUPPORTED_SORT_FIELD,
            (string)$response->getContent(),
            'The surrogate key stays internal — company users are addressed by uuid alone.',
        );
    }

    public function testGivenADescendingSortWhenSearchCompanyUsersThenTheOrderIsReversed(): void
    {
        // Arrange
        [, $secondCompanyUserTransfer, $listedLastName] = $this->haveTwoListedCompanyUsers();
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getCompanyUserCollectionUrl([
            'q' => $listedLastName,
            'sort' => static::SORT_FIELD_CUSTOMER_FIRST_NAME_DESCENDING,
        ]));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertSame(
            $secondCompanyUserTransfer->getUuidOrFail(),
            $this->getResourceIds($response)[0] ?? null,
            'Descending sort puts the alphabetically last customer first name first.',
        );
    }

    public function testGivenSeveralSortFieldsWhenSearchCompanyUsersThenTheLeadingFieldDecidesTheOrder(): void
    {
        // Arrange
        [$firstCompanyUserTransfer, $secondCompanyUserTransfer, $listedLastName] = $this->haveTwoListedCompanyUsers();
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getCompanyUserCollectionUrl([
            'q' => $listedLastName,
            'sort' => static::SORT_FIELDS_FIRST_NAME_THEN_EMAIL_DESCENDING,
        ]));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertSame(
            [$firstCompanyUserTransfer->getUuidOrFail(), $secondCompanyUserTransfer->getUuidOrFail()],
            $this->getResourceIds($response),
            'Every requested sort field is applied in order; the leading one is not dropped in favour of the last.',
        );
    }

    public function testGivenAPageLimitWhenSearchCompanyUsersThenOnePageAndItsPaginationMetadataAreReturned(): void
    {
        // Arrange
        [, , $listedLastName] = $this->haveTwoListedCompanyUsers();
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getCompanyUserCollectionUrl([
            'q' => $listedLastName,
            'page' => ['limit' => 1],
            'sort' => static::SORT_FIELD_CUSTOMER_FIRST_NAME,
        ]));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);

        $payload = $this->decodeJsonApi($response);
        $this->assertCount(
            1,
            $payload[static::JSON_API_KEY_DATA],
            'A limit of one truncates the page to a single company user.',
        );

        $pagination = $this->decodeJsonApi($response)[static::JSON_API_KEY_META][static::META_PAGINATION];
        $this->assertSame(
            static::LISTED_COMPANY_USER_COUNT,
            $pagination[static::PAGINATION_KEY_NUM_FOUND],
            'The pagination metadata reports both matches.',
        );
        $this->assertSame(
            static::LISTED_COMPANY_USER_COUNT,
            $pagination[static::PAGINATION_KEY_MAX_PAGE],
            'Two matches at one per page is two pages.',
        );
        $this->assertNotEmpty(
            $payload[static::JSON_API_KEY_LINKS][static::LINK_NEXT] ?? null,
            'The second page is reachable from the response links.',
        );
    }

    public function testGivenACustomerReferenceFilterWhenSearchCompanyUsersThenOnlyThatCustomersCompanyUserIsReturned(): void
    {
        // Arrange
        [$firstCompanyUserTransfer] = $this->haveTwoListedCompanyUsers();
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getCompanyUserCollectionUrl([
            'filter' => [
                static::FILTER_CUSTOMER_REFERENCE => $firstCompanyUserTransfer
                    ->getCustomerOrFail()
                    ->getCustomerReferenceOrFail(),
            ],
        ]));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertSame([$firstCompanyUserTransfer->getUuidOrFail()], $this->getResourceIds($response));
    }

    public function testGivenAnAnonymizedCustomerWhenSearchCompanyUsersThenItsCompanyUserIsNotInTheCollection(): void
    {
        // Arrange
        $companyUserTransfer = $this->haveListedCompanyUser();
        $this->tester->actingAsUser();
        $customerReference = $companyUserTransfer->getCustomerOrFail()->getCustomerReferenceOrFail();
        $this->anonymizeCustomerOfCompanyUser($companyUserTransfer);

        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getCompanyUserCollectionUrl([
            'filter' => [static::FILTER_CUSTOMER_REFERENCE => $customerReference],
        ]));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertNotContains($companyUserTransfer->getUuidOrFail(), $this->getResourceIds($response));
    }

    public function testGivenACompanyUuidFilterWhenSearchCompanyUsersThenEveryCompanyUserOfThatCompanyIsReturned(): void
    {
        // Arrange
        [$firstCompanyUserTransfer, $secondCompanyUserTransfer] = $this->haveTwoListedCompanyUsers();
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getCompanyUserCollectionUrl([
            'filter' => [
                static::FILTER_COMPANY_UUID => $firstCompanyUserTransfer->getCompanyOrFail()->getUuidOrFail(),
            ],
        ]));

        // Assert — both fixture company users belong to the same company.
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);

        $uuids = $this->getResourceIds($response);
        $this->assertContains($firstCompanyUserTransfer->getUuidOrFail(), $uuids);
        $this->assertContains($secondCompanyUserTransfer->getUuidOrFail(), $uuids);
    }

    public function testGivenABusinessUnitFilterWhenSearchCompanyUsersThenOnlyThatBusinessUnitsCompanyUserIsReturned(): void
    {
        // Arrange — the two fixture company users sit in different business units.
        [$firstCompanyUserTransfer] = $this->haveTwoListedCompanyUsers();
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getCompanyUserCollectionUrl([
            'filter' => [
                static::FILTER_COMPANY_BUSINESS_UNIT_UUID => $firstCompanyUserTransfer
                    ->getCompanyBusinessUnitOrFail()
                    ->getUuidOrFail(),
            ],
        ]));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertSame([$firstCompanyUserTransfer->getUuidOrFail()], $this->getResourceIds($response));
    }

    public function testGivenAnUnknownCompanyUuidFilterWhenSearchCompanyUsersThenTheResultIsEmptyRatherThanUnfiltered(): void
    {
        // Arrange
        $this->haveTwoListedCompanyUsers();
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getCompanyUserCollectionUrl([
            'filter' => [static::FILTER_COMPANY_UUID => static::UNKNOWN_UUID],
        ]));

        // Assert — a uuid matching nothing selects no company user rather than falling back to the
        // unfiltered collection.
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertSame([], $this->getResourceIds($response));
    }

    public function testGivenAnIsActiveFilterWhenSearchCompanyUsersThenOnlyActiveCompanyUsersAreReturned(): void
    {
        // Arrange
        [, , $listedLastName] = $this->haveTwoListedCompanyUsers();
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getCompanyUserCollectionUrl([
            'q' => $listedLastName,
            'filter' => [static::FILTER_IS_ACTIVE => 'true'],
        ]));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);

        $members = $this->getJsonApiMembers($response, static::JSON_API_KEY_DATA);
        $this->assertNotEmpty($members);

        foreach ($members as $member) {
            $this->assertTrue($member[static::JSON_API_KEY_ATTRIBUTES][CompanyUserTransfer::IS_ACTIVE]);
        }
    }

    public function testGivenTheCustomersIncludeWhenSearchCompanyUsersThenTheCustomersAreSideLoaded(): void
    {
        // Arrange
        [$firstCompanyUserTransfer, , $listedLastName] = $this->haveTwoListedCompanyUsers();
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getCompanyUserCollectionUrl([
            'q' => $listedLastName,
            'include' => static::INCLUDE_CUSTOMERS,
        ]));

        // Assert — the relationship resolves through the flat top-level customerReference.
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertContains(
            $firstCompanyUserTransfer->getCustomerOrFail()->getCustomerReferenceOrFail(),
            $this->getIncludedIds($response),
        );
    }

    /**
     * @dataProvider unsupportedSortFieldDataProvider
     */
    public function testGivenAnUnsupportedSortFieldWhenSearchCompanyUsersThenItRespondsBadRequest(string $sortField): void
    {
        // Arrange
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getCompanyUserCollectionUrl([
            'sort' => $sortField,
        ]));

        // Assert
        $this->assertRespondsWithErrorCode(
            $response,
            Response::HTTP_BAD_REQUEST,
            CustomerExperienceManagementConfig::RESPONSE_CODE_INVALID_SORT_FIELD,
        );
    }

    /**
     * @return array<string, array<string>>
     */
    public static function unsupportedSortFieldDataProvider(): array
    {
        return [
            'surrogate key' => [static::UNSUPPORTED_SORT_FIELD],
            'filterable but not sortable attribute' => [static::SORT_FIELD_IS_ACTIVE],
            'attribute the operator cannot order by' => [static::SORT_FIELD_IS_DEFAULT],
        ];
    }

    public function testGivenNoAuthenticationWhenSearchCompanyUsersThenItRespondsUnauthorized(): void
    {
        // Act — intentionally unauthenticated.
        $response = $this->handleApiRequest('GET', $this->tester->getCompanyUserCollectionUrl());

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_UNAUTHORIZED);
    }

    public function testGivenAnInvalidTokenWhenSearchCompanyUsersThenItRespondsUnauthorized(): void
    {
        // Arrange
        $this->tester->actingWithInvalidToken();

        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getCompanyUserCollectionUrl());

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_UNAUTHORIZED);
    }

    public function testGivenAnOperatorTheAclRefusesWhenSearchCompanyUsersThenItRespondsForbidden(): void
    {
        // Arrange
        $this->tester->actingAsUserWithoutAclAccess();

        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getCompanyUserCollectionUrl());

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_FORBIDDEN);
    }
}
