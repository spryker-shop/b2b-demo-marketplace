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
 * @group CustomerGroupCompaniesBackendApiTest
 * Add your own group annotations below this line
 */
class CustomerGroupCompaniesBackendApiTest extends AbstractCustomerGroupBackendApiTestCase
{
    protected const string RELATIONSHIP_COMPANIES = 'companies';

    protected const string NAME_ALPHA = 'Alpha Industries';

    protected const string NAME_BRAVO = 'Bravo Trading';

    protected const string FILTER_NAME = 'customer-group-companies.name';

    public function testListsTheCompaniesOfTheGroupWithTheirUuidAndNameOnly(): void
    {
        // Arrange
        $firstCompanyTransfer = $this->tester->haveCompany();
        $secondCompanyTransfer = $this->tester->haveCompany();
        $customerGroupTransfer = $this->haveCustomerGroupWithCompanies([$firstCompanyTransfer, $secondCompanyTransfer]);
        $this->haveCustomerGroupWithCompanies([$this->tester->haveCompany()]);

        // Act
        $response = $this->tester->handleApiRequest(
            'GET',
            $this->tester->getCustomerGroupCompanyCollectionUrl($customerGroupTransfer->getUuidOrFail()),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertSame(
            [$firstCompanyTransfer->getUuidOrFail(), $secondCompanyTransfer->getUuidOrFail()],
            $this->getResourceIds($response),
        );

        $this->assertSame([
            $firstCompanyTransfer->getUuidOrFail() => [
                static::ATTRIBUTE_COMPANY_UUID => $firstCompanyTransfer->getUuidOrFail(),
                static::ATTRIBUTE_COMPANY_NAME => $firstCompanyTransfer->getNameOrFail(),
            ],
            $secondCompanyTransfer->getUuidOrFail() => [
                static::ATTRIBUTE_COMPANY_UUID => $secondCompanyTransfer->getUuidOrFail(),
                static::ATTRIBUTE_COMPANY_NAME => $secondCompanyTransfer->getNameOrFail(),
            ],
        ], array_column(
            $this->decodeJsonApi($response)[static::JSON_API_KEY_DATA],
            static::JSON_API_KEY_ATTRIBUTES,
            static::JSON_API_KEY_ID,
        ));

        $this->assertSame(2, $this->getMetaPagination($response)[static::PAGINATION_KEY_NUM_FOUND]);
    }

    public function testSearchesTheCompanyNameIgnoringCase(): void
    {
        // Arrange
        $alphaCompanyTransfer = $this->haveNamedCompany(static::NAME_ALPHA);
        $customerGroupTransfer = $this->haveCustomerGroupWithCompanies([$alphaCompanyTransfer, $this->haveNamedCompany(static::NAME_BRAVO)]);

        // Act
        $response = $this->tester->handleApiRequest(
            'GET',
            $this->tester->getCustomerGroupCompanyCollectionUrl($customerGroupTransfer->getUuidOrFail(), ['q' => 'ALPHA']),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertSame([$alphaCompanyTransfer->getUuidOrFail()], $this->getResourceIds($response));
    }

    public function testSortsByNameInAStableOrderAcrossPages(): void
    {
        // Arrange
        $bravoCompanyTransfer = $this->haveNamedCompany(static::NAME_BRAVO);
        $firstAlphaCompanyTransfer = $this->haveNamedCompany(static::NAME_ALPHA);
        $secondAlphaCompanyTransfer = $this->haveNamedCompany(static::NAME_ALPHA);
        $uuid = $this->haveCustomerGroupWithCompanies([$bravoCompanyTransfer, $firstAlphaCompanyTransfer, $secondAlphaCompanyTransfer])
            ->getUuidOrFail();

        $collectionUrlBuilder = fn (array $query): string => $this->tester->getCustomerGroupCompanyCollectionUrl($uuid, $query);

        // Act
        $ascendingIds = $this->getIdsOfTwoPages($collectionUrlBuilder, 'name');
        $descendingIds = $this->getIdsOfTwoPages($collectionUrlBuilder, '-name');

        // Assert
        $this->assertSame([
            $firstAlphaCompanyTransfer->getUuidOrFail(),
            $secondAlphaCompanyTransfer->getUuidOrFail(),
            $bravoCompanyTransfer->getUuidOrFail(),
        ], $ascendingIds);
        $this->assertSame([
            $bravoCompanyTransfer->getUuidOrFail(),
            $secondAlphaCompanyTransfer->getUuidOrFail(),
            $firstAlphaCompanyTransfer->getUuidOrFail(),
        ], $descendingIds);
    }

    public function testAlignsAnOffsetThatIsNotAMultipleOfTheLimitToTheStartOfItsPage(): void
    {
        // Arrange
        $companyTransfers = [$this->tester->haveCompany(), $this->tester->haveCompany(), $this->tester->haveCompany()];
        $uuid = $this->haveCustomerGroupWithCompanies($companyTransfers)->getUuidOrFail();

        // Act
        $response = $this->tester->handleApiRequest(
            'GET',
            $this->tester->getCustomerGroupCompanyCollectionUrl($uuid, ['page' => ['limit' => 2, 'offset' => 1]]),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertSame(
            [$companyTransfers[0]->getUuidOrFail(), $companyTransfers[1]->getUuidOrFail()],
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
        $companyTransfers = [$this->tester->haveCompany(), $this->tester->haveCompany(), $this->tester->haveCompany()];
        $uuid = $this->haveCustomerGroupWithCompanies($companyTransfers)->getUuidOrFail();

        // Act
        $response = $this->tester->handleApiRequest(
            'GET',
            $this->tester->getCustomerGroupCompanyCollectionUrl($uuid, ['page' => ['limit' => 2, 'offset' => 2]]),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertSame([$companyTransfers[2]->getUuidOrFail()], $this->getResourceIds($response));
        $this->assertSame(2, $this->getMetaPagination($response)[static::PAGINATION_KEY_CURRENT_PAGE]);
        $this->assertSame(2, $this->getMetaPagination($response)[static::PAGINATION_KEY_MAX_PAGE]);
        $this->assertSame(0, $this->findLinkedPageOffset($response, static::LINK_FIRST));
        $this->assertSame(0, $this->findLinkedPageOffset($response, static::LINK_PREV));
        $this->assertSame(2, $this->findLinkedPageOffset($response, static::LINK_LAST));
        $this->assertNull($this->findLinkedPageOffset($response, static::LINK_NEXT));
    }

    public function testRejectsAnUnsupportedSortField(): void
    {
        // Arrange
        $uuid = $this->haveCustomerGroup()->getUuidOrFail();

        // Act
        $response = $this->tester->handleApiRequest(
            'GET',
            $this->tester->getCustomerGroupCompanyCollectionUrl($uuid, ['sort' => static::ATTRIBUTE_COMPANY_UUID]),
        );

        // Assert
        $this->assertRespondsWithErrorCode(
            $response,
            Response::HTTP_BAD_REQUEST,
            CustomerExperienceManagementConfig::RESPONSE_CODE_INVALID_SORT_FIELD,
        );
    }

    public function testRejectsAFilterBecauseTheCollectionHasNone(): void
    {
        // Arrange
        $uuid = $this->haveCustomerGroup()->getUuidOrFail();

        // Act
        $response = $this->tester->handleApiRequest(
            'GET',
            $this->tester->getCustomerGroupCompanyCollectionUrl($uuid, ['filter' => [static::FILTER_NAME => static::NAME_ALPHA]]),
        );

        // Assert
        $this->assertRespondsWithErrorCode(
            $response,
            Response::HTTP_BAD_REQUEST,
            CustomerExperienceManagementConfig::RESPONSE_CODE_UNSUPPORTED_FILTER_FIELD,
        );
    }

    public function testReturnsNotFoundForAnUnknownGroup(): void
    {
        // Act
        $response = $this->tester->handleApiRequest(
            'GET',
            $this->tester->getCustomerGroupCompanyCollectionUrl(static::UNKNOWN_WELL_FORMED_UUID),
        );

        // Assert
        $this->assertRespondsWithErrorCode(
            $response,
            Response::HTTP_NOT_FOUND,
            CustomerExperienceManagementConfig::RESPONSE_CODE_CUSTOMER_GROUP_NOT_FOUND,
        );
    }

    public function testDoesNotReadOneCompanyMembership(): void
    {
        // Arrange
        $companyTransfer = $this->tester->haveCompany();
        $uuid = $this->haveCustomerGroupWithCompanies([$companyTransfer])->getUuidOrFail();

        // Act
        $response = $this->tester->handleApiRequest(
            'GET',
            $this->tester->getCustomerGroupCompanyUrl($uuid, $companyTransfer->getUuidOrFail()),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_METHOD_NOT_ALLOWED);
    }

    public function testIncludesTheOwnCompanyOfEachMembership(): void
    {
        // Arrange
        $firstCompanyTransfer = $this->tester->haveCompany();
        $secondCompanyTransfer = $this->tester->haveCompany();
        $uuid = $this->haveCustomerGroupWithCompanies([$firstCompanyTransfer, $secondCompanyTransfer])->getUuidOrFail();

        // Act
        $response = $this->tester->handleApiRequest(
            'GET',
            $this->tester->getCustomerGroupCompanyCollectionUrl($uuid, ['include' => static::RELATIONSHIP_COMPANIES]),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);

        foreach ($this->decodeJsonApi($response)[static::JSON_API_KEY_DATA] as $member) {
            $this->assertSame(
                [$member[static::JSON_API_KEY_ID]],
                array_column($member[static::JSON_API_KEY_RELATIONSHIPS][static::RELATIONSHIP_COMPANIES][static::JSON_API_KEY_DATA], static::JSON_API_KEY_ID),
            );
        }

        $this->assertSame(
            [$firstCompanyTransfer->getUuidOrFail(), $secondCompanyTransfer->getUuidOrFail()],
            array_column($this->findIncludedResources($response, static::RELATIONSHIP_COMPANIES), static::JSON_API_KEY_ID),
        );
    }

    public function testRespondsUnauthorizedWithoutAValidToken(): void
    {
        // Arrange
        $uuid = $this->haveCustomerGroup()->getUuidOrFail();
        $this->tester->actingWithInvalidToken();

        // Act
        $response = $this->tester->handleApiRequest('GET', $this->tester->getCustomerGroupCompanyCollectionUrl($uuid));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_UNAUTHORIZED);
    }

    public function testRespondsForbiddenWithoutAccessToTheResource(): void
    {
        // Arrange
        $uuid = $this->haveCustomerGroup()->getUuidOrFail();
        $this->tester->actingAsUserWithoutAclAccess();

        // Act
        $response = $this->tester->handleApiRequest('GET', $this->tester->getCustomerGroupCompanyCollectionUrl($uuid));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_FORBIDDEN);
    }
}
