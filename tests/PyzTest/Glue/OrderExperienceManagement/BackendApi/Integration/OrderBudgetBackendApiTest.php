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
 * `budget` on the `orders` resource, end to end over a booted GLUE_BACKEND kernel.
 *
 * The field belongs to PurchasingControl from end to end and OrderExperienceManagement names it
 * nowhere. Four separate registrations carry it, and the unit tests either side of them each see
 * only one: the intake plugin that resolves `budgetUuid` onto the quote, the checkout saver that
 * writes the assignment onto the order, the Sales order-hydration plugin that reads it back onto
 * `OrderTransfer`, and the `orders` resource expander plugin that shapes it into the payload.
 *
 * That last one is why this suite is the right home for these cases. It reaches the response through
 * the `#[Plugins]` attribute, the `{Organization}\{Layer}\{Module}\{Module}DependencyProvider`
 * namespace convention and the project's `Pyz\Glue\OrderExperienceManagement` override — a chain
 * resolved at container build time, by nothing a unit test constructs. Unregister the plugin, or
 * misname the getter it is registered under, and every unit test still passes while `budget`
 * silently disappears from the API. These cases fail instead.
 *
 * Auto-generated group annotations
 *
 * @group PyzTest
 * @group Glue
 * @group OrderExperienceManagement
 * @group BackendApi
 * @group Integration
 * @group OrderBudgetBackendApiTest
 * Add your own group annotations below this line
 */
class OrderBudgetBackendApiTest extends AbstractOrderExperienceManagementBackendApiTestCase
{
    protected const string ATTRIBUTE_BUDGET = 'budget';

    /**
     * Write-only on the schema (`writable: true, readable: false`), so it selects the budget and is
     * never echoed back — the resolved assignment reads back as `budget` instead.
     */
    protected const string ATTRIBUTE_BUDGET_UUID = 'budgetUuid';

    public function testGivenABudgetUuidWhenCreateOrderThenTheResolvedBudgetIsReported(): void
    {
        // Arrange
        $budgetTransfer = $this->tester->haveActiveBudget();
        [, $attributes] = $this->tester->haveValidOrderPayload([
            static::ATTRIBUTE_BUDGET_UUID => $budgetTransfer->getUuidOrFail(),
        ]);
        $this->tester->actingAsUser();

        // Act
        $response = $this->createOrderViaApi($attributes);

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_CREATED);

        $responseAttributes = $this->getResourceAttributes($response);
        $budget = $responseAttributes[static::ATTRIBUTE_BUDGET] ?? null;

        $this->assertIsArray($budget, 'The POST response reports the budget the order was charged against.');
        $this->assertSame($budgetTransfer->getUuidOrFail(), $budget['uuid'] ?? null);
        $this->assertSame($budgetTransfer->getNameOrFail(), $budget['name'] ?? null);
        $this->assertSame($budgetTransfer->getAmountOrFail(), $budget['amount'] ?? null);
        $this->assertSame($budgetTransfer->getCurrencyIsoCodeOrFail(), $budget['currencyIsoCode'] ?? null);

