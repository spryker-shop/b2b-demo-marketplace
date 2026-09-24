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
 * `GET /orders/{orderReference}` over a booted GLUE_BACKEND kernel.
 *
 * Auto-generated group annotations
 *
 * @group PyzTest
 * @group Glue
 * @group OrderExperienceManagement
 * @group BackendApi
 * @group Integration
 * @group GetSingleOrderBackendApiTest
 * Add your own group annotations below this line
 */
class GetSingleOrderBackendApiTest extends AbstractOrderExperienceManagementBackendApiTestCase
{
    public function testGivenAnAuthenticatedOperatorWhenGetOrderByReferenceThenTheOrderIsReturned(): void
    {
        // Arrange
        [$customerTransfer, $saveOrderTransfer] = $this->tester->haveCustomerWithOrder();
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest(
            'GET',
            $this->tester->getOrderUrl($saveOrderTransfer->getOrderReferenceOrFail()),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);

        $data = $this->decodeJsonApi($response)[static::JSON_API_KEY_DATA];
        $this->assertSame(
            $saveOrderTransfer->getOrderReferenceOrFail(),
            $data[static::JSON_API_KEY_ID],
            'The order is addressed by its order reference, which is also the resource id.',
        );
        $this->assertSame(
            $customerTransfer->getCustomerReferenceOrFail(),
            $this->getResourceAttributes($response)['customerReference'] ?? null,
        );
    }

    /**
     * The item endpoint is the only place line items are served — the collection omits them.
     */
    public function testGivenAnOrderWithItemsWhenGetOrderByReferenceThenItsLineItemsAreIncluded(): void
    {
        // Arrange
        [, $saveOrderTransfer] = $this->tester->haveCustomerWithOrder();
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest(
            'GET',
            $this->tester->getOrderUrl($saveOrderTransfer->getOrderReferenceOrFail()),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);

        $attributes = $this->getResourceAttributes($response);
        $this->assertNotEmpty($attributes['items'] ?? [], 'The item representation serves the order lines.');
        $this->assertSame(
            count($attributes['items']),
            $attributes['itemsCount'] ?? null,
            'itemsCount must agree with the number of lines actually served.',
        );
    }

    /**
     * Every line reports the OMS events legal for its own state. The concrete event names depend on
     * the state machine the fixture's process defines, so what is asserted is the invariant that
     * holds for any of them: the per-line sets are present, and the order-level field is exactly
     * their union.
     */
    public function testGivenAnOrderWithItemsWhenGetOrderByReferenceThenEachLineCarriesItsAvailableTransitions(): void
    {
        // Arrange
        [, $saveOrderTransfer] = $this->tester->haveCustomerWithOrder();
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest(
            'GET',
            $this->tester->getOrderUrl($saveOrderTransfer->getOrderReferenceOrFail()),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);

        $attributes = $this->getResourceAttributes($response);
        $this->assertNotEmpty($attributes['items'] ?? []);

        $perItemEvents = [];

        foreach ($attributes['items'] as $item) {
            $this->assertArrayHasKey(
                'availableEvents',
                $item,
                'Every line must report its own transitions, so a caller never has to guess which line an order-level event applies to.',
            );
            $this->assertIsArray($item['availableEvents']);

            $perItemEvents = array_merge($perItemEvents, $item['availableEvents']);
        }

        $this->assertEqualsCanonicalizing(
            array_values(array_unique($perItemEvents)),
            $attributes['availableEvents'] ?? [],
            'The order-level field is the union over the lines — anything else means the two disagree.',
        );

        // Guards against the assertions above passing vacuously: every event in the fixture's `Test01`
        // process is manual="true", so the manual-only filter must not remove anything here. An empty
        // set would mean the filter is dropping legitimate events on the real path — which the unit
        // tests, working off stubs, cannot detect.
        $this->assertNotEmpty(
            $attributes['availableEvents'] ?? [],
            'The fixture order sits in a state with manual events, so the filtered set must be non-empty.',
        );
    }

