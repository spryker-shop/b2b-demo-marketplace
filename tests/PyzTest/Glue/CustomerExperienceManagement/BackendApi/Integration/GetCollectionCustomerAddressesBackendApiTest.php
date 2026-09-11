<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace PyzTest\Glue\CustomerExperienceManagement\BackendApi\Integration;

use Generated\Shared\Transfer\AddressTransfer;
use PyzTest\Glue\CustomerExperienceManagement\AbstractCustomerExperienceManagementBackendApiTestCase;
use SprykerFeature\Glue\CustomerExperienceManagement\CustomerExperienceManagementConfig;
use Symfony\Component\HttpFoundation\Response;

/**
 * `GET /customers/{customerReference}/addresses` over a booted GLUE_BACKEND kernel.
 *
 * Ported from GetCollectionCustomerAddressesBackendJsonApiCest.
 *
 * Auto-generated group annotations
 *
 * @group PyzTest
 * @group Glue
 * @group CustomerExperienceManagement
 * @group BackendApi
 * @group Integration
 * @group GetCollectionCustomerAddressesBackendApiTest
 * Add your own group annotations below this line
 */
class GetCollectionCustomerAddressesBackendApiTest extends AbstractCustomerExperienceManagementBackendApiTestCase
{
    /**
     * The uuid is the public identifier, but it is not a sortable column.
     */
    protected const string UNSUPPORTED_SORT_FIELD = AddressTransfer::UUID;

    protected const string RESOURCE_PROPERTY_ID_CUSTOMER_ADDRESS = 'idCustomerAddress';

    protected const int READABLE_ADDRESS_COUNT = 2;

    public function testGivenACustomerWithAddressesWhenGetCollectionThenEveryAddressIsReturned(): void
    {
        // Arrange
        [$customerTransfer, $firstAddressTransfer, $secondAddressTransfer] = $this->tester->haveCustomerWithTwoAddresses();
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest(
            'GET',
            $this->tester->getCustomerAddressCollectionUrl($customerTransfer->getCustomerReferenceOrFail()),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);

        $uuids = $this->getResourceIds($response);
        $this->assertCount(static::READABLE_ADDRESS_COUNT, $uuids);
        $this->assertContains($firstAddressTransfer->getUuidOrFail(), $uuids);
        $this->assertContains($secondAddressTransfer->getUuidOrFail(), $uuids);
    }