        $this->assertArrayNotHasKey(
            static::ATTRIBUTE_BUDGET_UUID,
            $responseAttributes,
            'budgetUuid is write-only and must never be echoed back.',
        );
    }

    /**
     * The read route resolves the budget from the ORDER, not from the request that placed it — which
     * is the whole reason `budgetUuid` can be write-only. A GET issued after the fact has no request
     * to echo, so anything it reports came back out of the database through the plugin chain.
     */
    public function testGivenAnOrderPlacedAgainstABudgetWhenGetOrderThenTheBudgetIsResolvedFromTheOrder(): void
    {
        // Arrange
        $budgetTransfer = $this->tester->haveActiveBudget();
        [, $attributes] = $this->tester->haveValidOrderPayload([
            static::ATTRIBUTE_BUDGET_UUID => $budgetTransfer->getUuidOrFail(),
        ]);
        $this->tester->actingAsUser();

        $createResponse = $this->createOrderViaApi($attributes);
        $this->assertRespondsWithStatus($createResponse, Response::HTTP_CREATED);

        $orderReference = (string)($this->decodeJsonApi($createResponse)[static::JSON_API_KEY_DATA][static::JSON_API_KEY_ID] ?? '');
        $this->assertNotSame('', $orderReference);

        // Act
        $getResponse = $this->handleApiRequest('GET', $this->tester->getOrderUrl($orderReference));

        // Assert
        $this->assertRespondsWithStatus($getResponse, Response::HTTP_OK);

        $budget = $this->getResourceAttributes($getResponse)[static::ATTRIBUTE_BUDGET] ?? null;

        $this->assertIsArray($budget, 'The GET response re-resolves the budget from the placed order.');
        $this->assertSame($budgetTransfer->getUuidOrFail(), $budget['uuid'] ?? null);
        $this->assertSame($budgetTransfer->getAmountOrFail(), $budget['amount'] ?? null);
    }

    /**
     * The POST response and a later GET must describe the same placed order the same way. They are
     * built from different starting points — the submitted resource on one side, a fresh read on the
     * other — and `budget` reaches both only because the same expander stack runs over each.
     */
    public function testGivenAnOrderPlacedAgainstABudgetWhenCreateAndGetAreComparedThenTheyReportTheSameBudget(): void
    {
        // Arrange
        $budgetTransfer = $this->tester->haveActiveBudget();
        [, $attributes] = $this->tester->haveValidOrderPayload([
            static::ATTRIBUTE_BUDGET_UUID => $budgetTransfer->getUuidOrFail(),
        ]);
        $this->tester->actingAsUser();

        // Act
        $createResponse = $this->createOrderViaApi($attributes);
        $orderReference = (string)($this->decodeJsonApi($createResponse)[static::JSON_API_KEY_DATA][static::JSON_API_KEY_ID] ?? '');
        $getResponse = $this->handleApiRequest('GET', $this->tester->getOrderUrl($orderReference));

        // Assert
        $createdBudget = $this->getResourceAttributes($createResponse)[static::ATTRIBUTE_BUDGET] ?? null;

        // Asserted before the comparison, which two absent budgets would otherwise satisfy — the
        // exact reading under which an unregistered plugin looks like agreement.
        $this->assertIsArray($createdBudget);

        $this->assertSame(
            $createdBudget,
            $this->getResourceAttributes($getResponse)[static::ATTRIBUTE_BUDGET] ?? null,
            'POST and GET must report the same budget for the same order.',
        );
    }

    /**
     * Most orders name no budget, and the plugin must report that as nothing at all rather than an
     * empty object: `budget` stays null and `skip_null_values` drops the key. A plugin that wrote a
     * default would show up here.
     */
    public function testGivenNoBudgetUuidWhenCreateOrderThenNoBudgetIsReported(): void
    {
        // Arrange
        [, $attributes] = $this->tester->haveValidOrderPayload();
        $this->tester->actingAsUser();

        // Act
        $response = $this->createOrderViaApi($attributes);

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_CREATED);
        $this->assertArrayNotHasKey(static::ATTRIBUTE_BUDGET, $this->getResourceAttributes($response));
    }

    /**
     * A uuid naming no budget is reported as a validation issue on the field that carried it, not
     * ignored — an order placed against no budget at all leaves the spend invisible to whoever set
     * the budget up.
     */
    public function testGivenAnUnknownBudgetUuidWhenCreateOrderThenTheOrderIsRejected(): void
    {
        // Arrange
        [, $attributes] = $this->tester->haveValidOrderPayload([
            static::ATTRIBUTE_BUDGET_UUID => $this->tester->getUnknownBudgetUuid(),
        ]);
        $this->tester->actingAsUser();

        // Act
        $response = $this->createOrderViaApi($attributes);

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_UNPROCESSABLE_ENTITY);
    }
}
