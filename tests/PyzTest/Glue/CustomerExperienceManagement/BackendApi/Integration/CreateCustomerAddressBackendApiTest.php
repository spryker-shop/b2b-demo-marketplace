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
 * `POST /customers/{customerReference}/addresses` over a booted GLUE_BACKEND kernel.
 *
 * Ported from CreateCustomerAddressBackendJsonApiCest, including the default-address promotion rule:
 * the first address of a customer becomes both defaults, and a later one does not steal them.
 *
 * Auto-generated group annotations
 *
 * @group PyzTest
 * @group Glue
 * @group CustomerExperienceManagement
 * @group BackendApi
 * @group Integration
 * @group CreateCustomerAddressBackendApiTest
 * Add your own group annotations below this line
 */
class CreateCustomerAddressBackendApiTest extends AbstractCustomerExperienceManagementBackendApiTestCase
{
    protected const string INVALID_SALUTATION = 'Captain';

    protected const string SECOND_ADDRESS_CITY = 'Hamburg';

    protected const int REQUIRED_ATTRIBUTE_COUNT = 8;

    public function testGivenACustomerWithoutAddressesWhenCreateThenTheAddressBecomesBothDefaults(): void
    {
        // Arrange
        $customerTransfer = $this->tester->haveAddresseeCustomer();
        $this->tester->actingAsUser();
        $customerReference = $customerTransfer->getCustomerReferenceOrFail();
        $attributes = $this->tester->buildValidCustomerAddressAttributes();

        // Act
        $response = $this->handleApiRequest(
            'POST',
            $this->tester->getCustomerAddressCollectionUrl($customerReference),
            $this->tester->buildCustomerAddressRequestBody($attributes),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_CREATED);

        $this->assertAttributesMatch(
            [
                AddressTransfer::SALUTATION => $attributes[AddressTransfer::SALUTATION],
                AddressTransfer::FIRST_NAME => $attributes[AddressTransfer::FIRST_NAME],
                AddressTransfer::LAST_NAME => $attributes[AddressTransfer::LAST_NAME],
                AddressTransfer::ADDRESS1 => $attributes[AddressTransfer::ADDRESS1],
                AddressTransfer::CITY => $attributes[AddressTransfer::CITY],
                AddressTransfer::ZIP_CODE => $attributes[AddressTransfer::ZIP_CODE],
                CustomerTransfer::CUSTOMER_REFERENCE => $customerReference,
            ],
            $this->getResourceAttributes($response),
            'The address is attached to the customer named in the URI, not to one from the payload.',
        );
        $this->assertAttributesMatch(
            [
                AddressTransfer::IS_DEFAULT_BILLING => true,
                AddressTransfer::IS_DEFAULT_SHIPPING => true,
            ],
            $this->getResourceAttributes($response),
            'The first address of a customer becomes both the billing and the shipping default.',
        );
        $this->assertNotEmpty(
            $this->decodeJsonApi($response)[static::JSON_API_KEY_DATA][static::JSON_API_KEY_ID] ?? null,
            'The database assigns the uuid the response is addressed by.',
        );
    }

