<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace PyzTest\Glue\CustomerExperienceManagement\BackendApi\Integration;

use Generated\Shared\Transfer\CustomerTransfer;
use PyzTest\Glue\CustomerExperienceManagement\AbstractCustomerExperienceManagementBackendApiTestCase;
use PyzTest\Glue\CustomerExperienceManagement\Helper\CustomerExperienceManagementBackendApiHelper;
use Symfony\Component\HttpFoundation\Response;

/**
 * `GET /customers/{customerReference}?include=notes` — the first backend relationship include in
 * the repository.
 *
 * Its failure mode is silently missing data rather than an error: the relationship resolver returns
 * nothing and the response is still a valid 200. Every assertion here therefore checks that the
 * notes are PRESENT; none of them assert the absence of an error.
 *
 * The sort and page cases exist because those parameters address the parent collection, and a naive
 * implementation applies them to the include as well and empties it.
 *
 * Ported from CustomerNotesRelationshipBackendJsonApiCest.
 *
 * Auto-generated group annotations
 *
 * @group PyzTest
 * @group Glue
 * @group CustomerExperienceManagement
 * @group BackendApi
 * @group Integration
 * @group CustomerNotesRelationshipBackendApiTest
 * Add your own group annotations below this line
 */
class CustomerNotesRelationshipBackendApiTest extends AbstractCustomerExperienceManagementBackendApiTestCase
{
    protected const string INCLUDE_NOTES = 'notes';

    protected const string CUSTOMER_SORT_FIELD = CustomerTransfer::EMAIL;

    protected const int READABLE_NOTE_COUNT = 2;

    protected const string ATTRIBUTE_USERNAME = 'username';

    protected const string ATTRIBUTE_CUSTOMER_REFERENCE = 'customerReference';

    public function testGivenAnIncludeWhenGetCustomerThenTheNotesComeBackAsIncludedResources(): void
    {
        // Arrange
        [$customerReference] = $this->haveNotedCustomer();

        // Act
        $response = $this->handleApiRequest('GET', $this->buildCustomerUrl($customerReference));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);

        $relationships = $this->decodeJsonApi($response)[static::JSON_API_KEY_DATA][static::JSON_API_KEY_RELATIONSHIPS] ?? [];
        $this->assertNotEmpty(
            $relationships[static::INCLUDE_NOTES][static::JSON_API_KEY_DATA] ?? null,
            'The customer advertises its notes as a relationship.',
        );
        $this->assertCount(
            static::READABLE_NOTE_COUNT,
            $this->getIncludedIds($response),
            'Every note of the customer is delivered in the included section.',
        );
    }

    public function testGivenAnIncludeWhenGetCustomerThenTheIncludedNotesCarryTheirOwnAttributes(): void
    {
        // Arrange
        [$customerReference] = $this->haveNotedCustomer();

        // Act
        $response = $this->handleApiRequest('GET', $this->buildCustomerUrl($customerReference));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);

        $firstIncluded = (array)($this->decodeJsonApi($response)[static::JSON_API_KEY_INCLUDED][0] ?? []);
        $this->assertSame(
            CustomerExperienceManagementBackendApiHelper::RESOURCE_NOTES,
            $firstIncluded[static::JSON_API_KEY_TYPE] ?? null,
        );
        $this->assertSame(
            $customerReference,
            $firstIncluded[static::JSON_API_KEY_ATTRIBUTES][static::ATTRIBUTE_CUSTOMER_REFERENCE] ?? null,
        );
        $this->assertNotEmpty(
            $firstIncluded[static::JSON_API_KEY_ATTRIBUTES][static::ATTRIBUTE_USERNAME] ?? null,
            'An included note reports its author, exactly as the notes endpoint does.',
        );
    }

    public function testGivenASortForTheParentWhenGetCustomerWithIncludeThenTheNotesSurvive(): void
    {
        // Arrange
        [$customerReference] = $this->haveNotedCustomer();

        // Act
        $response = $this->handleApiRequest(
            'GET',
            $this->buildCustomerUrl($customerReference, ['sort' => static::CUSTOMER_SORT_FIELD]),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertCount(
            static::READABLE_NOTE_COUNT,
            $this->getIncludedIds($response),
            'A sort meant for the parent resource must not empty the included notes.',
        );
    }

    public function testGivenAPageLimitForTheParentWhenGetCustomerWithIncludeThenTheNotesAreNotTruncated(): void
    {
        // Arrange
        [$customerReference] = $this->haveNotedCustomer();

        // Act
        $response = $this->handleApiRequest(
            'GET',
            $this->buildCustomerUrl($customerReference, ['page' => ['limit' => 1]]),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertCount(
            static::READABLE_NOTE_COUNT,
            $this->getIncludedIds($response),
            "The parent's page window must not truncate the included notes.",
        );
    }

    public function testGivenAnIncludeWhenGetCustomerThenTheNotesCarryNoCollectionPagination(): void
    {
        // Arrange
        [$customerReference] = $this->haveNotedCustomer();

        // Act
        $response = $this->handleApiRequest('GET', $this->buildCustomerUrl($customerReference));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertCount(static::READABLE_NOTE_COUNT, $this->getIncludedIds($response));
        $this->assertStringNotContainsString(
            sprintf('"%s"', static::META_PAGINATION),
            (string)$response->getContent(),
            'Collection metadata belongs to the notes endpoint, not to an included relationship.',
        );
    }

    public function testGivenNoIncludeWhenGetCustomerThenNoNotesAreReturned(): void
    {
        // Arrange
        [$customerReference, $firstNoteTransfer] = $this->haveNotedCustomer();

        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getCustomerUrl($customerReference));

        // Assert — notes are delivered only when asked for, so the customer payload stays cheap.
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertStringNotContainsString(
            $firstNoteTransfer->getUuidOrFail(),
            (string)$response->getContent(),
        );
    }

    public function testGivenACustomerWithoutNotesWhenGetWithIncludeThenTheIncludeIsEmpty(): void
    {
        // Arrange
        $customerTransfer = $this->tester->haveNotedCustomer();
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest(
            'GET',
            $this->buildCustomerUrl($customerTransfer->getCustomerReferenceOrFail()),
        );

        // Assert — a customer without notes still resolves, with an empty include.
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertSame([], $this->getIncludedIds($response));
    }

    public function testGivenNoAuthenticationWhenGetCustomerWithIncludeThenItRespondsUnauthorized(): void
    {
        // Arrange
        $customerTransfer = $this->tester->haveNotedCustomer();

        // Act — intentionally unauthenticated.
        $response = $this->handleApiRequest(
            'GET',
            $this->buildCustomerUrl($customerTransfer->getCustomerReferenceOrFail()),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_UNAUTHORIZED);
    }

    /**
     * @param array<string, mixed> $query
     */
    protected function buildCustomerUrl(string $customerReference, array $query = []): string
    {
        return $this->tester->getCustomerUrlWithQuery(
            $customerReference,
            $query + ['include' => static::INCLUDE_NOTES],
        );
    }

    /**
     * @return array{0: string, 1: \Generated\Shared\Transfer\SpyCustomerNoteEntityTransfer}
     */
    protected function haveNotedCustomer(): array
    {
        $userTransfer = $this->tester->haveNoteAuthor();
        [$customerTransfer, $firstNoteTransfer] = $this->tester->haveCustomerWithTwoNotes($userTransfer);

        $this->tester->actingAsUser($userTransfer);

        return [$customerTransfer->getCustomerReferenceOrFail(), $firstNoteTransfer];
    }
}
