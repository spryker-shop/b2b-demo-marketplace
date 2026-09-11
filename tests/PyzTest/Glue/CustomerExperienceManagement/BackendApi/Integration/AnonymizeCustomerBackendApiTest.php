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
 * `DELETE /customers/{customerReference}` over a booted GLUE_BACKEND kernel.
 *
 * The operation anonymizes rather than deletes: the row is retained, stamped with `anonymizedAt`,
 * and scrubbed of personal data. That is why there are separate cases for "no longer readable" and
 * "still listed when explicitly asked for" — both are true of the same record.
 *
 * Ported from AnonymizeCustomerBackendJsonApiCest, whose four post-anonymization cases were chained
 * with test-dependency annotations on one shared customer. Here each arranges and anonymizes its
 * own, so a failure in one does not cascade into the rest.
 *
 * Auto-generated group annotations
 *
 * @group PyzTest
 * @group Glue
 * @group CustomerExperienceManagement
 * @group BackendApi
 * @group Integration
 * @group AnonymizeCustomerBackendApiTest
 * Add your own group annotations below this line
 */
class AnonymizeCustomerBackendApiTest extends AbstractCustomerExperienceManagementBackendApiTestCase
{
    protected const string ANONYMIZABLE_FIRST_NAME = 'CxmAnonymizableFirstName';

    protected const string ANONYMIZABLE_LAST_NAME = 'CxmAnonymizable';

    protected const string ATTRIBUTE_ANONYMIZED_AT = 'anonymizedAt';

    protected const string FILTER_CUSTOMER_REFERENCE = 'customers.customerReference';

    protected const string FILTER_INCLUDE_ANONYMIZED = 'customers.includeAnonymized';

    public function testGivenNoAuthenticationWhenAnonymizeCustomerThenItRespondsUnauthorized(): void
    {
        // Arrange
        $customerReference = $this->tester->haveCustomer()->getCustomerReferenceOrFail();

        // Act — intentionally unauthenticated.
        $response = $this->handleApiRequest('DELETE', $this->tester->getCustomerUrl($customerReference));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_UNAUTHORIZED);
    }

    public function testGivenAnUnknownReferenceWhenAnonymizeCustomerThenItRespondsNotFound(): void
    {
        // Arrange
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest(
            'DELETE',
            $this->tester->getCustomerUrl(static::UNKNOWN_CUSTOMER_REFERENCE),
        );

        // Assert
        $this->assertRespondsWithErrorCode(
            $response,
            Response::HTTP_NOT_FOUND,
            CustomerExperienceManagementConfig::RESPONSE_CODE_CUSTOMER_NOT_FOUND,
        );
    }

    public function testGivenAnExistingCustomerWhenAnonymizeThenItRespondsNoContent(): void
    {
        // Arrange
        $customerTransfer = $this->haveAnonymizableCustomer();
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest(
            'DELETE',
            $this->tester->getCustomerUrl($customerTransfer->getCustomerReferenceOrFail()),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_NO_CONTENT);
    }

    public function testGivenAnAnonymizedCustomerWhenGetByReferenceThenItIsNoLongerReadable(): void
    {
        // Arrange
        $customerTransfer = $this->haveAnonymizedCustomer();

        // Act
        $response = $this->handleApiRequest(
            'GET',
            $this->tester->getCustomerUrl($customerTransfer->getCustomerReferenceOrFail()),
        );

        // Assert
        $this->assertRespondsWithErrorCode(
            $response,
            Response::HTTP_NOT_FOUND,
            CustomerExperienceManagementConfig::RESPONSE_CODE_CUSTOMER_NOT_FOUND,
        );
    }

    public function testGivenAnAnonymizedCustomerWhenExplicitlyRequestedThenItIsStillListed(): void
    {
        // Arrange
        $customerTransfer = $this->haveAnonymizedCustomer();
        $customerReference = $customerTransfer->getCustomerReferenceOrFail();

        // Act
        $response = $this->handleApiRequest('GET', $this->anonymizedCustomerCollectionUrl($customerReference));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertContains($customerReference, $this->getResourceIds($response));
    }

    public function testGivenAnAnonymizedCustomerWhenListedThenItNoLongerCarriesPersonalData(): void
    {
        // Arrange
        $customerTransfer = $this->haveAnonymizedCustomer();

        // Act
        $response = $this->handleApiRequest(
            'GET',
            $this->anonymizedCustomerCollectionUrl($customerTransfer->getCustomerReferenceOrFail()),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);

        $body = (string)$response->getContent();
        $this->assertStringNotContainsString(
            $customerTransfer->getEmailOrFail(),
            $body,
            'The anonymizer scrubbed the email off the retained row.',
        );
        $this->assertStringNotContainsString(
            $customerTransfer->getFirstNameOrFail(),
            $body,
            'The anonymizer scrubbed the first name off the retained row.',
        );
        $this->assertNotEmpty(
            $this->getFirstResourceAttributes($response)[static::ATTRIBUTE_ANONYMIZED_AT] ?? null,
            'The anonymization is stamped on the record.',
        );
    }

    protected function haveAnonymizableCustomer(): CustomerTransfer
    {
        return $this->tester->haveCustomer([
            CustomerTransfer::EMAIL => uniqid('cxm.anonymizable.', true) . '@spryker.local',
            CustomerTransfer::FIRST_NAME => static::ANONYMIZABLE_FIRST_NAME,
            CustomerTransfer::LAST_NAME => static::ANONYMIZABLE_LAST_NAME,
        ]);
    }

    protected function haveAnonymizedCustomer(): CustomerTransfer
    {
        $customerTransfer = $this->haveAnonymizableCustomer();
        $this->tester->actingAsUser();

        $response = $this->handleApiRequest(
            'DELETE',
            $this->tester->getCustomerUrl($customerTransfer->getCustomerReferenceOrFail()),
        );

        $this->assertSame(
            Response::HTTP_NO_CONTENT,
            $response->getStatusCode(),
            sprintf('Could not anonymize the customer under test: %s', (string)$response->getContent()),
        );

        return $customerTransfer;
    }

    protected function anonymizedCustomerCollectionUrl(string $customerReference): string
    {
        return $this->tester->getCustomerCollectionUrl([
            'filter' => [
                static::FILTER_CUSTOMER_REFERENCE => $customerReference,
                static::FILTER_INCLUDE_ANONYMIZED => '1',
            ],
        ]);
    }
}
