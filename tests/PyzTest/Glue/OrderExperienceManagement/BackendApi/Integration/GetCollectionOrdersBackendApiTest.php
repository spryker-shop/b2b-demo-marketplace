<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace PyzTest\Glue\OrderExperienceManagement\BackendApi\Integration;

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
 * @group GetCollectionOrdersBackendApiTest
 * Add your own group annotations below this line
 */
class GetCollectionOrdersBackendApiTest extends AbstractOrderExperienceManagementBackendApiTestCase
{
    protected const int PLACED_ORDER_COUNT = 2;

    protected const string ITEM_STATE_UNDER_TEST = 'test-filter-state-a';

    protected const string ITEM_STATE_OTHER = 'test-filter-state-b';

    public function testGivenAnAuthenticatedOperatorWhenFilterByCustomerReferenceThenOnlyThatCustomersOrdersAreReturned(): void
    {
        // Arrange
        [$customerTransfer, $firstSaveOrderTransfer, $secondSaveOrderTransfer] = $this->tester->haveCustomerWithTwoOrders();
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getOrderCollectionUrl([
            'customerReference' => $customerTransfer->getCustomerReferenceOrFail(),
        ]));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);

        $orderReferences = $this->getResourceIds($response);
        $this->assertCount(
            static::PLACED_ORDER_COUNT,
            $orderReferences,
            'The customerReference filter isolates exactly the two orders this test placed.',
        );
        $this->assertContains($firstSaveOrderTransfer->getOrderReferenceOrFail(), $orderReferences);
        $this->assertContains($secondSaveOrderTransfer->getOrderReferenceOrFail(), $orderReferences);
    }

    public function testGivenAnOrderReferenceFilterWhenSearchOrdersThenOnlyThatOrderIsReturned(): void
    {
        // Arrange
        [, $firstSaveOrderTransfer] = $this->tester->haveCustomerWithTwoOrders();
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getOrderCollectionUrl([
            'orderReference' => $firstSaveOrderTransfer->getOrderReferenceOrFail(),
        ]));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertSame(
            [$firstSaveOrderTransfer->getOrderReferenceOrFail()],
            $this->getResourceIds($response),
        );
    }

    public function testGivenCommaSeparatedOrderReferencesWhenSearchOrdersThenAllOfThemAreReturned(): void
    {
        // Arrange
        [, $firstSaveOrderTransfer, $secondSaveOrderTransfer] = $this->tester->haveCustomerWithTwoOrders();
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getOrderCollectionUrl([
            'orderReference' => sprintf(
                '%s,%s',
                $firstSaveOrderTransfer->getOrderReferenceOrFail(),
                $secondSaveOrderTransfer->getOrderReferenceOrFail(),
            ),
        ]));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);

        $orderReferences = $this->getResourceIds($response);
        $this->assertContains($firstSaveOrderTransfer->getOrderReferenceOrFail(), $orderReferences);
        $this->assertContains($secondSaveOrderTransfer->getOrderReferenceOrFail(), $orderReferences);
    }

    public function testGivenAnOrderCollectionWhenSearchOrdersThenItemsAreOmittedAndCountedInstead(): void
    {
        // Arrange
        [$customerTransfer] = $this->tester->haveCustomerWithTwoOrders();
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getOrderCollectionUrl([
            'customerReference' => $customerTransfer->getCustomerReferenceOrFail(),
        ]));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);

        $attributes = $this->getFirstResourceAttributes($response);
        $this->assertArrayNotHasKey('items', $attributes, 'The collection representation omits line items.');
        $this->assertArrayHasKey('itemsCount', $attributes, 'itemsCount stands in for the omitted items.');
    }

    public function testGivenAFilterFieldThatDoesNotExistWhenSearchOrdersThenItIsRejected(): void
    {
        // Arrange
        $this->tester->haveCustomerWithTwoOrders();
        $this->tester->actingAsUser();

        // Act — `itemStte` is a typo for `itemState`; the raw bag bypasses the helper's wrapping.
        $response = $this->handleApiRequest('GET', $this->tester->getOrderCollectionUrl([
            'filter' => ['orders.itemStte' => static::ITEM_STATE_UNDER_TEST],
        ]));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_BAD_REQUEST);
        $this->assertStringContainsString(
            'itemStte',
            (string)$response->getContent(),
            'The rejection must name the offending field so the typo is self-correcting.',
        );
    }

    public function testGivenAFilterKeyWithoutTheResourcePrefixWhenSearchOrdersThenItIsRejected(): void
    {
        // Arrange
        $this->tester->haveCustomerWithTwoOrders();
        $this->tester->actingAsUser();

        // Act — a correct field name, but missing the `orders.` prefix.
        $response = $this->handleApiRequest('GET', $this->tester->getOrderCollectionUrl([
            'filter' => ['itemState' => static::ITEM_STATE_UNDER_TEST],
        ]));

        // Assert — a silently dropped filter would answer 200 with every order, which reads as success.
        $this->assertRespondsWithStatus($response, Response::HTTP_BAD_REQUEST);
    }

    /**
     * @uses \Spryker\ApiPlatform\ResponseTransform\PaginationLinksTransform::resolveItemsPerPage()
     */
    public function testGivenAFlatPageParameterWhenSearchOrdersThenThatPageIsReturnedWithoutError(): void
    {
        // Arrange
        [$customerTransfer] = $this->tester->haveCustomerWithTwoOrders();
        $this->tester->actingAsUser();

        // Act — a flat `page` must stay inert rather than crash (see docblock).
        $flatPageResponse = $this->handleApiRequest('GET', $this->tester->getOrderCollectionUrl([
            'customerReference' => $customerTransfer->getCustomerReferenceOrFail(),
            'perPage' => 1,
            'page' => 1,
            'sort' => 'createdAt',
        ]));

        // Act — the real pagination controls, which must genuinely truncate and page through.
        $firstPageResponse = $this->handleApiRequest('GET', $this->tester->getOrderCollectionUrl([
            'customerReference' => $customerTransfer->getCustomerReferenceOrFail(),
            'page' => ['limit' => 1, 'offset' => 0],
            'sort' => 'createdAt',
        ]));
        $secondPageResponse = $this->handleApiRequest('GET', $this->tester->getOrderCollectionUrl([
            'customerReference' => $customerTransfer->getCustomerReferenceOrFail(),
            'page' => ['limit' => 1, 'offset' => 1],
            'sort' => 'createdAt',
        ]));

        // Assert
        $this->assertRespondsWithStatus($flatPageResponse, Response::HTTP_OK);
        $this->assertRespondsWithStatus($firstPageResponse, Response::HTTP_OK);
        $this->assertRespondsWithStatus($secondPageResponse, Response::HTTP_OK);

        $firstPageReferences = $this->getResourceIds($firstPageResponse);
        $secondPageReferences = $this->getResourceIds($secondPageResponse);

        $this->assertCount(1, $firstPageReferences, 'page[limit]=1 truncates the page to a single order.');
        $this->assertCount(1, $secondPageReferences, 'The second order is reachable at page[offset]=1.');
        $this->assertNotSame(
            $firstPageReferences,
            $secondPageReferences,
            'The second page must not repeat the first — that is what a silently ignored offset looks like.',
        );
    }

    public function testGivenAnItemStateFilterWhenSearchOrdersThenOnlyOrdersHoldingThatStateAreReturned(): void
    {
        // Arrange
        [$customerTransfer, $matchingSaveOrderTransfer, $otherSaveOrderTransfer] = $this->tester->haveCustomerWithTwoOrders();

        $this->tester->setOrderItemStates(
            $matchingSaveOrderTransfer->getOrderReferenceOrFail(),
            static::ITEM_STATE_UNDER_TEST,
        );
        $this->tester->setOrderItemStates(
            $otherSaveOrderTransfer->getOrderReferenceOrFail(),
            static::ITEM_STATE_OTHER,
        );

        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getOrderCollectionUrl([
            'customerReference' => $customerTransfer->getCustomerReferenceOrFail(),
            'itemState' => static::ITEM_STATE_UNDER_TEST,
        ]));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertSame(
            [$matchingSaveOrderTransfer->getOrderReferenceOrFail()],
            $this->getResourceIds($response),
        );
        $this->assertContains(
            static::ITEM_STATE_UNDER_TEST,
            $this->getFirstResourceAttributes($response)['itemStates'] ?? [],
            'The order the filter returned reports the filtered state in its own itemStates.',
        );
    }

    public function testGivenCommaSeparatedItemStatesWhenSearchOrdersThenOrdersInAnyOfThemAreReturned(): void
    {
        // Arrange
        [$customerTransfer, $firstSaveOrderTransfer, $secondSaveOrderTransfer] = $this->tester->haveCustomerWithTwoOrders();

        $this->tester->setOrderItemStates(
            $firstSaveOrderTransfer->getOrderReferenceOrFail(),
            static::ITEM_STATE_UNDER_TEST,
        );
        $this->tester->setOrderItemStates(
            $secondSaveOrderTransfer->getOrderReferenceOrFail(),
            static::ITEM_STATE_OTHER,
        );

        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getOrderCollectionUrl([
            'customerReference' => $customerTransfer->getCustomerReferenceOrFail(),
            'itemState' => sprintf('%s,%s', static::ITEM_STATE_UNDER_TEST, static::ITEM_STATE_OTHER),
        ]));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);

        $orderReferences = $this->getResourceIds($response);
        $this->assertContains($firstSaveOrderTransfer->getOrderReferenceOrFail(), $orderReferences);
        $this->assertContains($secondSaveOrderTransfer->getOrderReferenceOrFail(), $orderReferences);
    }

    public function testGivenOnlyOneLineInTheFilteredStateWhenSearchOrdersThenTheOrderIsReturnedOnce(): void
    {
        // Arrange
        [$customerTransfer, $saveOrderTransfer] = $this->tester->haveCustomerWithTwoItemOrder();

        $orderItems = $saveOrderTransfer->getOrderItems();
        $this->assertGreaterThan(1, $orderItems->count(), 'The fixture must carry more than one line.');

        $this->tester->setOrderItemStates(
            $saveOrderTransfer->getOrderReferenceOrFail(),
            static::ITEM_STATE_UNDER_TEST,
            [$orderItems->offsetGet(0)->getUuidOrFail()],
        );

        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getOrderCollectionUrl([
            'customerReference' => $customerTransfer->getCustomerReferenceOrFail(),
            'itemState' => static::ITEM_STATE_UNDER_TEST,
        ]));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertSame(
            [$saveOrderTransfer->getOrderReferenceOrFail()],
            $this->getResourceIds($response),
        );
    }

    public function testGivenAnUnknownItemStateWhenSearchOrdersThenAnEmptyCollectionIsReturned(): void
    {
        // Arrange
        [$customerTransfer] = $this->tester->haveCustomerWithTwoOrders();
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getOrderCollectionUrl([
            'customerReference' => $customerTransfer->getCustomerReferenceOrFail(),
            'itemState' => 'no-such-oms-state',
        ]));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertSame([], $this->getResourceIds($response));
    }

    public function testGivenAnUnknownCustomerReferenceWhenSearchOrdersThenAnEmptyCollectionIsReturned(): void
    {
        // Arrange
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getOrderCollectionUrl([
            'customerReference' => $this->tester->getUnknownCustomerReference(),
        ]));

        // Assert — an unmatched filter is an empty result, not a 404.
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertSame([], $this->getResourceIds($response));
    }

    public function testGivenNoAuthenticationWhenSearchOrdersThenItRespondsUnauthorized(): void
    {
        // Act — intentionally unauthenticated.
        $response = $this->handleApiRequest('GET', $this->tester->getOrderCollectionUrl());

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_UNAUTHORIZED);
    }

    public function testGivenAnInvalidTokenWhenSearchOrdersThenItRespondsUnauthorized(): void
    {
        // Arrange
        $this->tester->actingWithInvalidToken();

        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getOrderCollectionUrl());

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_UNAUTHORIZED);
    }

    public function testGivenAnOperatorTheAclRefusesWhenSearchOrdersThenItRespondsForbidden(): void
    {
        // Arrange
        $this->tester->actingAsUserWithoutAclAccess();

        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getOrderCollectionUrl());

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_FORBIDDEN);
    }
}
