<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace PyzTest\Glue\CustomerExperienceManagement\BackendApi\Integration;

use Generated\Shared\Transfer\SpyCustomerNoteEntityTransfer;
use PyzTest\Glue\CustomerExperienceManagement\AbstractCustomerExperienceManagementBackendApiTestCase;
use SprykerFeature\Glue\CustomerExperienceManagement\CustomerExperienceManagementConfig;
use Symfony\Component\HttpFoundation\Response;

/**
 * `GET /customers/{customerReference}/notes` over a booted GLUE_BACKEND kernel.
 *
 * A note collection is a timeline: newest first by default, and notes written within the same
 * second still have to come back in a stable order.
 *
 * Ported from GetCollectionCustomerNotesBackendJsonApiCest.
 *
 * Auto-generated group annotations
 *
 * @group PyzTest
 * @group Glue
 * @group CustomerExperienceManagement
 * @group BackendApi
 * @group Integration
 * @group GetCollectionCustomerNotesBackendApiTest
 * Add your own group annotations below this line
 */
class GetCollectionCustomerNotesBackendApiTest extends AbstractCustomerExperienceManagementBackendApiTestCase
{
    /**
     * The message is free text, not a sortable column.
     */
    protected const string UNSUPPORTED_SORT_FIELD = SpyCustomerNoteEntityTransfer::MESSAGE;

    protected const string RESOURCE_PROPERTY_ID_CUSTOMER_NOTE = 'idCustomerNote';

    protected const string RESOURCE_PROPERTY_FK_USER = 'fkUser';

    protected const int READABLE_NOTE_COUNT = 2;

    public function testGivenACustomerWithNotesWhenGetCollectionThenEveryNoteIsReturned(): void
    {
        // Arrange
        [$customerTransfer, $firstNoteTransfer, $secondNoteTransfer] = $this->haveNotedCustomer();

        // Act
        $response = $this->handleApiRequest(
            'GET',
            $this->tester->getCustomerNoteCollectionUrl($customerTransfer->getCustomerReferenceOrFail()),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);

        $uuids = $this->getResourceIds($response);
        $this->assertCount(static::READABLE_NOTE_COUNT, $uuids);
        $this->assertContains($firstNoteTransfer->getUuidOrFail(), $uuids);
        $this->assertContains($secondNoteTransfer->getUuidOrFail(), $uuids);
    }

