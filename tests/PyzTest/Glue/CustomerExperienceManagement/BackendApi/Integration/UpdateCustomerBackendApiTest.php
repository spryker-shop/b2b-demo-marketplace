<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace PyzTest\Glue\CustomerExperienceManagement\BackendApi\Integration;

use Generated\Shared\Transfer\CustomerTransfer;
use PyzTest\Glue\CustomerExperienceManagement\AbstractCustomerExperienceManagementBackendApiTestCase;
use SprykerFeature\Glue\CustomerExperienceManagement\CustomerExperienceManagementConfig;
use Symfony\Component\HttpFoundation\Response;

/**
 * `PATCH /customers/{customerReference}` over a booted GLUE_BACKEND kernel.
 *
 * Ported from UpdateCustomerBackendJsonApiCest. That Cest chained the write and the read-back with
 * a test-dependency annotation; here each method arranges its own customer, so the read-back happens
 * inside the test that asserts persistence rather than depending on a previous one having run.
 *
 * Auto-generated group annotations
 *
 * @group PyzTest
 * @group Glue
 * @group CustomerExperienceManagement
 * @group BackendApi
 * @group Integration
 * @group UpdateCustomerBackendApiTest
 * Add your own group annotations below this line
 */
class UpdateCustomerBackendApiTest extends AbstractCustomerExperienceManagementBackendApiTestCase
{
    protected const string UPDATED_FIRST_NAME = 'PatchedViaBackendApi';

    public function testGivenASingleAttributeWhenUpdateCustomerThenItIsAppliedAndTheRestIsKept(): void
    {
        // Arrange
        $customerTransfer = $this->tester->haveCustomer();
        $this->tester->actingAsUser();
        $customerReference = $customerTransfer->getCustomerReferenceOrFail();

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getCustomerUrl($customerReference),
            $this->tester->buildCustomerRequestBody(
                [CustomerTransfer::FIRST_NAME => static::UPDATED_FIRST_NAME],
                $customerReference,
            ),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);

        $attributes = $this->getResourceAttributes($response);
        $this->assertSame(
            static::UPDATED_FIRST_NAME,
            $attributes[CustomerTransfer::FIRST_NAME],
            'The supplied attribute is applied.',
        );
        $this->assertAttributesMatch(
            [
                CustomerTransfer::LAST_NAME => $customerTransfer->getLastNameOrFail(),
                CustomerTransfer::EMAIL => $customerTransfer->getEmailOrFail(),
            ],
            $attributes,
            'Attributes the request omitted keep their stored value rather than being blanked.',
        );
    }

    public function testGivenAnUpdatedCustomerWhenReadBackThenTheChangeWasPersisted(): void
    {
        // Arrange
        $customerTransfer = $this->tester->haveCustomer();
        $this->tester->actingAsUser();
        $customerReference = $customerTransfer->getCustomerReferenceOrFail();

        $updateResponse = $this->handleApiRequest(
            'PATCH',
            $this->tester->getCustomerUrl($customerReference),
            $this->tester->buildCustomerRequestBody(
                [CustomerTransfer::FIRST_NAME => static::UPDATED_FIRST_NAME],
                $customerReference,
            ),
        );
        $this->assertRespondsWithStatus($updateResponse, Response::HTTP_OK);

        // Act — read the customer back rather than trusting the write response.
        $response = $this->handleApiRequest('GET', $this->tester->getCustomerUrl($customerReference));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertSame(
            static::UPDATED_FIRST_NAME,
            $this->getResourceAttributes($response)[CustomerTransfer::FIRST_NAME],
        );
    }

    public function testGivenAnUnknownReferenceWhenUpdateCustomerThenItRespondsNotFound(): void
    {
        // Arrange
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getCustomerUrl(static::UNKNOWN_CUSTOMER_REFERENCE),
            $this->tester->buildCustomerRequestBody(
                [CustomerTransfer::FIRST_NAME => static::UPDATED_FIRST_NAME],
                static::UNKNOWN_CUSTOMER_REFERENCE,
            ),
        );

        // Assert
        $this->assertRespondsWithErrorCode(
            $response,
            Response::HTTP_NOT_FOUND,
            CustomerExperienceManagementConfig::RESPONSE_CODE_CUSTOMER_NOT_FOUND,
        );
    }

    public function testGivenNoAuthenticationWhenUpdateCustomerThenItRespondsUnauthorized(): void
    {
        // Arrange
        $customerReference = $this->tester->haveCustomer()->getCustomerReferenceOrFail();

        // Act — intentionally unauthenticated.
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getCustomerUrl($customerReference),
            $this->tester->buildCustomerRequestBody(
                [CustomerTransfer::FIRST_NAME => static::UPDATED_FIRST_NAME],
                $customerReference,
            ),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_UNAUTHORIZED);
    }
}