    public function testGivenAnotherCustomerHasAddressesWhenGetCollectionThenOnlyTheGivenCustomersAreReturned(): void
    {
        // Arrange
        [$customerTransfer] = $this->tester->haveCustomerWithTwoAddresses();
        [, $otherAddressTransfer] = $this->tester->haveCustomerWithTwoAddresses();
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest(
            'GET',
            $this->tester->getCustomerAddressCollectionUrl($customerTransfer->getCustomerReferenceOrFail()),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertNotContains(
            $otherAddressTransfer->getUuidOrFail(),
            $this->getResourceIds($response),
            'The collection is scoped to the customer in the URI, not to every address in the system.',
        );
    }

    public function testGivenACustomerWithAddressesWhenGetCollectionThenTheAddressDatabaseIdIsNotExposed(): void
    {
        // Arrange
        [$customerTransfer, $firstAddressTransfer] = $this->tester->haveCustomerWithTwoAddresses();
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest(
            'GET',
            $this->tester->getCustomerAddressCollectionUrl($customerTransfer->getCustomerReferenceOrFail()),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertContains($firstAddressTransfer->getUuidOrFail(), $this->getResourceIds($response));
        $this->assertStringNotContainsString(
            static::RESOURCE_PROPERTY_ID_CUSTOMER_ADDRESS,
            (string)$response->getContent(),
            'The surrogate key stays internal — addresses are addressed by uuid alone.',
        );
    }

    public function testGivenADescendingSortWhenGetCollectionThenTheOrderIsReversed(): void
    {
        // Arrange
        [$customerTransfer, , $secondAddressTransfer] = $this->tester->haveCustomerWithTwoAddresses();
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest(
            'GET',
            $this->tester->getCustomerAddressCollectionUrl(
                $customerTransfer->getCustomerReferenceOrFail(),
                ['sort' => '-' . AddressTransfer::FIRST_NAME],
            ),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertSame(
            $secondAddressTransfer->getUuidOrFail(),
            $this->getResourceIds($response)[0] ?? null,
            'Descending sort puts the alphabetically last first name first.',
        );
    }

    public function testGivenAPageLimitWhenGetCollectionThenOnePageAndItsPaginationMetadataAreReturned(): void
    {
        // Arrange
        [$customerTransfer] = $this->tester->haveCustomerWithTwoAddresses();
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest(
            'GET',
            $this->tester->getCustomerAddressCollectionUrl($customerTransfer->getCustomerReferenceOrFail(), [
                'page' => ['limit' => 1],
                'sort' => AddressTransfer::FIRST_NAME,
            ]),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertCount(1, $this->getResourceIds($response), 'A limit of one truncates the page.');

        $this->assertNoPaginationInsideMembers($response);

        $pagination = $this->getMetaPagination($response);
        $this->assertSame(
            static::READABLE_ADDRESS_COUNT,
            $pagination[static::PAGINATION_KEY_NUM_FOUND],
            'The pagination metadata reports both addresses.',
        );
        $this->assertSame(
            static::READABLE_ADDRESS_COUNT,
            $pagination[static::PAGINATION_KEY_MAX_PAGE],
            'Two addresses at one per page is two pages.',
        );
        $this->assertNotEmpty(
            $this->decodeJsonApi($response)[static::JSON_API_KEY_LINKS][static::LINK_NEXT] ?? null,
            'The second page of the nested route is reachable from the response links.',
        );
    }

    public function testGivenAPageOffsetWhenGetCollectionThenTheRemainingAddressIsReturned(): void
    {
        // Arrange
        [$customerTransfer, , $secondAddressTransfer] = $this->tester->haveCustomerWithTwoAddresses();
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest(
            'GET',
            $this->tester->getCustomerAddressCollectionUrl($customerTransfer->getCustomerReferenceOrFail(), [
                'page' => ['limit' => 1, 'offset' => 1],
                'sort' => AddressTransfer::FIRST_NAME,
            ]),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertSame(
            $secondAddressTransfer->getUuidOrFail(),
            $this->getResourceIds($response)[0] ?? null,
            'An offset walks the sorted collection instead of restarting it.',
        );
    }

    public function testGivenACustomerWithoutAddressesWhenGetCollectionThenAnEmptyCollectionIsReturned(): void
    {
        // Arrange
        $customerTransfer = $this->tester->haveAddresseeCustomer();
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest(
            'GET',
            $this->tester->getCustomerAddressCollectionUrl($customerTransfer->getCustomerReferenceOrFail()),
        );

        // Assert — an addressless customer is an empty collection, not a not-found.
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertSame([], $this->getResourceIds($response));
    }

    public function testGivenAnUnsupportedSortFieldWhenGetCollectionThenItRespondsBadRequest(): void
    {
        // Arrange
        [$customerTransfer] = $this->tester->haveCustomerWithTwoAddresses();
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest(
            'GET',
            $this->tester->getCustomerAddressCollectionUrl(
                $customerTransfer->getCustomerReferenceOrFail(),
                ['sort' => static::UNSUPPORTED_SORT_FIELD],
            ),
        );

        // Assert — the field reaches an SQL ORDER BY, so anything off the allow list is refused.
        $this->assertRespondsWithErrorCode(
            $response,
            Response::HTTP_BAD_REQUEST,
            CustomerExperienceManagementConfig::RESPONSE_CODE_INVALID_SORT_FIELD,
        );
    }

    public function testGivenAnUnknownCustomerReferenceWhenGetCollectionThenItRespondsNotFound(): void
    {
        // Arrange
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest(
            'GET',
            $this->tester->getCustomerAddressCollectionUrl(static::UNKNOWN_CUSTOMER_REFERENCE),
        );

        // Assert
        $this->assertRespondsWithErrorCode(
            $response,
            Response::HTTP_NOT_FOUND,
            CustomerExperienceManagementConfig::RESPONSE_CODE_CUSTOMER_NOT_FOUND,
        );
    }

    public function testGivenNoAuthenticationWhenGetCollectionThenItRespondsUnauthorized(): void
    {
        // Arrange
        [$customerTransfer] = $this->tester->haveCustomerWithTwoAddresses();

        // Act — intentionally unauthenticated.
        $response = $this->handleApiRequest(
            'GET',
            $this->tester->getCustomerAddressCollectionUrl($customerTransfer->getCustomerReferenceOrFail()),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_UNAUTHORIZED);
    }

    public function testGivenAnInvalidTokenWhenGetCollectionThenItRespondsUnauthorized(): void
    {
        // Arrange
        [$customerTransfer] = $this->tester->haveCustomerWithTwoAddresses();
        $this->tester->actingWithInvalidToken();

        // Act
        $response = $this->handleApiRequest(
            'GET',
            $this->tester->getCustomerAddressCollectionUrl($customerTransfer->getCustomerReferenceOrFail()),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_UNAUTHORIZED);
    }
}