    public function testGivenAnotherCustomerHasNotesWhenGetCollectionThenOnlyTheGivenCustomersAreReturned(): void
    {
        // Arrange
        [$customerTransfer] = $this->haveNotedCustomer();
        [, $otherNoteTransfer] = $this->haveNotedCustomer();

        // Act
        $response = $this->handleApiRequest(
            'GET',
            $this->tester->getCustomerNoteCollectionUrl($customerTransfer->getCustomerReferenceOrFail()),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertNotContains(
            $otherNoteTransfer->getUuidOrFail(),
            $this->getResourceIds($response),
            'The collection is scoped to the customer in the URI, not to every note in the system.',
        );
    }

    public function testGivenACustomerWithNotesWhenGetCollectionThenTheInternalKeysAreNotExposed(): void
    {
        // Arrange
        [$customerTransfer, $firstNoteTransfer] = $this->haveNotedCustomer();

        // Act
        $response = $this->handleApiRequest(
            'GET',
            $this->tester->getCustomerNoteCollectionUrl($customerTransfer->getCustomerReferenceOrFail()),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertContains($firstNoteTransfer->getUuidOrFail(), $this->getResourceIds($response));

        $body = (string)$response->getContent();
        $this->assertStringNotContainsString(
            static::RESOURCE_PROPERTY_ID_CUSTOMER_NOTE,
            $body,
            'The surrogate key stays internal — notes are addressed by uuid alone.',
        );
        $this->assertStringNotContainsString(
            static::RESOURCE_PROPERTY_FK_USER,
            $body,
            'The author is reported by name, never by the user table key.',
        );
    }

    public function testGivenACustomerWithNotesWhenGetCollectionThenTheNewestNoteComesFirst(): void
    {
        // Arrange
        [$customerTransfer, , $secondNoteTransfer] = $this->haveNotedCustomer();

        // Act
        $response = $this->handleApiRequest(
            'GET',
            $this->tester->getCustomerNoteCollectionUrl($customerTransfer->getCustomerReferenceOrFail()),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertSame(
            $secondNoteTransfer->getUuidOrFail(),
            $this->getResourceIds($response)[0] ?? null,
            'A note timeline reads newest first, and notes written in the same second stay ordered.',
        );
    }

    public function testGivenAnAscendingSortWhenGetCollectionThenTheOldestNoteComesFirst(): void
    {
        // Arrange
        [$customerTransfer, $firstNoteTransfer] = $this->haveNotedCustomer();

        // Act
        $response = $this->handleApiRequest(
            'GET',
            $this->tester->getCustomerNoteCollectionUrl(
                $customerTransfer->getCustomerReferenceOrFail(),
                ['sort' => SpyCustomerNoteEntityTransfer::CREATED_AT],
            ),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertSame(
            $firstNoteTransfer->getUuidOrFail(),
            $this->getResourceIds($response)[0] ?? null,
            'An ascending sort reverses the default timeline order.',
        );
    }

    public function testGivenAPageLimitWhenGetCollectionThenOnePageAndItsPaginationMetadataAreReturned(): void
    {
        // Arrange
        [$customerTransfer] = $this->haveNotedCustomer();

        // Act
        $response = $this->handleApiRequest(
            'GET',
            $this->tester->getCustomerNoteCollectionUrl(
                $customerTransfer->getCustomerReferenceOrFail(),
                ['page' => ['limit' => 1]],
            ),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertCount(1, $this->getResourceIds($response), 'A limit of one truncates the page.');

        $this->assertNoPaginationInsideMembers($response);

        $pagination = $this->getMetaPagination($response);
        $this->assertSame(static::READABLE_NOTE_COUNT, $pagination[static::PAGINATION_KEY_NUM_FOUND]);
        $this->assertSame(static::READABLE_NOTE_COUNT, $pagination[static::PAGINATION_KEY_MAX_PAGE]);
        $this->assertNotEmpty(
            $this->decodeJsonApi($response)[static::JSON_API_KEY_LINKS][static::LINK_NEXT] ?? null,
            'The second page of the nested route is reachable from the response links.',
        );
    }

    public function testGivenAPageOffsetWhenGetCollectionThenTheRemainingNoteIsReturned(): void
    {
        // Arrange
        [$customerTransfer, $firstNoteTransfer] = $this->haveNotedCustomer();

        // Act
        $response = $this->handleApiRequest(
            'GET',
            $this->tester->getCustomerNoteCollectionUrl(
                $customerTransfer->getCustomerReferenceOrFail(),
                ['page' => ['limit' => 1, 'offset' => 1]],
            ),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertSame(
            $firstNoteTransfer->getUuidOrFail(),
            $this->getResourceIds($response)[0] ?? null,
            'An offset walks the timeline instead of restarting it.',
        );
    }

    public function testGivenACustomerWithoutNotesWhenGetCollectionThenAnEmptyCollectionIsReturned(): void
    {
        // Arrange
        $customerTransfer = $this->tester->haveNotedCustomer();
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest(
            'GET',
            $this->tester->getCustomerNoteCollectionUrl($customerTransfer->getCustomerReferenceOrFail()),
        );

        // Assert — a customer without notes is an empty collection, not a not-found.
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertSame([], $this->getResourceIds($response));
    }

    public function testGivenAnUnsupportedSortFieldWhenGetCollectionThenItRespondsBadRequest(): void
    {
        // Arrange
        [$customerTransfer] = $this->haveNotedCustomer();

        // Act
        $response = $this->handleApiRequest(
            'GET',
            $this->tester->getCustomerNoteCollectionUrl(
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
            $this->tester->getCustomerNoteCollectionUrl(static::UNKNOWN_CUSTOMER_REFERENCE),
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
        $customerTransfer = $this->tester->haveNotedCustomer();

        // Act — intentionally unauthenticated.
        $response = $this->handleApiRequest(
            'GET',
            $this->tester->getCustomerNoteCollectionUrl($customerTransfer->getCustomerReferenceOrFail()),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_UNAUTHORIZED);
    }

    public function testGivenAnInvalidTokenWhenGetCollectionThenItRespondsUnauthorized(): void
    {
        // Arrange
        $customerTransfer = $this->tester->haveNotedCustomer();
        $this->tester->actingWithInvalidToken();

        // Act
        $response = $this->handleApiRequest(
            'GET',
            $this->tester->getCustomerNoteCollectionUrl($customerTransfer->getCustomerReferenceOrFail()),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_UNAUTHORIZED);
    }

    /**
     * @return array{0: \Generated\Shared\Transfer\CustomerTransfer, 1: \Generated\Shared\Transfer\SpyCustomerNoteEntityTransfer, 2: \Generated\Shared\Transfer\SpyCustomerNoteEntityTransfer}
     */
    protected function haveNotedCustomer(): array
    {
        $userTransfer = $this->tester->haveNoteAuthor();
        $fixtures = $this->tester->haveCustomerWithTwoNotes($userTransfer);

        $this->tester->actingAsUser($userTransfer);

        return $fixtures;
    }
}
