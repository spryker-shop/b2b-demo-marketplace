<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace PyzTest\Glue\CustomerExperienceManagement\BackendApi\Integration;

use Generated\Shared\Transfer\AddressTransfer;
use Generated\Shared\Transfer\CustomerTransfer;
use PyzTest\Glue\CustomerExperienceManagement\AbstractCustomerExperienceManagementBackendApiTestCase;
use SprykerFeature\Glue\CustomerExperienceManagement\CustomerExperienceManagementConfig;
use Symfony\Component\HttpFoundation\Response;

/**
 * `GET /customers/{customerReference}/addresses/{uuid}` over a booted GLUE_BACKEND kernel.
 *
 * Ported from GetSingleCustomerAddressBackendJsonApiCest.
 *
 * Auto-generated group annotations
 *
 * @group PyzTest
 * @group Glue
 * @group CustomerExperienceManagement
 * @group BackendApi
 * @group Integration
 * @group GetSingleCustomerAddressBackendApiTest
 * Add your own group annotations below this line
 */
class GetSingleCustomerAddressBackendApiTest extends AbstractCustomerExperienceManagementBackendApiTestCase
{
    protected const string RESOURCE_PROPERTY_ID_CUSTOMER_ADDRESS = 'idCustomerAddress';

    public function testGivenAnExistingAddressWhenGetByUuidThenTheAddressIsReturned(): void
    {
        // Arrange
        [$customerTransfer, $addressTransfer] = $this->tester->haveCustomerWithTwoAddresses();
        $this->tester->actingAsUser();
        $customerReference = $customerTransfer->getCustomerReferenceOrFail();

        // Act
        $response = $this->handleApiRequest(
            'GET',
            $this->tester->getCustomerAddressUrl($customerReference, $addressTransfer->getUuidOrFail()),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);

        $payload = $this->decodeJsonApi($response)[static::JSON_API_KEY_DATA];
        $this->assertSame(
            $addressTransfer->getUuidOrFail(),
            $payload[static::JSON_API_KEY_ID],
            'The uuid is the public identifier of the address.',
        );
        $this->assertAttributesMatch(
            [
                AddressTransfer::UUID => $addressTransfer->getUuidOrFail(),
                AddressTransfer::CITY => $addressTransfer->getCityOrFail(),
                AddressTransfer::ZIP_CODE => $addressTransfer->getZipCodeOrFail(),
                AddressTransfer::FIRST_NAME => $addressTransfer->getFirstNameOrFail(),
                AddressTransfer::LAST_NAME => $addressTransfer->getLastNameOrFail(),
                CustomerTransfer::CUSTOMER_REFERENCE => $customerReference,
            ],
            $this->getResourceAttributes($response),
        );
        $this->assertStringEndsWith(
            $this->tester->getCustomerAddressUrl($customerReference, $addressTransfer->getUuidOrFail()),
            (string)($payload[static::JSON_API_KEY_LINKS][static::JSON_API_KEY_SELF] ?? ''),
        );
    }

    public function testGivenAnExistingAddressWhenGetByUuidThenTheCountryNameIsDerivedFromTheCode(): void
    {
        // Arrange
        [$customerTransfer, $addressTransfer] = $this->tester->haveCustomerWithTwoAddresses();
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest(
            'GET',
            $this->tester->getCustomerAddressUrl(
                $customerTransfer->getCustomerReferenceOrFail(),
                $addressTransfer->getUuidOrFail(),
            ),
        );

        // Assert — the stored country relation is flattened to its name for the API.
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertAttributesMatch(
            [
                AddressTransfer::ISO2_CODE => $this->tester->getIso2Code(),
                AddressTransfer::COUNTRY => $this->tester->getCountryName(),
            ],
            $this->getResourceAttributes($response),
        );
    }

    public function testGivenAnExistingAddressWhenGetByUuidThenTheDatabaseIdIsNotExposed(): void
    {
        // Arrange
        [$customerTransfer, $addressTransfer] = $this->tester->haveCustomerWithTwoAddresses();
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest(
            'GET',
            $this->tester->getCustomerAddressUrl(
                $customerTransfer->getCustomerReferenceOrFail(),
                $addressTransfer->getUuidOrFail(),
            ),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertStringNotContainsString(
            static::RESOURCE_PROPERTY_ID_CUSTOMER_ADDRESS,
            (string)$response->getContent(),
            'The surrogate key stays internal — addresses are addressed by uuid alone.',
        );
    }

    public function testGivenAnUnknownUuidWhenGetAddressThenItRespondsNotFound(): void
    {
        // Arrange
        $customerTransfer = $this->tester->haveAddresseeCustomer();
        $this->tester->actingAsUser();
        $customerReference = $customerTransfer->getCustomerReferenceOrFail();

        // Act
        $response = $this->handleApiRequest(
            'GET',
            $this->tester->getCustomerAddressUrl($customerReference, static::UNKNOWN_UUID),
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
                static::UNKNOWN_UUID,
                $customerReference,
            ),
            $this->getErrorDetails($response),
        );
    }

    public function testGivenAnUnknownCustomerReferenceWhenGetAddressThenItRespondsNotFound(): void
    {
        // Arrange
        [, $addressTransfer] = $this->tester->haveCustomerWithTwoAddresses();
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest(
            'GET',
            $this->tester->getCustomerAddressUrl(
                static::UNKNOWN_CUSTOMER_REFERENCE,
                $addressTransfer->getUuidOrFail(),
            ),
        );

        // Assert — an unknown customer is reported before the address is looked up.
        $this->assertRespondsWithErrorCode(
            $response,
            Response::HTTP_NOT_FOUND,
            CustomerExperienceManagementConfig::RESPONSE_CODE_CUSTOMER_NOT_FOUND,
        );
    }

    public function testGivenNoAuthenticationWhenGetAddressThenItRespondsUnauthorized(): void
    {
        // Arrange
        [$customerTransfer, $addressTransfer] = $this->tester->haveCustomerWithTwoAddresses();

        // Act — intentionally unauthenticated.
        $response = $this->handleApiRequest(
            'GET',
            $this->tester->getCustomerAddressUrl(
                $customerTransfer->getCustomerReferenceOrFail(),
                $addressTransfer->getUuidOrFail(),
            ),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_UNAUTHORIZED);
    }
}
