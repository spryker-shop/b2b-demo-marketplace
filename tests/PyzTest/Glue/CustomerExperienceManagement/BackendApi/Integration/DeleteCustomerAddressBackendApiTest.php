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
 * `DELETE /customers/{customerReference}/addresses/{uuid}` over a booted GLUE_BACKEND kernel.
 *
 * Unlike a customer, an address is really removed rather than anonymized.
 *
 * Ported from DeleteCustomerAddressBackendJsonApiCest. The address that gets deleted is provisioned
 * first so it holds both defaults, which is what makes the last case — that the defaults are not
 * reassigned to the survivor — observable.
 *
 * Auto-generated group annotations
 *
 * @group PyzTest
 * @group Glue
 * @group CustomerExperienceManagement
 * @group BackendApi
 * @group Integration
 * @group DeleteCustomerAddressBackendApiTest
 * Add your own group annotations below this line
 */
class DeleteCustomerAddressBackendApiTest extends AbstractCustomerExperienceManagementBackendApiTestCase
{
    protected const int REMAINING_ADDRESS_COUNT = 1;

    public function testGivenAnUnknownUuidWhenDeleteAddressThenItRespondsNotFound(): void
    {
        // Arrange
        $customerTransfer = $this->tester->haveAddresseeCustomer();
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest(
            'DELETE',
            $this->tester->getCustomerAddressUrl(
                $customerTransfer->getCustomerReferenceOrFail(),
                static::UNKNOWN_UUID,
            ),
        );

        // Assert
        $this->assertRespondsWithErrorCode(
            $response,
            Response::HTTP_NOT_FOUND,
            CustomerExperienceManagementConfig::RESPONSE_CODE_ADDRESS_NOT_FOUND,
        );
    }

    public function testGivenAnUnknownCustomerReferenceWhenDeleteAddressThenItRespondsNotFound(): void
    {
        // Arrange
        [, $addressTransfer] = $this->tester->haveCustomerWithTwoAddresses();
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest(
            'DELETE',
            $this->tester->getCustomerAddressUrl(
                static::UNKNOWN_CUSTOMER_REFERENCE,
                $addressTransfer->getUuidOrFail(),
            ),
        );

        // Assert
        $this->assertRespondsWithErrorCode(
            $response,
            Response::HTTP_NOT_FOUND,
            CustomerExperienceManagementConfig::RESPONSE_CODE_CUSTOMER_NOT_FOUND,
        );
    }

    public function testGivenNoAuthenticationWhenDeleteAddressThenItRespondsUnauthorized(): void
    {
        // Arrange
        [$customerTransfer, $addressTransfer] = $this->tester->haveCustomerWithTwoAddresses();

        // Act — intentionally unauthenticated.
        $response = $this->handleApiRequest(
            'DELETE',
            $this->tester->getCustomerAddressUrl(
                $customerTransfer->getCustomerReferenceOrFail(),
                $addressTransfer->getUuidOrFail(),
            ),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_UNAUTHORIZED);
    }

    public function testGivenAnExistingAddressWhenDeleteThenItRespondsNoContent(): void
    {
        // Arrange
        [$customerReference, $deletableUuid] = $this->haveCustomerWithTwoApiCreatedAddresses();

        // Act
        $response = $this->handleApiRequest(
            'DELETE',
            $this->tester->getCustomerAddressUrl($customerReference, $deletableUuid),
        );

        // Assert — a successful delete answers with no content rather than the deleted resource.
        $this->assertRespondsWithStatus($response, Response::HTTP_NO_CONTENT);
    }

    public function testGivenADeletedAddressWhenGetByUuidThenItIsNoLongerReadable(): void
    {
        // Arrange
        [$customerReference, $deletableUuid] = $this->haveCustomerWithTwoApiCreatedAddresses();
        $this->deleteAddress($customerReference, $deletableUuid);

        // Act
        $response = $this->handleApiRequest(
            'GET',
            $this->tester->getCustomerAddressUrl($customerReference, $deletableUuid),
        );

        // Assert
        $this->assertRespondsWithErrorCode(
            $response,
            Response::HTTP_NOT_FOUND,
            CustomerExperienceManagementConfig::RESPONSE_CODE_ADDRESS_NOT_FOUND,
        );
    }

    public function testGivenADeletedAddressWhenGetCollectionThenOnlyTheSurvivorIsListed(): void
    {
        // Arrange
        [$customerReference, $deletableUuid, $survivingUuid] = $this->haveCustomerWithTwoApiCreatedAddresses();
        $this->deleteAddress($customerReference, $deletableUuid);

        // Act
        $response = $this->handleApiRequest(
            'GET',
            $this->tester->getCustomerAddressCollectionUrl($customerReference),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertSame(
            [$survivingUuid],
            $this->getResourceIds($response),
            'The row is removed rather than flagged, so the deleted uuid is absent from the collection.',
        );
        $this->assertCount(static::REMAINING_ADDRESS_COUNT, $this->getResourceIds($response));
    }

    public function testGivenTheDefaultAddressIsDeletedWhenReadingTheSurvivorThenItWasNotPromoted(): void
    {
        // Arrange
        [$customerReference, $deletableUuid, $survivingUuid] = $this->haveCustomerWithTwoApiCreatedAddresses();
        $this->deleteAddress($customerReference, $deletableUuid);

        // Act
        $response = $this->handleApiRequest(
            'GET',
            $this->tester->getCustomerAddressUrl($customerReference, $survivingUuid),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertAttributesMatch(
            [
                AddressTransfer::IS_DEFAULT_BILLING => false,
                AddressTransfer::IS_DEFAULT_SHIPPING => false,
            ],
            $this->getResourceAttributes($response),
            'Deleting the default address does not promote the surviving address in its place.',
        );
    }

    /**
     * The first address is created first so it holds both defaults; it is the one the delete cases
     * remove.
     *
     * @return array{0: string, 1: string, 2: string} customerReference, deletable uuid, surviving uuid
     */
    protected function haveCustomerWithTwoApiCreatedAddresses(): array
    {
        $customerTransfer = $this->tester->haveAddresseeCustomer();
        $this->tester->actingAsUser();
        $customerReference = $customerTransfer->getCustomerReferenceOrFail();

        return [
            $customerReference,
            $this->haveAddressViaApi($customerReference, [
                AddressTransfer::CITY => $this->tester->getFirstAddressCity(),
            ]),
            $this->haveAddressViaApi($customerReference, [
                AddressTransfer::CITY => $this->tester->getSecondAddressCity(),
            ]),
        ];
    }

    protected function deleteAddress(string $customerReference, string $uuid): void
    {
        $response = $this->handleApiRequest(
            'DELETE',
            $this->tester->getCustomerAddressUrl($customerReference, $uuid),
        );

        $this->assertSame(
            Response::HTTP_NO_CONTENT,
            $response->getStatusCode(),
            sprintf('Could not delete the address under test: %s', (string)$response->getContent()),
        );
    }
}
