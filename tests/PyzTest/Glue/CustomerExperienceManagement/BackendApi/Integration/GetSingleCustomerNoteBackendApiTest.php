<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace PyzTest\Glue\CustomerExperienceManagement\BackendApi\Integration;

use PyzTest\Glue\CustomerExperienceManagement\AbstractCustomerExperienceManagementBackendApiTestCase;
use SprykerFeature\Glue\CustomerExperienceManagement\CustomerExperienceManagementConfig;
use Symfony\Component\HttpFoundation\Response;

/**
 * `GET /customers/{customerReference}/notes/{uuid}` over a booted GLUE_BACKEND kernel.
 *
 * NEW coverage — the docker-lane suites had no test for this operation. It was reached only
 * incidentally, as the read-back of a create, which left two things unverified:
 *
 * - `RESPONSE_CODE_NOTE_NOT_FOUND` was declared in the config and asserted by nothing, so the
 *   unknown-uuid path was never exercised.
 * - Notes had no ownership coverage at all, although addresses did. A note reached through another
 *   customer's URI must be a not-found that discloses nothing — the same contract addresses hold to.
 *
 * Auto-generated group annotations
 *
 * @group PyzTest
 * @group Glue
 * @group CustomerExperienceManagement
 * @group BackendApi
 * @group Integration
 * @group GetSingleCustomerNoteBackendApiTest
 * Add your own group annotations below this line
 */
class GetSingleCustomerNoteBackendApiTest extends AbstractCustomerExperienceManagementBackendApiTestCase
{
    protected const string MESSAGE = 'Confirmed the delivery window with the customer.';

    protected const string ATTRIBUTE_MESSAGE = 'message';

    protected const string ATTRIBUTE_USERNAME = 'username';

    protected const string ATTRIBUTE_CUSTOMER_REFERENCE = 'customerReference';

    public function testGivenAnExistingNoteWhenGetByUuidThenTheNoteIsReturned(): void
    {
        // Arrange
        [$customerReference, $uuid] = $this->haveOwnedNote();

        // Act
        $response = $this->handleApiRequest(
            'GET',
            $this->tester->getCustomerNoteUrl($customerReference, $uuid),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);

        $payload = $this->decodeJsonApi($response)[static::JSON_API_KEY_DATA];
        $this->assertSame($uuid, $payload[static::JSON_API_KEY_ID]);

        $attributes = $this->getResourceAttributes($response);
        $this->assertSame(static::MESSAGE, $attributes[static::ATTRIBUTE_MESSAGE]);
        $this->assertSame($customerReference, $attributes[static::ATTRIBUTE_CUSTOMER_REFERENCE]);
        $this->assertNotEmpty(
            $attributes[static::ATTRIBUTE_USERNAME] ?? null,
            'A note names its author wherever it is read.',
        );
        $this->assertStringEndsWith(
            $this->tester->getCustomerNoteUrl($customerReference, $uuid),
            (string)($payload[static::JSON_API_KEY_LINKS][static::JSON_API_KEY_SELF] ?? ''),
        );
    }

    public function testGivenAnUnknownUuidWhenGetNoteThenItRespondsNotFound(): void
    {
        // Arrange
        [$customerReference] = $this->haveOwnedNote();

        // Act
        $response = $this->handleApiRequest(
            'GET',
            $this->tester->getCustomerNoteUrl($customerReference, static::UNKNOWN_UUID),
        );

        // Assert
        $this->assertRespondsWithErrorCode(
            $response,
            Response::HTTP_NOT_FOUND,
            CustomerExperienceManagementConfig::RESPONSE_CODE_NOTE_NOT_FOUND,
        );
        $this->assertContains(
            sprintf(
                CustomerExperienceManagementConfig::RESPONSE_DETAILS_NOTE_NOT_FOUND,
                static::UNKNOWN_UUID,
                $customerReference,
            ),
            $this->getErrorDetails($response),
        );
    }

    public function testGivenANoteOfAnotherCustomerWhenGetThroughTheWrongCustomerThenItRespondsNotFound(): void
    {
        // Arrange
        [, $uuid, $wrongOwnerReference] = $this->haveOwnedNote();

        // Act
        $response = $this->handleApiRequest(
            'GET',
            $this->tester->getCustomerNoteUrl($wrongOwnerReference, $uuid),
        );

        // Assert — not-found rather than forbidden, so the uuid's existence is not confirmed.
        $this->assertRespondsWithErrorCode(
            $response,
            Response::HTTP_NOT_FOUND,
            CustomerExperienceManagementConfig::RESPONSE_CODE_NOTE_NOT_FOUND,
        );
    }

    public function testGivenANoteOfAnotherCustomerWhenGetThroughTheWrongCustomerThenNothingIsRevealed(): void
    {
        // Arrange
        [$ownerReference, $uuid, $wrongOwnerReference] = $this->haveOwnedNote();

        // Act
        $response = $this->handleApiRequest(
            'GET',
            $this->tester->getCustomerNoteUrl($wrongOwnerReference, $uuid),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_NOT_FOUND);

        $body = (string)$response->getContent();
        $this->assertStringNotContainsString(
            static::MESSAGE,
            $body,
            'The rejection carries none of the note text.',
        );
        $this->assertStringNotContainsString(
            $ownerReference,
            $body,
            'The rejection does not name the customer the note belongs to.',
        );
    }

    public function testGivenAnUnknownCustomerReferenceWhenGetNoteThenItRespondsNotFound(): void
    {
        // Arrange
        [, $uuid] = $this->haveOwnedNote();

        // Act
        $response = $this->handleApiRequest(
            'GET',
            $this->tester->getCustomerNoteUrl(static::UNKNOWN_CUSTOMER_REFERENCE, $uuid),
        );

        // Assert — an unknown customer is reported before the note is looked up.
        $this->assertRespondsWithErrorCode(
            $response,
            Response::HTTP_NOT_FOUND,
            CustomerExperienceManagementConfig::RESPONSE_CODE_CUSTOMER_NOT_FOUND,
        );
    }

    /**
     * The note has to be created through the API, so this test is authenticated while arranging and
     * only then drops to an invalid token — which is why
     * {@see \SprykerTest\ApiPlatform\Helper\BackendApiLoginHelper::actingWithInvalidToken()} has to
     * invalidate explicitly rather than inherit the un-authenticated default.
     */
    public function testGivenAnInvalidTokenWhenGetNoteThenItRespondsUnauthorized(): void
    {
        // Arrange
        [$customerReference, $uuid] = $this->haveOwnedNote();
        $this->tester->actingWithInvalidToken();

        // Act
        $response = $this->handleApiRequest(
            'GET',
            $this->tester->getCustomerNoteUrl($customerReference, $uuid),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_UNAUTHORIZED);
    }

    /**
     * A note owned by one customer, plus a second customer that does not own it.
     *
     * @return array{0: string, 1: string, 2: string} owner reference, note uuid, wrong-owner reference
     */
    protected function haveOwnedNote(): array
    {
        $ownerTransfer = $this->tester->haveNotedCustomer();
        $wrongOwnerTransfer = $this->tester->haveNotedCustomer();
        $userTransfer = $this->tester->haveNoteAuthor();

        $this->tester->actingAsUser($userTransfer);

        $ownerReference = $ownerTransfer->getCustomerReferenceOrFail();

        return [
            $ownerReference,
            $this->haveNoteViaApi($ownerReference, ['message' => static::MESSAGE]),
            $wrongOwnerTransfer->getCustomerReferenceOrFail(),
        ];
    }
}
