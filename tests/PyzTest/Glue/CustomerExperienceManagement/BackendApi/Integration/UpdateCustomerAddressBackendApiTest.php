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
 * `PATCH /customers/{customerReference}/addresses/{uuid}` over a booted GLUE_BACKEND kernel.
 *
 * Ported from UpdateCustomerAddressBackendJsonApiCest. The default-address cases provision their
 * addresses through the API, because promotion is assigned by the write path they exercise.
 *
 * Auto-generated group annotations
 *
 * @group PyzTest
 * @group Glue
 * @group CustomerExperienceManagement
 * @group BackendApi
 * @group Integration
 * @group UpdateCustomerAddressBackendApiTest
 * Add your own group annotations below this line
 */
class UpdateCustomerAddressBackendApiTest extends AbstractCustomerExperienceManagementBackendApiTestCase
{
    protected const string UPDATED_CITY = 'Hamburg';

    protected const string INVALID_SALUTATION = 'Captain';

    protected const string COUNTRY_NAME_FROM_PAYLOAD = 'Atlantis';

    public function testGivenASingleAttributeWhenUpdateAddressThenItIsAppliedAndTheRestIsKept(): void
    {
        // Arrange
        [$customerTransfer, $addressTransfer] = $this->tester->haveCustomerWithTwoAddresses();
        $this->tester->actingAsUser();
        $uuid = $addressTransfer->getUuidOrFail();

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getCustomerAddressUrl($customerTransfer->getCustomerReferenceOrFail(), $uuid),
            $this->tester->buildCustomerAddressRequestBody(
                [AddressTransfer::CITY => static::UPDATED_CITY],
                $uuid,
            ),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertAttributesMatch(
            [AddressTransfer::CITY => static::UPDATED_CITY],
            $this->getResourceAttributes($response),
            'The supplied attribute is applied.',
        );
        $this->assertAttributesMatch(
            [
                AddressTransfer::FIRST_NAME => $addressTransfer->getFirstNameOrFail(),
                AddressTransfer::LAST_NAME => $addressTransfer->getLastNameOrFail(),
                AddressTransfer::ZIP_CODE => $addressTransfer->getZipCodeOrFail(),
                AddressTransfer::ADDRESS1 => $addressTransfer->getAddress1OrFail(),
            ],
            $this->getResourceAttributes($response),
            'Attributes the request omitted keep their stored value rather than being blanked.',
        );
    }

    public function testGivenAnUpdatedAddressWhenReadBackThenTheChangeWasPersisted(): void
    {
        // Arrange
        [$customerTransfer, $addressTransfer] = $this->tester->haveCustomerWithTwoAddresses();
        $this->tester->actingAsUser();
        $customerReference = $customerTransfer->getCustomerReferenceOrFail();
        $uuid = $addressTransfer->getUuidOrFail();

        $updateResponse = $this->handleApiRequest(
            'PATCH',
            $this->tester->getCustomerAddressUrl($customerReference, $uuid),
            $this->tester->buildCustomerAddressRequestBody([AddressTransfer::CITY => static::UPDATED_CITY], $uuid),
        );
        $this->assertRespondsWithStatus($updateResponse, Response::HTTP_OK);

        // Act — read the address back rather than trusting the write response.
        $response = $this->handleApiRequest('GET', $this->tester->getCustomerAddressUrl($customerReference, $uuid));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertAttributesMatch(
            [AddressTransfer::CITY => static::UPDATED_CITY],
            $this->getResourceAttributes($response),
        );
    }

    public function testGivenACountryInThePayloadWhenUpdateAddressThenTheReadOnlyCountryIsIgnored(): void
    {
        // Arrange
        [$customerTransfer, $addressTransfer] = $this->tester->haveCustomerWithTwoAddresses();
        $this->tester->actingAsUser();
        $uuid = $addressTransfer->getUuidOrFail();

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getCustomerAddressUrl($customerTransfer->getCustomerReferenceOrFail(), $uuid),
            $this->tester->buildCustomerAddressRequestBody(
                [AddressTransfer::COUNTRY => static::COUNTRY_NAME_FROM_PAYLOAD],
                $uuid,
            ),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertAttributesMatch(
            [AddressTransfer::COUNTRY => $this->tester->getCountryName()],
            $this->getResourceAttributes($response),
            'The country is derived from the country code, so a payload country cannot overwrite it.',
        );
    }

