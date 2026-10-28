<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace PyzTest\Glue\OrderExperienceManagement\BackendApi\Integration;

use Generated\Shared\Transfer\UserTransfer;
use PyzTest\Glue\OrderExperienceManagement\AbstractOrderExperienceManagementBackendApiTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Auto-generated group annotations
 *
 * @group PyzTest
 * @group Glue
 * @group OrderExperienceManagement
 * @group BackendApi
 * @group Integration
 * @group OrderCommentsBackendApiTest
 * Add your own group annotations below this line
 */
class OrderCommentsBackendApiTest extends AbstractOrderExperienceManagementBackendApiTestCase
{
    protected const string RESOURCE_ORDER_COMMENTS = 'order-comments';

    protected const string MESSAGE = 'Called the customer; they will confirm tomorrow.';

    protected const string MESSAGE_SECOND = 'Customer confirmed: hold until Friday.';

    protected const string ATTRIBUTE_COMMENTS = 'comments';

    protected const string ATTRIBUTE_MESSAGE = 'message';

    protected const string ATTRIBUTE_USERNAME = 'username';

    protected const string ATTRIBUTE_ORDER_REFERENCE = 'orderReference';

    protected const string ATTRIBUTE_CREATED_AT = 'createdAt';

    protected const string ACTING_USER_USERNAME_FORMAT = 'oem-comment-%s@spryker.test';

    protected const string ACTING_USER_FIRST_NAME = 'Ada';

    protected const string ACTING_USER_LAST_NAME = 'Operator';

    /**
     * @uses \SprykerFeature\Glue\OrderExperienceManagement\Api\Backend\Processor\OrderCommentsBackendProcessor::AUTHOR_NAME_FORMAT
     */
    protected const string ACTING_USER_DISPLAY_NAME = 'Ada Operator';

    protected const int MESSAGE_LENGTH_OVER_LIMIT = 5001;

    public function testGivenAnOrderWithoutCommentsWhenGetCommentsThenEmptyCollectionIsReturned(): void
    {
        // Arrange
        [, $saveOrderTransfer] = $this->tester->haveCustomerWithOrder();
        $this->tester->actingAsUser($this->haveActingUser());

        // Act
        $response = $this->handleApiRequest('GET', $this->getCommentsUrl($saveOrderTransfer->getOrderReferenceOrFail()));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertSame([], $this->getJsonApiMembers($response, static::JSON_API_KEY_DATA));
    }

