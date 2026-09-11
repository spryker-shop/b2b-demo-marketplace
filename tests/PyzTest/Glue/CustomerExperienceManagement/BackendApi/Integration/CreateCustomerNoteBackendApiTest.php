<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace PyzTest\Glue\CustomerExperienceManagement\BackendApi\Integration;

use Generated\Shared\Transfer\SpyCustomerNoteEntityTransfer;
use Generated\Shared\Transfer\UserTransfer;
use PyzTest\Glue\CustomerExperienceManagement\AbstractCustomerExperienceManagementBackendApiTestCase;
use SprykerFeature\Glue\CustomerExperienceManagement\CustomerExperienceManagementConfig;
use Symfony\Component\HttpFoundation\Response;

/**
 * `POST /customers/{customerReference}/notes` over a booted GLUE_BACKEND kernel.
 *
 * A note is an audit record: the author comes from the access token rather than the payload, the
 * identifier and timestamp are assigned on insert, and the resource exposes no way to rewrite or
 * erase one. The read-only and not-exposed cases are what hold that shape.
 *
 * Ported from CreateCustomerNoteBackendJsonApiCest.
 *
 * Auto-generated group annotations
 *
 * @group PyzTest
 * @group Glue
 * @group CustomerExperienceManagement
 * @group BackendApi
 * @group Integration
 * @group CreateCustomerNoteBackendApiTest
 * Add your own group annotations below this line
 */
class CreateCustomerNoteBackendApiTest extends AbstractCustomerExperienceManagementBackendApiTestCase
{
    protected const string MESSAGE = 'Agreed a payment plan for invoice 4711.';

    protected const string REWRITTEN_MESSAGE = 'rewritten history';

    protected const string FORGED_AUTHOR = 'Not The Acting Operator';

    protected const string FORGED_UUID = 'aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee';

    protected const string FORGED_CREATED_AT = '1999-01-01 00:00:00.000000';

    protected const string ATTRIBUTE_MESSAGE = 'message';

    protected const string ATTRIBUTE_USERNAME = 'username';

    protected const string ATTRIBUTE_CREATED_AT = 'createdAt';

    protected const string ATTRIBUTE_CUSTOMER_REFERENCE = 'customerReference';

    public function testGivenAValidMessageWhenCreateNoteThenItIsFiledForTheCustomerInTheUri(): void
    {
        // Arrange
        [$customerReference] = $this->haveWritableCustomer();

        // Act
        $response = $this->handleApiRequest(
            'POST',
            $this->tester->getCustomerNoteCollectionUrl($customerReference),
            $this->tester->buildCustomerNoteRequestBody(
                $this->tester->buildValidCustomerNoteAttributes([
                    SpyCustomerNoteEntityTransfer::MESSAGE => static::MESSAGE,
                ]),
            ),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_CREATED);

        $attributes = $this->getResourceAttributes($response);
        $this->assertSame(static::MESSAGE, $attributes[static::ATTRIBUTE_MESSAGE]);
        $this->assertSame($customerReference, $attributes[static::ATTRIBUTE_CUSTOMER_REFERENCE]);
        $this->assertNotEmpty(
            $this->decodeJsonApi($response)[static::JSON_API_KEY_DATA][static::JSON_API_KEY_ID] ?? null,
            'The created note carries the identifier it will be addressable by.',
        );
    }

    public function testGivenAnAuthenticatedOperatorWhenCreateNoteThenTheNoteIsAttributedToThem(): void
    {
        // Arrange
        [$customerReference, $userTransfer] = $this->haveWritableCustomer();

        // Act
        $response = $this->handleApiRequest(
            'POST',
            $this->tester->getCustomerNoteCollectionUrl($customerReference),
            $this->tester->buildCustomerNoteRequestBody($this->tester->buildValidCustomerNoteAttributes()),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_CREATED);
        $this->assertSame(
            $this->formatOperatorName($userTransfer),
            $this->getResourceAttributes($response)[static::ATTRIBUTE_USERNAME],
            'The note records the operator whose token was used, resolved from the access token.',
        );
    }