    public function testGivenACreatedAddressWhenReadBackThenItIsPersistedWithItsResolvedCountry(): void
    {
        // Arrange
        $customerTransfer = $this->tester->haveAddresseeCustomer();
        $this->tester->actingAsUser();
        $customerReference = $customerTransfer->getCustomerReferenceOrFail();
        $uuid = $this->haveAddressViaApi($customerReference);

        // Act
        $response = $this->handleApiRequest(
            'GET',
            $this->tester->getCustomerAddressUrl($customerReference, $uuid),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertSame(
            $uuid,
            $this->decodeJsonApi($response)[static::JSON_API_KEY_DATA][static::JSON_API_KEY_ID],
        );
        $this->assertAttributesMatch(
            [AddressTransfer::COUNTRY => $this->tester->getCountryName()],
            $this->getResourceAttributes($response),
            'The country relation was resolved from the submitted country code and persisted.',
        );
    }

    public function testGivenACustomerAlreadyHasAnAddressWhenCreateThenTheNewOneIsNotPromoted(): void
    {
        // Arrange
        $customerTransfer = $this->tester->haveAddresseeCustomer();
        $this->tester->actingAsUser();
        $customerReference = $customerTransfer->getCustomerReferenceOrFail();
        $this->haveAddressViaApi($customerReference);

        // Act
        $response = $this->handleApiRequest(
            'POST',
            $this->tester->getCustomerAddressCollectionUrl($customerReference),
            $this->tester->buildCustomerAddressRequestBody(
                $this->tester->buildValidCustomerAddressAttributes([
                    AddressTransfer::CITY => static::SECOND_ADDRESS_CITY,
                ]),
            ),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_CREATED);
        $this->assertAttributesMatch(
            [
                AddressTransfer::IS_DEFAULT_BILLING => false,
                AddressTransfer::IS_DEFAULT_SHIPPING => false,
            ],
            $this->getResourceAttributes($response),
            'Only the first address is promoted — a later one does not steal the defaults.',
        );
    }

    public function testGivenNoAttributesWhenCreateAddressThenEveryRequiredAttributeIsReported(): void
    {
        // Arrange
        $customerTransfer = $this->tester->haveAddresseeCustomer();
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest(
            'POST',
            $this->tester->getCustomerAddressCollectionUrl($customerTransfer->getCustomerReferenceOrFail()),
            $this->tester->buildCustomerAddressRequestBody([]),
        );

        // Assert
        $this->assertRespondsWithErrorCode(
            $response,
            Response::HTTP_UNPROCESSABLE_ENTITY,
            static::RESPONSE_CODE_VALIDATION,
        );
        $this->assertCount(
            static::REQUIRED_ATTRIBUTE_COUNT,
            $this->getErrorDetails($response),
            'Every required attribute is reported, not just the first.',
        );
    }

    public function testGivenASalutationOutsideTheAllowedChoicesWhenCreateAddressThenItIsRejected(): void
    {
        // Arrange
        $customerTransfer = $this->tester->haveAddresseeCustomer();
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest(
            'POST',
            $this->tester->getCustomerAddressCollectionUrl($customerTransfer->getCustomerReferenceOrFail()),
            $this->tester->buildCustomerAddressRequestBody(
                $this->tester->buildValidCustomerAddressAttributes([
                    AddressTransfer::SALUTATION => static::INVALID_SALUTATION,
                ]),
            ),
        );

        // Assert
        $this->assertRespondsWithErrorCode(
            $response,
            Response::HTTP_UNPROCESSABLE_ENTITY,
            static::RESPONSE_CODE_VALIDATION,
        );
    }

    public function testGivenAnUnknownCustomerReferenceWhenCreateAddressThenItRespondsNotFound(): void
    {
        // Arrange
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest(
            'POST',
            $this->tester->getCustomerAddressCollectionUrl(static::UNKNOWN_CUSTOMER_REFERENCE),
            $this->tester->buildCustomerAddressRequestBody(
                $this->tester->buildValidCustomerAddressAttributes(),
            ),
        );

        // Assert
        $this->assertRespondsWithErrorCode(
            $response,
            Response::HTTP_NOT_FOUND,
            CustomerExperienceManagementConfig::RESPONSE_CODE_CUSTOMER_NOT_FOUND,
        );
    }

    public function testGivenNoAuthenticationWhenCreateAddressThenItRespondsUnauthorized(): void
    {
        // Arrange
        $customerTransfer = $this->tester->haveAddresseeCustomer();

        // Act — intentionally unauthenticated.
        $response = $this->handleApiRequest(
            'POST',
            $this->tester->getCustomerAddressCollectionUrl($customerTransfer->getCustomerReferenceOrFail()),
            $this->tester->buildCustomerAddressRequestBody(
                $this->tester->buildValidCustomerAddressAttributes(),
            ),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_UNAUTHORIZED);
    }
}
