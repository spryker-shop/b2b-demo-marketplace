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
 * @group OrderBudgetBackendApiTest
 * Add your own group annotations below this line
 */
class OrderBudgetBackendApiTest extends AbstractOrderExperienceManagementBackendApiTestCase
{
    protected const string ATTRIBUTE_BUDGET = 'budget';

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
