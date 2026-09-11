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
 * Address ownership: an address reached through a customer that does not own it.
 *
 * Every operation must answer not-found rather than forbidden, and must neither disclose the
 * address nor apply the write. Not-found over forbidden is deliberate — a 403 would confirm the
 * uuid exists to a client probing another customer's URI.
 *
 * Ported from CustomerAddressOwnershipBackendJsonApiCest.
 *
 * Auto-generated group annotations
 *
 * @group PyzTest
 * @group Glue
 * @group CustomerExperienceManagement
 * @group BackendApi
 * @group Integration
 * @group CustomerAddressOwnershipBackendApiTest
 * Add your own group annotations below this line
 */
class CustomerAddressOwnershipBackendApiTest extends AbstractCustomerExperienceManagementBackendApiTestCase
{
    protected const string CITY_OWNED = 'Aachen';

    protected const string CITY_FROM_FOREIGN_REQUEST = 'Hamburg';

    public function testGivenAnOwnedAddressWhenTheOwnerReadsItThenItIsReturned(): void
    {
        // Arrange
        [$ownerReference, , $uuid] = $this->haveOwnedAddress();

        // Act
        $response = $this->handleApiRequest(
            'GET',
            $this->tester->getCustomerAddressUrl($ownerReference, $uuid),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertSame(
            $uuid,
            $this->decodeJsonApi($response)[static::JSON_API_KEY_DATA][static::JSON_API_KEY_ID],
        );
    }

    public function testGivenAnOwnedAddressWhenReadThroughTheWrongCustomerThenItRespondsNotFound(): void
    {
        // Arrange
        [, $wrongOwnerReference, $uuid] = $this->haveOwnedAddress();

        // Act
        $response = $this->handleApiRequest(
            'GET',
            $this->tester->getCustomerAddressUrl($wrongOwnerReference, $uuid),
        );

        // Assert
        $this->assertRespondsWithErrorCode(
            $response,
            Response::HTTP_NOT_FOUND,
            CustomerExperienceManagementConfig::RESPONSE_CODE_ADDRESS_NOT_FOUND,
        );
        $this->assertContains(
            sprintf(
                CustomerExperienceManagementConfig::RESPONSE_DETAILS_ADDRESS_NOT_FOUND,
                $uuid,
                $wrongOwnerReference,
            ),
            $this->getErrorDetails($response),
        );
    }

    public function testGivenAnOwnedAddressWhenReadThroughTheWrongCustomerThenNothingIsRevealed(): void
    {
        // Arrange
        [$ownerReference, $wrongOwnerReference, $uuid] = $this->haveOwnedAddress();

        // Act
        $response = $this->handleApiRequest(
            'GET',
            $this->tester->getCustomerAddressUrl($wrongOwnerReference, $uuid),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_NOT_FOUND);

        $body = (string)$response->getContent();
        $this->assertStringNotContainsString(
            static::CITY_OWNED,
            $body,
            'The rejection carries no address data.',
        );
        $this->assertStringNotContainsString(
            $ownerReference,
            $body,
            'The rejection does not name the true owner.',
        );
    }

    public function testGivenAnOwnedAddressWhenUpdatedThroughTheWrongCustomerThenItRespondsNotFound(): void
    {
        // Arrange
        [, $wrongOwnerReference, $uuid] = $this->haveOwnedAddress();

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getCustomerAddressUrl($wrongOwnerReference, $uuid),
            $this->tester->buildCustomerAddressRequestBody(
                [AddressTransfer::CITY => static::CITY_FROM_FOREIGN_REQUEST],
                $uuid,
            ),
        );

        // Assert
        $this->assertRespondsWithErrorCode(
            $response,
            Response::HTTP_NOT_FOUND,
            CustomerExperienceManagementConfig::RESPONSE_CODE_ADDRESS_NOT_FOUND,
        );
    }

    public function testGivenARejectedForeignUpdateWhenTheOwnerReadsTheAddressThenItIsUnchanged(): void
    {
        // Arrange
        [$ownerReference, $wrongOwnerReference, $uuid] = $this->haveOwnedAddress();

        $rejectedResponse = $this->handleApiRequest(
            'PATCH',
            $this->tester->getCustomerAddressUrl($wrongOwnerReference, $uuid),
            $this->tester->buildCustomerAddressRequestBody(
                [AddressTransfer::CITY => static::CITY_FROM_FOREIGN_REQUEST],
                $uuid,
            ),
        );
        $this->assertRespondsWithStatus($rejectedResponse, Response::HTTP_NOT_FOUND);

        // Act
        $response = $this->handleApiRequest(
            'GET',
            $this->tester->getCustomerAddressUrl($ownerReference, $uuid),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertAttributesMatch(
            [AddressTransfer::CITY => static::CITY_OWNED],
            $this->getResourceAttributes($response),
            'The rejected update did not reach the database.',
        );
    }

    public function testGivenAnOwnedAddressWhenDeletedThroughTheWrongCustomerThenItRespondsNotFound(): void
    {
        // Arrange
        [, $wrongOwnerReference, $uuid] = $this->haveOwnedAddress();

        // Act
        $response = $this->handleApiRequest(
            'DELETE',
            $this->tester->getCustomerAddressUrl($wrongOwnerReference, $uuid),
        );

        // Assert
        $this->assertRespondsWithErrorCode(
            $response,
            Response::HTTP_NOT_FOUND,
            CustomerExperienceManagementConfig::RESPONSE_CODE_ADDRESS_NOT_FOUND,
        );
    }

    public function testGivenARejectedForeignDeleteWhenTheOwnerReadsTheAddressThenItIsStillThere(): void
    {
        // Arrange
        [$ownerReference, $wrongOwnerReference, $uuid] = $this->haveOwnedAddress();

        $rejectedResponse = $this->handleApiRequest(
            'DELETE',
            $this->tester->getCustomerAddressUrl($wrongOwnerReference, $uuid),
        );
        $this->assertRespondsWithStatus($rejectedResponse, Response::HTTP_NOT_FOUND);

        // Act
        $response = $this->handleApiRequest(
            'GET',
            $this->tester->getCustomerAddressUrl($ownerReference, $uuid),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertSame(
            $uuid,
            $this->decodeJsonApi($response)[static::JSON_API_KEY_DATA][static::JSON_API_KEY_ID],
            'The rejected delete did not remove the row.',
        );
    }

    /**
     * @return array{0: string, 1: string, 2: string} owner reference, wrong-owner reference, address uuid
     */
    protected function haveOwnedAddress(): array
    {
        $ownerTransfer = $this->tester->haveAddresseeCustomer();
        $wrongOwnerTransfer = $this->tester->haveAddresseeCustomer();
        $this->tester->actingAsUser();

        $ownerReference = $ownerTransfer->getCustomerReferenceOrFail();

        return [
            $ownerReference,
            $wrongOwnerTransfer->getCustomerReferenceOrFail(),
            $this->haveAddressViaApi($ownerReference, [AddressTransfer::CITY => static::CITY_OWNED]),
        ];
    }
}