    /**
     * D3: `items[].uuid` is the sales-order-item uuid — the value `POST /orders/{ref}/transitions`
     * takes in `orderItemUuids`.
     *
     * The shape assertion is the point. Before D3 the provider returned the order item REFERENCE in
     * this field, a 32-hex string, so a test that only checked "uuid is present" passed against the
     * wrong value; only the format check distinguishes them.
     */
    public function testGivenAnOrderWhenGetOrderByReferenceThenEachLineExposesTheSalesOrderItemUuid(): void
    {
        // Arrange
        [, $saveOrderTransfer] = $this->tester->haveCustomerWithOrder();
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest(
            'GET',
            $this->tester->getOrderUrl($saveOrderTransfer->getOrderReferenceOrFail()),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);

        $attributes = $this->getResourceAttributes($response);
        $this->assertNotEmpty($attributes['items'] ?? []);

        foreach ($attributes['items'] as $item) {
            $this->assertArrayHasKey('uuid', $item);
            $this->assertMatchesRegularExpression(
                '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i',
                (string)$item['uuid'],
                'items[].uuid must be the spy_sales_order_item uuid, not the order item reference.',
            );
        }
    }

    /**
     * `availableEvents` promises events a CLIENT may fire, which is a NARROWER set than the
     * platform's own "manual events" — `Process::getManualEvents()` keeps an event when
     * `isManual() || isOnEnter()`, and an on-enter event is raised by OMS itself.
     *
     * Checked against `OmsFacade::getOrderItemManualEvents()` rather than against a reimplementation
     * of the API's own filter, so this genuinely cross-checks the API against the platform. It proves
     * containment and non-emptiness; that on-enter events specifically are excluded is proven by
     * `AvailableOrderItemTransitionReaderTest`, because the fixture process declares every event
     * `manual="true"` and so cannot exhibit the difference.
     */
    public function testGivenAnOrderWhenGetOrderByReferenceThenAvailableEventsAreWithinThePlatformsManualEvents(): void
    {
        // Arrange
        [, $saveOrderTransfer] = $this->tester->haveCustomerWithOrder();
        $orderReference = $saveOrderTransfer->getOrderReferenceOrFail();
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getOrderUrl($orderReference));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);

        $platformManualEvents = $this->tester->getPlatformManualEventsForOrder($orderReference);
        $reportedEvents = $this->getResourceAttributes($response)['availableEvents'] ?? [];

        $this->assertNotEmpty($reportedEvents);

        foreach ($reportedEvents as $eventName) {
            $this->assertContains(
                $eventName,
                $platformManualEvents,
                sprintf('"%s" is reported as available but the state machine does not offer it.', $eventName),
            );
        }
    }

    /**
     * The surrogate key must never reach a client: orders are addressed by reference alone.
     */
    public function testGivenAnOrderWhenGetOrderByReferenceThenTheInternalOrderIdIsNotExposed(): void
    {
        // Arrange
        [, $saveOrderTransfer] = $this->tester->haveCustomerWithOrder();
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest(
            'GET',
            $this->tester->getOrderUrl($saveOrderTransfer->getOrderReferenceOrFail()),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertArrayNotHasKey('idSalesOrder', $this->getResourceAttributes($response));
    }

    public function testGivenAnUnknownReferenceWhenGetOrderThenItRespondsNotFound(): void
    {
        // Arrange
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getOrderUrl(static::UNKNOWN_ORDER_REFERENCE));

        // Assert — the provider returns null for a miss, which API Platform renders as a plain 404.
        $this->assertRespondsWithStatus($response, Response::HTTP_NOT_FOUND);
    }

    public function testGivenNoAuthenticationWhenGetOrderThenItRespondsUnauthorized(): void
    {
        // Act — intentionally unauthenticated.
        $response = $this->handleApiRequest('GET', $this->tester->getOrderUrl(static::UNKNOWN_ORDER_REFERENCE));

        // Assert — authorization is decided before the order is looked up, so this is 401, not 404.
        $this->assertRespondsWithStatus($response, Response::HTTP_UNAUTHORIZED);
    }

    public function testGivenAnOperatorTheAclRefusesWhenGetOrderThenItRespondsForbidden(): void
    {
        // Arrange
        $this->tester->actingAsUserWithoutAclAccess();

        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getOrderUrl(static::UNKNOWN_ORDER_REFERENCE));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_FORBIDDEN);
    }
}
