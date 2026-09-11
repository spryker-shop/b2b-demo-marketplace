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
 * `GET /customers/{customerReference}` over a booted GLUE_BACKEND kernel.
 *
 * Ported from GetSingleCustomerBackendJsonApiCest, which drove the same route over HTTP against a
 * running stack and a real OAuth server.
 *
 * Auto-generated group annotations
 *
 * @group PyzTest
 * @group Glue
 * @group CustomerExperienceManagement
 * @group BackendApi
 * @group Integration
 * @group GetSingleCustomerBackendApiTest
 * Add your own group annotations below this line
 */
class GetSingleCustomerBackendApiTest extends AbstractCustomerExperienceManagementBackendApiTestCase
{
    public function testGivenAnAuthenticatedOperatorWhenGetCustomerByReferenceThenTheCustomerIsReturned(): void
    {
        // Arrange
        $customerTransfer = $this->tester->haveCustomer();
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest(
            'GET',
            $this->tester->getCustomerUrl($customerTransfer->getCustomerReferenceOrFail()),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);

        $data = $this->decodeJsonApi($response)[static::JSON_API_KEY_DATA];
        $this->assertSame($customerTransfer->getCustomerReferenceOrFail(), $data[static::JSON_API_KEY_ID]);
        $this->assertAttributesMatch(
            [
                CustomerTransfer::EMAIL => $customerTransfer->getEmailOrFail(),
                CustomerTransfer::FIRST_NAME => $customerTransfer->getFirstNameOrFail(),
                CustomerTransfer::LAST_NAME => $customerTransfer->getLastNameOrFail(),
            ],
            $this->getResourceAttributes($response),
        );
        $this->assertStringEndsWith(
            $this->tester->getCustomerUrl($customerTransfer->getCustomerReferenceOrFail()),
            (string)($data[static::JSON_API_KEY_LINKS][static::JSON_API_KEY_SELF] ?? ''),
            'The resource carries the self link a client follows to re-read it.',
        );
    }

    public function testGivenAnUnknownReferenceWhenGetCustomerThenItRespondsNotFound(): void
    {
        // Arrange
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getCustomerUrl(static::UNKNOWN_CUSTOMER_REFERENCE));

        // Assert
        $this->assertJsonApiError(
            $response,
            Response::HTTP_NOT_FOUND,
            CustomerExperienceManagementConfig::RESPONSE_CODE_CUSTOMER_NOT_FOUND,
            sprintf(
                CustomerExperienceManagementConfig::RESPONSE_DETAILS_CUSTOMER_NOT_FOUND,
                static::UNKNOWN_CUSTOMER_REFERENCE,
            ),
        );
    }

    public function testGivenNoAuthenticationWhenGetCustomerThenItRespondsUnauthorized(): void
    {
        // Arrange
        $customerTransfer = $this->tester->haveCustomer();

        // Act — intentionally unauthenticated.
        $response = $this->handleApiRequest(
            'GET',
            $this->tester->getCustomerUrl($customerTransfer->getCustomerReferenceOrFail()),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_UNAUTHORIZED);
    }
}