    public function testGivenASecondAddressWhenPromotedToBillingDefaultThenItReportsItself(): void
    {
        // Arrange
        [$customerReference, , $secondUuid] = $this->haveCustomerWithTwoApiCreatedAddresses();

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getCustomerAddressUrl($customerReference, $secondUuid),
            $this->tester->buildCustomerAddressRequestBody(
                [AddressTransfer::IS_DEFAULT_BILLING => true],
                $secondUuid,
            ),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertAttributesMatch(
            [
                AddressTransfer::IS_DEFAULT_BILLING => true,
                AddressTransfer::IS_DEFAULT_SHIPPING => false,
            ],
            $this->getResourceAttributes($response),
            'The promoted address takes the billing default without claiming the shipping default.',
        );
    }

    public function testGivenAPromotedBillingDefaultWhenReadingThePreviousOneThenItWasDemoted(): void
    {
        // Arrange
        [$customerReference, $firstUuid, $secondUuid] = $this->haveCustomerWithTwoApiCreatedAddresses();

        $promoteResponse = $this->handleApiRequest(
            'PATCH',
            $this->tester->getCustomerAddressUrl($customerReference, $secondUuid),
            $this->tester->buildCustomerAddressRequestBody(
                [AddressTransfer::IS_DEFAULT_BILLING => true],
                $secondUuid,
            ),
        );
        $this->assertRespondsWithStatus($promoteResponse, Response::HTTP_OK);

        // Act
        $response = $this->handleApiRequest(
            'GET',
            $this->tester->getCustomerAddressUrl($customerReference, $firstUuid),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertAttributesMatch(
            [
                AddressTransfer::IS_DEFAULT_BILLING => false,
                AddressTransfer::IS_DEFAULT_SHIPPING => true,
            ],
            $this->getResourceAttributes($response),
            'The address that held the billing default lost only that one — shipping is untouched.',
        );
    }

    public function testGivenASalutationOutsideTheAllowedChoicesWhenUpdateAddressThenItIsRejected(): void
    {
        // Arrange
        [$customerTransfer, $addressTransfer] = $this->tester->haveCustomerWithTwoAddresses();
        $this->tester->actingAsUser();
        $uuid = $addressTransfer->getUuidOrFail();

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getCustomerAddressUrl($customerTransfer->getCustomerReferenceOrFail(), $uuid),
            $this->tester->buildCustomerAddressRequestBody(
                [AddressTransfer::SALUTATION => static::INVALID_SALUTATION],
                $uuid,
            ),
        );

        // Assert
        $this->assertRespondsWithErrorCode(
            $response,
            Response::HTTP_UNPROCESSABLE_ENTITY,
            static::RESPONSE_CODE_VALIDATION,
        );
    }

    public function testGivenAnUnknownUuidWhenUpdateAddressThenItRespondsNotFound(): void
    {
        // Arrange
        $customerTransfer = $this->tester->haveAddresseeCustomer();
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getCustomerAddressUrl(
                $customerTransfer->getCustomerReferenceOrFail(),
                static::UNKNOWN_UUID,
            ),
            $this->tester->buildCustomerAddressRequestBody(
                [AddressTransfer::CITY => static::UPDATED_CITY],
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

    public function testGivenAnUnknownCustomerReferenceWhenUpdateAddressThenItRespondsNotFound(): void
    {
        // Arrange
        [, $addressTransfer] = $this->tester->haveCustomerWithTwoAddresses();
        $this->tester->actingAsUser();
        $uuid = $addressTransfer->getUuidOrFail();

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getCustomerAddressUrl(static::UNKNOWN_CUSTOMER_REFERENCE, $uuid),
            $this->tester->buildCustomerAddressRequestBody([AddressTransfer::CITY => static::UPDATED_CITY], $uuid),
        );

        // Assert
        $this->assertRespondsWithErrorCode(
            $response,
            Response::HTTP_NOT_FOUND,
            CustomerExperienceManagementConfig::RESPONSE_CODE_CUSTOMER_NOT_FOUND,
        );
    }

    public function testGivenNoAuthenticationWhenUpdateAddressThenItRespondsUnauthorized(): void
    {
        // Arrange
        [$customerTransfer, $addressTransfer] = $this->tester->haveCustomerWithTwoAddresses();
        $uuid = $addressTransfer->getUuidOrFail();

        // Act — intentionally unauthenticated.
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getCustomerAddressUrl($customerTransfer->getCustomerReferenceOrFail(), $uuid),
            $this->tester->buildCustomerAddressRequestBody([AddressTransfer::CITY => static::UPDATED_CITY], $uuid),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_UNAUTHORIZED);
    }

    /**
     * A customer whose two addresses were created through the API, so the first one holds both
     * defaults and the promotion rules are observable.
     *
     * @return array{0: string, 1: string, 2: string} customerReference, first uuid, second uuid
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
}
