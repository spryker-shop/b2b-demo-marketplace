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
 * @group TriggerOrderTransitionBackendApiTest
 * Add your own group annotations below this line
 */
class TriggerOrderTransitionBackendApiTest extends AbstractOrderExperienceManagementBackendApiTestCase
{
    protected const string UNKNOWN_ITEM_UUID = 'ffffffff-ffff-ffff-ffff-ffffffffffff';

    protected const string RESOURCE_ORDER_TRANSITIONS = 'order-transitions';

    public function testGivenAnAuthenticatedOperatorWhenPostTransitionThenTheRouteResolvesToTheProcessor(): void
    {
        // Arrange
        [, $saveOrderTransfer] = $this->tester->haveCustomerWithOrder();
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest(
            'POST',
            $this->getTransitionsUrl($saveOrderTransfer->getOrderReferenceOrFail()),
            $this->buildTransitionRequestBody(['event' => $this->readFirstAvailableEvent($saveOrderTransfer->getOrderReferenceOrFail())]),
        );

        // Assert
        $this->assertNotSame(
            Response::HTTP_NOT_FOUND,
            $response->getStatusCode(),
            'A 404 here means the generated route is not /orders/{orderReference}/transitions.',
        );
    }

    public function testGivenAnEventReadFromAvailableEventsWhenPostTransitionThenItIsApplied(): void
    {
        // Arrange
        [, $saveOrderTransfer] = $this->tester->haveCustomerWithOrder();
        $orderReference = $saveOrderTransfer->getOrderReferenceOrFail();
        $this->tester->actingAsUser();

        $event = $this->readFirstAvailableEvent($orderReference);
        $this->assertNotNull($event, 'The fixture order must offer at least one available transition.');

        // Act
        $response = $this->handleApiRequest(
            'POST',
            $this->getTransitionsUrl($orderReference),
            $this->buildTransitionRequestBody(['event' => $event]),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);

        $attributes = $this->getResourceAttributes($response);
        $this->assertNotEmpty($attributes['items'] ?? [], 'Every item in scope must be reported.');

        foreach ($attributes['items'] as $item) {
            $this->assertArrayHasKey('outcome', $item);
            $this->assertContains($item['outcome'], ['transitioned', 'unchanged', 'skipped']);
            $this->assertArrayHasKey('stateBefore', $item);
        }
    }