    public function testGivenForgedReadOnlyAttributesWhenCreateNoteThenTheyAreIgnored(): void
    {
        // Arrange
        [$customerReference, $userTransfer] = $this->haveWritableCustomer();

        // Act — the payload tries to forge the author, the identifier and the timestamp.
        $response = $this->handleApiRequest(
            'POST',
            $this->tester->getCustomerNoteCollectionUrl($customerReference),
            $this->tester->buildCustomerNoteRequestBody(
                $this->tester->buildValidCustomerNoteAttributes([
                    SpyCustomerNoteEntityTransfer::USERNAME => static::FORGED_AUTHOR,
                    SpyCustomerNoteEntityTransfer::UUID => static::FORGED_UUID,
                    SpyCustomerNoteEntityTransfer::CREATED_AT => static::FORGED_CREATED_AT,
                ]),
            ),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_CREATED);

        $attributes = $this->getResourceAttributes($response);
        $this->assertSame(
            $this->formatOperatorName($userTransfer),
            $attributes[static::ATTRIBUTE_USERNAME],
            'A client cannot file a note under another operator name.',
        );
        $this->assertNotSame(
            static::FORGED_UUID,
            $this->decodeJsonApi($response)[static::JSON_API_KEY_DATA][static::JSON_API_KEY_ID],
            'The identifier is generated on insert, never accepted from the payload.',
        );
        $this->assertNotSame(
            static::FORGED_CREATED_AT,
            $attributes[static::ATTRIBUTE_CREATED_AT],
            'The creation timestamp is set by the database, never accepted from the payload.',
        );
    }

    public function testGivenACreatedNoteWhenReadBackThenItIsInTheCollectionAndAtItsOwnUrl(): void
    {
        // Arrange
        [$customerReference] = $this->haveWritableCustomer();
        $uuid = $this->haveNoteViaApi($customerReference, [
            SpyCustomerNoteEntityTransfer::MESSAGE => static::MESSAGE,
        ]);

        // Act
        $collectionResponse = $this->handleApiRequest(
            'GET',
            $this->tester->getCustomerNoteCollectionUrl($customerReference),
        );
        $itemResponse = $this->handleApiRequest(
            'GET',
            $this->tester->getCustomerNoteUrl($customerReference, $uuid),
        );

        // Assert
        $this->assertRespondsWithStatus($collectionResponse, Response::HTTP_OK);
        $this->assertContains($uuid, $this->getResourceIds($collectionResponse));

        $this->assertRespondsWithStatus($itemResponse, Response::HTTP_OK);
        $this->assertSame(
            static::MESSAGE,
            $this->getResourceAttributes($itemResponse)[static::ATTRIBUTE_MESSAGE],
        );
    }