    public function testGivenAnUnknownOrderReferenceWhenGetCommentsThenNotFoundIsReturned(): void
    {
        // Arrange
        $this->tester->actingAsUser($this->haveActingUser());

        // Act
        $response = $this->handleApiRequest('GET', $this->getCommentsUrl(static::UNKNOWN_ORDER_REFERENCE));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_NOT_FOUND);
    }

    public function testGivenNoCredentialsWhenGetCommentsThenUnauthorizedIsReturned(): void
    {
        // Arrange
        [, $saveOrderTransfer] = $this->tester->haveCustomerWithOrder();

        // Act
        $response = $this->handleApiRequest('GET', $this->getCommentsUrl($saveOrderTransfer->getOrderReferenceOrFail()));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_UNAUTHORIZED);
    }

    public function testGivenAnOperatorTheAclRefusesWhenGetCommentsThenItRespondsForbidden(): void
    {
        // Arrange
        $this->tester->actingAsUserWithoutAclAccess();

        // Act
        $response = $this->handleApiRequest('GET', $this->getCommentsUrl(static::UNKNOWN_ORDER_REFERENCE));

        // Assert — authorization is decided before the order is looked up, so this is 403, not 404.
        $this->assertRespondsWithStatus($response, Response::HTTP_FORBIDDEN);
    }

    public function testGivenAnAuthenticatedOperatorWhenPostCommentThenItIsCreatedAndReadableAfterwards(): void
    {
        // Arrange
        [, $saveOrderTransfer] = $this->tester->haveCustomerWithOrder();
        $orderReference = $saveOrderTransfer->getOrderReferenceOrFail();
        $this->tester->actingAsUser($this->haveActingUser());

        // Act
        $postResponse = $this->postComment($orderReference, static::MESSAGE);
        $getResponse = $this->handleApiRequest('GET', $this->getCommentsUrl($orderReference));

        // Assert
        $this->assertRespondsWithStatus($postResponse, Response::HTTP_CREATED);
        $postAttributes = $this->getResourceAttributes($postResponse);
        $this->assertSame(static::MESSAGE, $postAttributes[static::ATTRIBUTE_MESSAGE] ?? null);
        $this->assertSame($orderReference, $postAttributes[static::ATTRIBUTE_ORDER_REFERENCE] ?? null);
        $this->assertSame(static::ACTING_USER_DISPLAY_NAME, $postAttributes[static::ATTRIBUTE_USERNAME] ?? null);
        $this->assertNotEmpty($postAttributes[static::ATTRIBUTE_CREATED_AT] ?? null, 'A created comment must report when it was written.');

        $this->assertRespondsWithStatus($getResponse, Response::HTTP_OK);
        $this->assertSame(
            [static::MESSAGE],
            $this->extractMessages($getResponse),
            'A comment written through POST must be readable through GET.',
        );
    }

    public function testGivenAUsernameInTheBodyWhenPostCommentThenTheActingOperatorIsRecordedInstead(): void
    {
        // Arrange
        [, $saveOrderTransfer] = $this->tester->haveCustomerWithOrder();
        $this->tester->actingAsUser($this->haveActingUser());

        // Act
        $response = $this->handleApiRequest(
            'POST',
            $this->getCommentsUrl($saveOrderTransfer->getOrderReferenceOrFail()),
            $this->encodeJsonApiBody(static::RESOURCE_ORDER_COMMENTS, [
                static::ATTRIBUTE_MESSAGE => static::MESSAGE,
                static::ATTRIBUTE_USERNAME => 'Someone Else',
            ]),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_CREATED);
        $this->assertSame(
            static::ACTING_USER_DISPLAY_NAME,
            $this->getResourceAttributes($response)[static::ATTRIBUTE_USERNAME] ?? null,
        );
    }

    public function testGivenABlankMessageWhenPostCommentThenUnprocessableEntityIsReturned(): void
    {
        // Arrange
        [, $saveOrderTransfer] = $this->tester->haveCustomerWithOrder();
        $this->tester->actingAsUser($this->haveActingUser());

        // Act
        $response = $this->postComment($saveOrderTransfer->getOrderReferenceOrFail(), '');

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testGivenAMessageOverTheLengthLimitWhenPostCommentThenUnprocessableEntityIsReturned(): void
    {
        // Arrange
        [, $saveOrderTransfer] = $this->tester->haveCustomerWithOrder();
        $this->tester->actingAsUser($this->haveActingUser());

        // Act
        $response = $this->postComment(
            $saveOrderTransfer->getOrderReferenceOrFail(),
            str_repeat('a', static::MESSAGE_LENGTH_OVER_LIMIT),
        );

        // Assert
        $this->assertValidationFailedForAttribute($response, static::ATTRIBUTE_MESSAGE);
    }

    public function testGivenAnUnknownOrderReferenceWhenPostCommentThenNotFoundIsReturned(): void
    {
        // Arrange
        $this->tester->actingAsUser($this->haveActingUser());

        // Act
        $response = $this->postComment(static::UNKNOWN_ORDER_REFERENCE, static::MESSAGE);

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_NOT_FOUND);
    }

    public function testGivenNoCredentialsWhenPostCommentThenUnauthorizedIsReturned(): void
    {
        // Arrange
        [, $saveOrderTransfer] = $this->tester->haveCustomerWithOrder();

        // Act
        $response = $this->postComment($saveOrderTransfer->getOrderReferenceOrFail(), static::MESSAGE);

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_UNAUTHORIZED);
    }

    public function testGivenAnOperatorTheAclRefusesWhenPostCommentThenItRespondsForbidden(): void
    {
        // Arrange
        $this->tester->actingAsUserWithoutAclAccess();

        // Act
        $response = $this->postComment(static::UNKNOWN_ORDER_REFERENCE, static::MESSAGE);

        // Assert — authorization is decided before the order is looked up, so this is 403, not 404.
        $this->assertRespondsWithStatus($response, Response::HTTP_FORBIDDEN);
    }

    public function testGivenSeveralCommentsWhenGetCommentsThenTheyAreReportedOldestFirst(): void
    {
        // Arrange
        [, $saveOrderTransfer] = $this->tester->haveCustomerWithOrder();
        $orderReference = $saveOrderTransfer->getOrderReferenceOrFail();
        $this->tester->actingAsUser($this->haveActingUser());

        $this->assertRespondsWithStatus($this->postComment($orderReference, static::MESSAGE), Response::HTTP_CREATED);
        $this->assertRespondsWithStatus($this->postComment($orderReference, static::MESSAGE_SECOND), Response::HTTP_CREATED);

        // Act
        $response = $this->handleApiRequest('GET', $this->getCommentsUrl($orderReference));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertSame([static::MESSAGE, static::MESSAGE_SECOND], $this->extractMessages($response));
    }

    public function testGivenACommentedOrderWhenGetSingleOrderThenCommentsAreReported(): void
    {
        // Arrange
        [, $saveOrderTransfer] = $this->tester->haveCustomerWithOrder();
        $orderReference = $saveOrderTransfer->getOrderReferenceOrFail();
        $this->tester->actingAsUser($this->haveActingUser());
        $this->assertRespondsWithStatus($this->postComment($orderReference, static::MESSAGE), Response::HTTP_CREATED);

        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getOrderUrl($orderReference));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $comments = $this->getResourceAttributes($response)[static::ATTRIBUTE_COMMENTS] ?? null;

        $this->assertIsArray($comments, (string)$response->getContent());
        $this->assertCount(1, $comments);
        $this->assertSame(static::MESSAGE, $comments[0][static::ATTRIBUTE_MESSAGE] ?? null);
        $this->assertSame(static::ACTING_USER_DISPLAY_NAME, $comments[0][static::ATTRIBUTE_USERNAME] ?? null);
    }

    public function testGivenACommentedOrderWhenGetOrderCollectionThenCommentsAreOmitted(): void
    {
        // Arrange
        [$customerTransfer, $saveOrderTransfer] = $this->tester->haveCustomerWithOrder();
        $this->tester->actingAsUser($this->haveActingUser());
        $this->assertRespondsWithStatus(
            $this->postComment($saveOrderTransfer->getOrderReferenceOrFail(), static::MESSAGE),
            Response::HTTP_CREATED,
        );

        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getOrderCollectionUrl([
            'customerReference' => $customerTransfer->getCustomerReferenceOrFail(),
        ]));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertArrayNotHasKey(
            static::ATTRIBUTE_COMMENTS,
            $this->getFirstResourceAttributes($response),
            'The orders collection must not report comments.',
        );
    }

    public function testGivenTheShortNameRouteWhenRequestedThenItIsNotPublished(): void
    {
        // Arrange
        $this->tester->actingAsUser($this->haveActingUser());

        // Act
        $response = $this->handleApiRequest('GET', '/order-comments');

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_NOT_FOUND);
    }

    protected function postComment(string $orderReference, string $message): Response
    {
        return $this->handleApiRequest(
            'POST',
            $this->getCommentsUrl($orderReference),
            $this->encodeJsonApiBody(static::RESOURCE_ORDER_COMMENTS, [static::ATTRIBUTE_MESSAGE => $message]),
        );
    }

    protected function haveActingUser(): UserTransfer
    {
        return $this->tester->haveUser([
            UserTransfer::USERNAME => sprintf(static::ACTING_USER_USERNAME_FORMAT, uniqid()),
            UserTransfer::FIRST_NAME => static::ACTING_USER_FIRST_NAME,
            UserTransfer::LAST_NAME => static::ACTING_USER_LAST_NAME,
        ]);
    }

    /**
     * @return list<string>
     */
    protected function extractMessages(Response $response): array
    {
        $messages = [];

        foreach ($this->getJsonApiMembers($response, static::JSON_API_KEY_DATA) as $member) {
            $messages[] = (string)($member[static::JSON_API_KEY_ATTRIBUTES][static::ATTRIBUTE_MESSAGE] ?? '');
        }

        return $messages;
    }

    /**
     * @return non-empty-string
     */
    protected function getCommentsUrl(string $orderReference): string
    {
        return sprintf('/orders/%s/comments', $orderReference);
    }
}