    public function testGivenTheEventWasAlreadyAppliedWhenPostTransitionAgainThenItIsRejectedRatherThanReportedApplied(): void
    {
        // Arrange
        [, $saveOrderTransfer] = $this->tester->haveCustomerWithOrder();
        $orderReference = $saveOrderTransfer->getOrderReferenceOrFail();
        $this->tester->actingAsUser();

        $event = $this->readFirstAvailableEvent($orderReference);
        $this->handleApiRequest(
            'POST',
            $this->getTransitionsUrl($orderReference),
            $this->buildTransitionRequestBody(['event' => $event]),
        );

        // Act
        $response = $this->handleApiRequest(
            'POST',
            $this->getTransitionsUrl($orderReference),
            $this->buildTransitionRequestBody(['event' => $event]),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testGivenAUuidReadFromTheOrderWhenPostTransitionForThatUuidThenItIsApplied(): void
    {
        // Arrange
        [, $saveOrderTransfer] = $this->tester->haveCustomerWithOrder();
        $orderReference = $saveOrderTransfer->getOrderReferenceOrFail();
        $this->tester->actingAsUser();

        $items = $this->readOrderItems($orderReference);
        $this->assertNotEmpty($items);

        $uuid = $items[0]['uuid'];
        $event = $items[0]['availableEvents'][0] ?? null;
        $this->assertNotNull($event, 'The fixture line must offer at least one available transition.');

        // Act — the uuid and the event both came out of the GET a moment ago.
        $response = $this->handleApiRequest(
            'POST',
            $this->getTransitionsUrl($orderReference),
            $this->buildTransitionRequestBody(['event' => $event, 'itemUuids' => [$uuid]]),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);

        $attributes = $this->getResourceAttributes($response);
        $this->assertCount(1, $attributes['items']);
        $this->assertSame($uuid, $attributes['items'][0]['uuid']);
        $this->assertSame('transitioned', $attributes['items'][0]['outcome']);
        $this->assertNotSame(
            $attributes['items'][0]['stateBefore'],
            $attributes['items'][0]['state'],
            'A transitioned line must report a state different from the one it started in.',
        );
    }

    public function testGivenAnItemUuidOfAnotherOrderWhenPostTransitionThenTheOtherOrderIsLeftUntouched(): void
    {
        // Arrange
        [, $firstSaveOrderTransfer, $secondSaveOrderTransfer] = $this->tester->haveCustomerWithTwoOrders();
        $targetOrderReference = $firstSaveOrderTransfer->getOrderReferenceOrFail();
        $foreignOrderReference = $secondSaveOrderTransfer->getOrderReferenceOrFail();
        $this->tester->actingAsUser();

        $foreignItems = $this->readOrderItems($foreignOrderReference);
        $this->assertNotEmpty($foreignItems);

        $foreignUuid = $foreignItems[0]['uuid'];
        $foreignStateBefore = $foreignItems[0]['state'];
        $event = $foreignItems[0]['availableEvents'][0] ?? null;
        $this->assertNotNull($event);

        // Act — a real, firable event, but aimed at an order that does not own the line.
        $response = $this->handleApiRequest(
            'POST',
            $this->getTransitionsUrl($targetOrderReference),
            $this->buildTransitionRequestBody(['event' => $event, 'itemUuids' => [$foreignUuid]]),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_UNPROCESSABLE_ENTITY);

        $foreignItemsAfter = $this->readOrderItems($foreignOrderReference);
        $this->assertSame(
            $foreignStateBefore,
            $foreignItemsAfter[0]['state'],
            'The other order advanced, so the tenancy check let a foreign item reach the state machine.',
        );
    }

    public function testGivenATwoItemOrderWhenPostTransitionForOneUuidThenOnlyThatLineAdvances(): void
    {
        // Arrange
        [, $saveOrderTransfer] = $this->tester->haveCustomerWithTwoItemOrder();
        $orderReference = $saveOrderTransfer->getOrderReferenceOrFail();
        $this->tester->actingAsUser();

        $items = $this->readOrderItems($orderReference);
        $this->assertCount(2, $items, 'This case needs a two-line order; the fixture did not produce one.');

        $targetUuid = $items[0]['uuid'];
        $untargetedUuid = $items[1]['uuid'];
        $untargetedStateBefore = $items[1]['state'];
        $event = $items[0]['availableEvents'][0] ?? null;
        $this->assertNotNull($event);

        // Act
        $response = $this->handleApiRequest(
            'POST',
            $this->getTransitionsUrl($orderReference),
            $this->buildTransitionRequestBody(['event' => $event, 'itemUuids' => [$targetUuid]]),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);

        $attributes = $this->getResourceAttributes($response);
        $this->assertCount(1, $attributes['items'], 'Only the targeted line is in scope, so only it is reported.');
        $this->assertSame($targetUuid, $attributes['items'][0]['uuid']);

        $itemsAfter = $this->indexItemsByUuid($this->readOrderItems($orderReference));
        $this->assertSame(
            $untargetedStateBefore,
            $itemsAfter[$untargetedUuid]['state'],
            'The line the request did not name was advanced anyway.',
        );
    }

    public function testGivenAMixedStateOrderWhenPostTransitionWithOmittedListThenEligibleLinesAdvanceAndTheRestAreSkipped(): void
    {
        // Arrange
        [, $saveOrderTransfer] = $this->tester->haveCustomerWithTwoItemOrder();
        $orderReference = $saveOrderTransfer->getOrderReferenceOrFail();
        $this->tester->actingAsUser();

        $items = $this->readOrderItems($orderReference);
        $this->assertCount(2, $items, 'This case needs a two-line order; the fixture did not produce one.');

        $event = $items[0]['availableEvents'][0] ?? null;
        $this->assertNotNull($event);

        $this->handleApiRequest(
            'POST',
            $this->getTransitionsUrl($orderReference),
            $this->buildTransitionRequestBody(['event' => $event, 'itemUuids' => [$items[0]['uuid']]]),
        );

        // Act — the whole order this time, with one line already past the event.
        $response = $this->handleApiRequest(
            'POST',
            $this->getTransitionsUrl($orderReference),
            $this->buildTransitionRequestBody(['event' => $event]),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);

        $attributes = $this->getResourceAttributes($response);
        $this->assertCount(2, $attributes['items'], 'Both lines are in scope, so both are reported.');

        $outcomes = array_column($attributes['items'], 'outcome', 'uuid');
        $this->assertSame('skipped', $outcomes[$items[0]['uuid']], 'The already-advanced line is skipped.');
        $this->assertSame('transitioned', $outcomes[$items[1]['uuid']], 'The remaining line advances.');
    }

    public function testGivenAMixedStateOrderWhenPostTransitionWithAnExplicitListNamingAnIneligibleLineThenNothingIsApplied(): void
    {
        // Arrange
        [, $saveOrderTransfer] = $this->tester->haveCustomerWithTwoItemOrder();
        $orderReference = $saveOrderTransfer->getOrderReferenceOrFail();
        $this->tester->actingAsUser();

        $items = $this->readOrderItems($orderReference);
        $this->assertCount(2, $items, 'This case needs a two-line order; the fixture did not produce one.');

        $event = $items[0]['availableEvents'][0] ?? null;
        $this->assertNotNull($event);

        $this->handleApiRequest(
            'POST',
            $this->getTransitionsUrl($orderReference),
            $this->buildTransitionRequestBody(['event' => $event, 'itemUuids' => [$items[0]['uuid']]]),
        );

        $statesBefore = array_column($this->readOrderItems($orderReference), 'state', 'uuid');

        // Act — both lines named explicitly, one of which can no longer take the event.
        $response = $this->handleApiRequest(
            'POST',
            $this->getTransitionsUrl($orderReference),
            $this->buildTransitionRequestBody([
                'event' => $event,
                'itemUuids' => [$items[0]['uuid'], $items[1]['uuid']],
            ]),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_UNPROCESSABLE_ENTITY);

        $statesAfter = array_column($this->readOrderItems($orderReference), 'state', 'uuid');
        $this->assertSame(
            $statesBefore,
            $statesAfter,
            'An all-or-nothing rejection still advanced a line, so admission is not actually atomic.',
        );
    }

    public function testGivenAnUnknownOrderReferenceWhenPostTransitionThenItRespondsNotFound(): void
    {
        // Arrange
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest(
            'POST',
            $this->getTransitionsUrl(static::UNKNOWN_ORDER_REFERENCE),
            $this->buildTransitionRequestBody(['event' => 'ship']),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_NOT_FOUND);
    }

    public function testGivenAnItemUuidThatIsNotOnThisOrderWhenPostTransitionThenItIsRejected(): void
    {
        // Arrange
        [, $saveOrderTransfer] = $this->tester->haveCustomerWithOrder();
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest(
            'POST',
            $this->getTransitionsUrl($saveOrderTransfer->getOrderReferenceOrFail()),
            $this->buildTransitionRequestBody(['event' => 'ship', 'itemUuids' => [static::UNKNOWN_ITEM_UUID]]),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testGivenAnUnknownEventWhenPostTransitionThenItIsRejectedAsUnprocessable(): void
    {
        // Arrange
        [, $saveOrderTransfer] = $this->tester->haveCustomerWithOrder();
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest(
            'POST',
            $this->getTransitionsUrl($saveOrderTransfer->getOrderReferenceOrFail()),
            $this->buildTransitionRequestBody(['event' => 'no-such-event']),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testGivenAnEmptyItemUuidsListWhenPostTransitionThenItIsRejected(): void
    {
        // Arrange
        [, $saveOrderTransfer] = $this->tester->haveCustomerWithOrder();
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest(
            'POST',
            $this->getTransitionsUrl($saveOrderTransfer->getOrderReferenceOrFail()),
            $this->buildTransitionRequestBody(['event' => 'ship', 'itemUuids' => []]),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testGivenABlankEventWhenPostTransitionThenItIsRejected(): void
    {
        // Arrange
        [, $saveOrderTransfer] = $this->tester->haveCustomerWithOrder();
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest(
            'POST',
            $this->getTransitionsUrl($saveOrderTransfer->getOrderReferenceOrFail()),
            $this->buildTransitionRequestBody(['event' => '']),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testGivenNoAuthenticationWhenPostTransitionThenItRespondsUnauthorized(): void
    {
        // Act — intentionally unauthenticated, against an unknown order so a leak would show as 404.
        $response = $this->handleApiRequest(
            'POST',
            $this->getTransitionsUrl(static::UNKNOWN_ORDER_REFERENCE),
            $this->buildTransitionRequestBody(['event' => 'ship']),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_UNAUTHORIZED);
    }

    public function testGivenAnOperatorTheAclRefusesWhenPostTransitionThenItRespondsForbidden(): void
    {
        // Arrange
        $this->tester->actingAsUserWithoutAclAccess();

        // Act
        $response = $this->handleApiRequest(
            'POST',
            $this->getTransitionsUrl(static::UNKNOWN_ORDER_REFERENCE),
            $this->buildTransitionRequestBody(['event' => 'ship']),
        );

        // Assert — authorization is decided before the order is looked up, so this is 403, not 404.
        $this->assertRespondsWithStatus($response, Response::HTTP_FORBIDDEN);
    }

    protected function getTransitionsUrl(string $orderReference): string
    {
        return sprintf('%s/transitions', $this->tester->getOrderUrl($orderReference));
    }

    /**
     * @param array<string, mixed> $attributes
     */
    protected function buildTransitionRequestBody(array $attributes): string
    {
        return (string)json_encode([
            'data' => [
                'type' => static::RESOURCE_ORDER_TRANSITIONS,
                'attributes' => $attributes,
            ],
        ]);
    }

    protected function readFirstAvailableEvent(string $orderReference): ?string
    {
        $response = $this->handleApiRequest('GET', $this->tester->getOrderUrl($orderReference));
        $availableEvents = $this->getResourceAttributes($response)['availableEvents'] ?? [];

        return $availableEvents[0] ?? null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function readOrderItems(string $orderReference): array
    {
        $response = $this->handleApiRequest('GET', $this->tester->getOrderUrl($orderReference));

        return array_values($this->getResourceAttributes($response)['items'] ?? []);
    }

    /**
     * @param array<int, array<string, mixed>> $items
     *
     * @return array<string, array<string, mixed>>
     */
    protected function indexItemsByUuid(array $items): array
    {
        $itemsByUuid = [];

        foreach ($items as $item) {
            $itemsByUuid[(string)$item['uuid']] = $item;
        }

        return $itemsByUuid;
    }
}