    public function testGivenABlankMessageWhenCreateNoteThenItIsRejected(): void
    {
        // Arrange
        [$customerReference] = $this->haveWritableCustomer();

        // Act
        $response = $this->handleApiRequest(
            'POST',
            $this->tester->getCustomerNoteCollectionUrl($customerReference),
            $this->tester->buildCustomerNoteRequestBody([SpyCustomerNoteEntityTransfer::MESSAGE => '']),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testGivenNoMessageWhenCreateNoteThenItIsRejected(): void
    {
        // Arrange
        [$customerReference] = $this->haveWritableCustomer();

        // Act
        $response = $this->handleApiRequest(
            'POST',
            $this->tester->getCustomerNoteCollectionUrl($customerReference),
            $this->tester->buildCustomerNoteRequestBody([]),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testGivenAnUnknownCustomerReferenceWhenCreateNoteThenItRespondsNotFound(): void
    {
        // Arrange
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest(
            'POST',
            $this->tester->getCustomerNoteCollectionUrl(static::UNKNOWN_CUSTOMER_REFERENCE),
            $this->tester->buildCustomerNoteRequestBody($this->tester->buildValidCustomerNoteAttributes()),
        );

        // Assert
        $this->assertRespondsWithErrorCode(
            $response,
            Response::HTTP_NOT_FOUND,
            CustomerExperienceManagementConfig::RESPONSE_CODE_CUSTOMER_NOT_FOUND,
        );
    }

    public function testGivenAnExistingNoteWhenUpdateThenTheOperationIsNotExposed(): void
    {
        // Arrange
        [$customerReference] = $this->haveWritableCustomer();
        $uuid = $this->haveNoteViaApi($customerReference);

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getCustomerNoteUrl($customerReference, $uuid),
            $this->tester->buildCustomerNoteRequestBody([
                SpyCustomerNoteEntityTransfer::MESSAGE => static::REWRITTEN_MESSAGE,
            ]),
        );

        // Assert — a note records what was said; the resource exposes no way to rewrite it.
        $this->assertRespondsWithStatus($response, Response::HTTP_NOT_FOUND);

        $readResponse = $this->handleApiRequest(
            'GET',
            $this->tester->getCustomerNoteUrl($customerReference, $uuid),
        );
        $this->assertSame(
            $this->tester->buildValidCustomerNoteAttributes()[SpyCustomerNoteEntityTransfer::MESSAGE],
            $this->getResourceAttributes($readResponse)[static::ATTRIBUTE_MESSAGE],
            'The note survived the rejected update unchanged.',
        );
    }

    public function testGivenAnExistingNoteWhenDeleteThenTheOperationIsNotExposed(): void
    {
        // Arrange
        [$customerReference] = $this->haveWritableCustomer();
        $uuid = $this->haveNoteViaApi($customerReference);

        // Act
        $response = $this->handleApiRequest(
            'DELETE',
            $this->tester->getCustomerNoteUrl($customerReference, $uuid),
        );

        // Assert — the resource exposes no way to erase a note.
        $this->assertRespondsWithStatus($response, Response::HTTP_NOT_FOUND);

        $readResponse = $this->handleApiRequest(
            'GET',
            $this->tester->getCustomerNoteUrl($customerReference, $uuid),
        );
        $this->assertRespondsWithStatus($readResponse, Response::HTTP_OK);
    }

    public function testGivenNoAuthenticationWhenCreateNoteThenItRespondsUnauthorized(): void
    {
        // Arrange
        $customerTransfer = $this->tester->haveNotedCustomer();

        // Act — intentionally unauthenticated.
        $response = $this->handleApiRequest(
            'POST',
            $this->tester->getCustomerNoteCollectionUrl($customerTransfer->getCustomerReferenceOrFail()),
            $this->tester->buildCustomerNoteRequestBody($this->tester->buildValidCustomerNoteAttributes()),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_UNAUTHORIZED);
    }

    public function testGivenAnInvalidTokenWhenCreateNoteThenItRespondsUnauthorized(): void
    {
        // Arrange
        $customerTransfer = $this->tester->haveNotedCustomer();
        $this->tester->actingWithInvalidToken();

        // Act
        $response = $this->handleApiRequest(
            'POST',
            $this->tester->getCustomerNoteCollectionUrl($customerTransfer->getCustomerReferenceOrFail()),
            $this->tester->buildCustomerNoteRequestBody($this->tester->buildValidCustomerNoteAttributes()),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_UNAUTHORIZED);
    }

    /**
     * @return array{0: string, 1: \Generated\Shared\Transfer\UserTransfer}
     */
    protected function haveWritableCustomer(): array
    {
        $customerTransfer = $this->tester->haveNotedCustomer();
        $userTransfer = $this->tester->haveNoteAuthor();

        $this->tester->actingAsUser($userTransfer);

        return [$customerTransfer->getCustomerReferenceOrFail(), $userTransfer];
    }

    protected function formatOperatorName(UserTransfer $userTransfer): string
    {
        return sprintf('%s %s', $userTransfer->getFirstName(), $userTransfer->getLastName());
    }
}
