<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace PyzTest\Glue\Discount\BackendApi\Integration;

use Orm\Zed\Discount\Persistence\SpyDiscountQuery;
use PyzTest\Glue\Discount\AbstractDiscountBackendApiTestCase;
use PyzTest\Glue\Discount\Helper\DiscountBackendApiHelper;
use Spryker\Glue\Discount\DiscountConfig;
use Spryker\Shared\Discount\DiscountConstants;
use Symfony\Component\HttpFoundation\Response;

/**
 * Auto-generated group annotations
 *
 * @group PyzTest
 * @group Glue
 * @group Discount
 * @group BackendApi
 * @group Integration
 * @group UpdateDiscountBackendApiTest
 * Add your own group annotations below this line
 */
class UpdateDiscountBackendApiTest extends AbstractDiscountBackendApiTestCase
{
    protected const string UNKNOWN_STORE = 'UpdateDiscountUnknownStore';

    protected const string UPDATED_DESCRIPTION = 'Updated by the discount backend api test suite.';

    protected const int UPDATED_PRIORITY = 42;

    protected const int UPDATED_PERCENTAGE_AMOUNT = 15;

    protected const string RESOURCE_KEY_PROMOTION = 'promotion';

    protected const string PROMOTION_KEY_ABSTRACT_SKUS = 'abstractSkus';

    protected const string PROMOTION_KEY_QUANTITY = 'quantity';

    protected const string FIELD_SUB_TOTAL = 'sub-total';

    protected const string FIELD_CURRENCY = 'currency';

    protected const string FIELD_ITEM_QUANTITY = 'item-quantity';

    protected const string ATTRIBUTE_DECISION_RULE = 'decisionRule';

    protected const string ATTRIBUTE_DECISION_RULE_QUERY_STRING = 'decisionRuleQueryString';

    /**
     * The Back Office form stores both logical operators in one group without parentheses; the cart evaluates such a
     * query string from left to right.
     */
    protected const string BACK_OFFICE_DECISION_RULE_QUERY_STRING = "sub-total >= '100' and currency = 'EUR' or item-quantity > '2'";

    protected const string UNREPRESENTABLE_DECISION_RULE_QUERY_STRING = "unknown-field = '1'";

    protected const int PROMOTION_QUANTITY = 2;

    protected const int UPDATED_PROMOTION_QUANTITY = 5;

    protected const int UPDATED_PROMOTION_ABSTRACT_SKU_COUNT = 2;

    protected const string ABSTRACT_SKUS_OF_DISCOUNT = '{abstract skus of the discount}';

    protected const string ABSTRACT_SKUS_OF_PATCH = '{abstract skus of the patch}';

    /**
     * @dataProvider providePartialUpdates
     *
     * @param array<string, mixed> $attributes
     * @param array<string, mixed> $expectedAttributes
     */
    public function testAppliesThePartialUpdateAndKeepsEveryOmittedProperty(
        array $attributes,
        array $expectedAttributes,
    ): void {
        // Arrange
        $this->tester->actingAsUser();
        $uuid = $this->haveDiscountViaApi(['stores' => [$this->tester->getStoreName(), $this->tester->getSecondStoreName()]]);
        $storedAttributes = $this->getResourceAttributes(
            $this->handleApiRequest(static::METHOD_GET, $this->tester->getDiscountUrl($uuid)),
        );

        // Act
        $response = $this->handleApiRequest(
            static::METHOD_PATCH,
            $this->tester->getDiscountUrl($uuid),
            $this->tester->buildDiscountRequestBody($attributes, $uuid),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);

        $updatedAttributes = $this->getResourceAttributes($response);
        $this->assertAttributesMatch($expectedAttributes, $updatedAttributes);
        $this->assertAttributesMatch(
            array_diff_key($storedAttributes, $expectedAttributes, ['collector' => null, 'decisionRule' => null]),
            $updatedAttributes,
        );
        $this->assertAttributesMatch(
            $updatedAttributes,
            $this->getResourceAttributes(
                $this->handleApiRequest(static::METHOD_GET, $this->tester->getDiscountUrl($uuid)),
            ),
        );
    }

    /**
     * @return array<string, array{array<string, mixed>, array<string, mixed>}>
     */
    public function providePartialUpdates(): array
    {
        return [
            'given only a description, when the discount is patched, then every other property survives' => [
                ['description' => self::UPDATED_DESCRIPTION],
                ['description' => self::UPDATED_DESCRIPTION],
            ],
            'given only a priority, when the discount is patched, then every other property survives' => [
                ['priority' => self::UPDATED_PRIORITY],
                ['priority' => self::UPDATED_PRIORITY],
            ],
            'given a discount type change, when the discount is patched, then the new type is stored' => [
                ['discountType' => DiscountConstants::TYPE_VOUCHER],
                ['discountType' => DiscountConstants::TYPE_VOUCHER],
            ],
            'given an empty store list, when the discount is patched, then the store relations are cleared' => [
                ['stores' => []],
                ['stores' => []],
            ],
            'given isActive true, when the discount is patched, then it is activated' => [
                ['isActive' => true, 'description' => self::UPDATED_DESCRIPTION],
                ['isActive' => true, 'description' => self::UPDATED_DESCRIPTION],
            ],
            'given a read only property, when the discount is patched, then it is ignored' => [
                ['createdAt' => '2020-01-01 00:00:00', 'description' => self::UPDATED_DESCRIPTION],
                ['description' => self::UPDATED_DESCRIPTION],
            ],
        ];
    }

    public function testReplacesTheWholeRuleGroupOnPatchAndKeepsItWhenOmitted(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $storedDecisionRule = $this->tester->buildRuleGroup(
            [$this->tester->buildRule(static::FIELD_SUB_TOTAL, '>=', '500')],
            [$this->tester->buildRuleGroup(
                [$this->tester->buildRule(static::FIELD_CURRENCY, '=', 'EUR'), $this->tester->buildRule(static::FIELD_CURRENCY, '=', 'CHF')],
                [],
                DiscountBackendApiHelper::CONDITION_OR,
            )],
        );
        $uuid = $this->haveDiscountViaApi(['decisionRule' => $storedDecisionRule]);
        $patchedDecisionRule = ['condition' => DiscountBackendApiHelper::CONDITION_AND, 'rules' => [$this->tester->buildRule(static::FIELD_SUB_TOTAL, '>=', '1000')]];

        // Act
        $keptResponse = $this->handleApiRequest(
            static::METHOD_PATCH,
            $this->tester->getDiscountUrl($uuid),
            $this->tester->buildDiscountRequestBody(['description' => static::UPDATED_DESCRIPTION], $uuid),
        );
        $replacedResponse = $this->handleApiRequest(
            static::METHOD_PATCH,
            $this->tester->getDiscountUrl($uuid),
            $this->tester->buildDiscountRequestBody(['decisionRule' => $patchedDecisionRule], $uuid),
        );

        // Assert
        $this->assertRespondsWithStatus($keptResponse, Response::HTTP_OK);
        $this->assertSame(
            $this->flattenRuleGroup($storedDecisionRule),
            $this->flattenRuleGroup($this->getResourceAttributes($keptResponse)['decisionRule']),
        );

        $this->assertRespondsWithStatus($replacedResponse, Response::HTTP_OK);
        $this->assertSame(
            $this->flattenRuleGroup($patchedDecisionRule),
            $this->flattenRuleGroup($this->getResourceAttributes($replacedResponse)['decisionRule']),
        );
        $this->assertSame(
            $this->flattenRuleGroup($patchedDecisionRule),
            $this->flattenRuleGroup($this->getResourceAttributes(
                $this->handleApiRequest(static::METHOD_GET, $this->tester->getDiscountUrl($uuid)),
            )['decisionRule']),
        );
    }

    /**
     * @dataProvider provideClearingPatches
     *
     * @param array<string, mixed> $attributes
     */
    public function testTreatsNullAndTheEmptyFormAsClearingOnPatch(array $attributes, ?string $expectedDescription): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $uuid = $this->haveDiscountViaApi(['description' => static::UPDATED_DESCRIPTION]);

        // Act
        $response = $this->patchDiscount($uuid, $attributes);

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertClearedAttributes($this->getResourceAttributes($response), $expectedDescription);
        $this->assertClearedAttributes(
            $this->getResourceAttributes($this->handleApiRequest(static::METHOD_GET, $this->tester->getDiscountUrl($uuid))),
            $expectedDescription,
        );
    }

    /**
     * @return array<string, array{array<string, mixed>, string|null}>
     */
    public function provideClearingPatches(): array
    {
        return [
            'given null for nullable attributes, when the discount is patched, then they are cleared' => [
                ['description' => null, 'decisionRule' => null, 'stores' => null],
                null,
            ],
            'given the empty form of the attributes, when the discount is patched, then they are cleared' => [
                ['description' => '', 'decisionRule' => ['rules' => []], 'stores' => []],
                '',
            ],
        ];
    }

    public function testReadsABackOfficeRuleWithMixedOperatorsAndKeepsItsTextOnAnUnrelatedPatch(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $uuid = $this->haveDiscountViaApi();
        $this->storeDecisionRuleQueryString($uuid, static::BACK_OFFICE_DECISION_RULE_QUERY_STRING);
        $expectedDecisionRule = $this->tester->buildRuleGroup(
            [$this->tester->buildRule(static::FIELD_ITEM_QUANTITY, '>', '2')],
            [
                $this->tester->buildRuleGroup([
                    $this->tester->buildRule(static::FIELD_SUB_TOTAL, '>=', '100'),
                    $this->tester->buildRule(static::FIELD_CURRENCY, '=', 'EUR'),
                ]),
            ],
            DiscountBackendApiHelper::CONDITION_OR,
        );

        // Act
        $readAttributes = $this->getResourceAttributes(
            $this->handleApiRequest(static::METHOD_GET, $this->tester->getDiscountUrl($uuid)),
        );
        $patchResponse = $this->patchDiscount($uuid, ['description' => static::UPDATED_DESCRIPTION]);

        // Assert
        $this->assertSame(
            $this->flattenRuleGroup($expectedDecisionRule),
            $this->flattenRuleGroup((array)$readAttributes[static::ATTRIBUTE_DECISION_RULE]),
        );
        $this->assertRespondsWithStatus($patchResponse, Response::HTTP_OK);
        $this->assertSame(
            static::BACK_OFFICE_DECISION_RULE_QUERY_STRING,
            $this->getResourceAttributes($patchResponse)[static::ATTRIBUTE_DECISION_RULE_QUERY_STRING],
        );
        $this->assertSame(
            static::BACK_OFFICE_DECISION_RULE_QUERY_STRING,
            $this->getResourceAttributes(
                $this->handleApiRequest(static::METHOD_GET, $this->tester->getDiscountUrl($uuid)),
            )[static::ATTRIBUTE_DECISION_RULE_QUERY_STRING],
        );
    }

    /**
     * A stored rule the parser cannot represent is invalid for the discount itself, so a patch that leaves it in place
     * is refused the way the Back Office refuses to save the discount, while a patch that clears it goes through.
     */
    public function testRefusesToKeepAStoredRuleItCannotRepresentUntilNullIsSentForIt(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $uuid = $this->haveDiscountViaApi();
        $this->storeDecisionRuleQueryString($uuid, static::UNREPRESENTABLE_DECISION_RULE_QUERY_STRING);

        // Act
        $readAttributes = $this->getResourceAttributes(
            $this->handleApiRequest(static::METHOD_GET, $this->tester->getDiscountUrl($uuid)),
        );
        $refusedResponse = $this->patchDiscount($uuid, ['description' => static::UPDATED_DESCRIPTION]);
        $storedAttributes = $this->getResourceAttributes(
            $this->handleApiRequest(static::METHOD_GET, $this->tester->getDiscountUrl($uuid)),
        );
        $clearedResponse = $this->patchDiscount($uuid, [static::ATTRIBUTE_DECISION_RULE => null]);

        // Assert
        $this->assertArrayNotHasKey(static::ATTRIBUTE_DECISION_RULE, $readAttributes);
        $this->assertSame(
            static::UNREPRESENTABLE_DECISION_RULE_QUERY_STRING,
            $readAttributes[static::ATTRIBUTE_DECISION_RULE_QUERY_STRING],
        );

        $this->assertRespondsWithErrorCode(
            $refusedResponse,
            Response::HTTP_UNPROCESSABLE_ENTITY,
            DiscountConfig::RESPONSE_CODE_QUERY_STRING_INVALID,
        );
        $this->assertSame(
            static::UNREPRESENTABLE_DECISION_RULE_QUERY_STRING,
            $storedAttributes[static::ATTRIBUTE_DECISION_RULE_QUERY_STRING],
        );

        $this->assertRespondsWithStatus($clearedResponse, Response::HTTP_OK);
        $this->assertSame(
            '',
            $this->getResourceAttributes($clearedResponse)[static::ATTRIBUTE_DECISION_RULE_QUERY_STRING] ?? '',
        );
    }

    protected function storeDecisionRuleQueryString(string $uuid, string $queryString): void
    {
        SpyDiscountQuery::create()
            ->findOneByUuid($uuid)
            ->setDecisionRuleQueryString($queryString)
            ->save();
    }

    /**
     * @param array<string, mixed> $attributes
     */
    protected function assertClearedAttributes(array $attributes, ?string $expectedDescription): void
    {
        $this->assertSame($expectedDescription, $attributes['description'] ?? null);
        $this->assertSame([], $attributes['decisionRule']['rules'] ?? []);
        $this->assertSame([], $attributes['stores']);
    }

    /**
     * @param array<string, mixed> $attributes
     */
    protected function patchDiscount(string $uuid, array $attributes): Response
    {
        return $this->handleApiRequest(
            static::METHOD_PATCH,
            $this->tester->getDiscountUrl($uuid),
            $this->tester->buildDiscountRequestBody($attributes, $uuid),
        );
    }

    public function testKeepsTheDisplayNameOfTheDiscountItPatches(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $displayName = $this->tester->createUniqueDisplayName();
        $uuid = $this->haveDiscountViaApi(['displayName' => $displayName]);

        // Act
        $response = $this->handleApiRequest(
            static::METHOD_PATCH,
            $this->tester->getDiscountUrl($uuid),
            $this->tester->buildDiscountRequestBody(['displayName' => $displayName], $uuid),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertSame($displayName, $this->getResourceAttributes($response)['displayName']);
    }

    /**
     * @dataProvider providePromotionUpdates
     *
     * @param array<string, mixed>|null $promotion
     * @param array<string, mixed> $expectedPromotion
     */
    public function testAppliesThePromotionPatchAndKeepsEveryOmittedPromotionProperty(
        ?array $promotion,
        array $expectedPromotion,
    ): void {
        // Arrange
        $uuid = $this->havePromotionDiscount();
        $abstractSkusByPlaceholder = [
            static::ABSTRACT_SKUS_OF_DISCOUNT => $this->tester->getPromotionAbstractSkus(),
            static::ABSTRACT_SKUS_OF_PATCH => $this->tester->haveAbstractSkus(static::UPDATED_PROMOTION_ABSTRACT_SKU_COUNT),
        ];
        $attributes = $promotion === null
            ? []
            : [static::RESOURCE_KEY_PROMOTION => $this->resolveAbstractSkus($promotion, $abstractSkusByPlaceholder)];
        $expectedPromotion = $this->resolveAbstractSkus($expectedPromotion, $abstractSkusByPlaceholder);

        // Act
        $response = $this->handleApiRequest(
            static::METHOD_PATCH,
            $this->tester->getDiscountUrl($uuid),
            $this->tester->buildDiscountRequestBody($attributes, $uuid),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertSame($expectedPromotion, $this->getResourceAttributes($response)[static::RESOURCE_KEY_PROMOTION]);
        $this->assertSame(
            $expectedPromotion,
            $this->getResourceAttributes(
                $this->handleApiRequest(static::METHOD_GET, $this->tester->getDiscountUrl($uuid)),
            )[static::RESOURCE_KEY_PROMOTION],
        );
    }

    /**
     * @return array<string, array{array<string, mixed>|null, array<string, mixed>}>
     */
    public function providePromotionUpdates(): array
    {
        return [
            'given no promotion object, when the discount is patched, then the stored promotion survives' => [
                null,
                [
                    self::PROMOTION_KEY_ABSTRACT_SKUS => self::ABSTRACT_SKUS_OF_DISCOUNT,
                    self::PROMOTION_KEY_QUANTITY => self::PROMOTION_QUANTITY,
                ],
            ],
            'given a new sku list, when the discount is patched, then the skus are replaced and the quantity survives' => [
                [self::PROMOTION_KEY_ABSTRACT_SKUS => self::ABSTRACT_SKUS_OF_PATCH],
                [
                    self::PROMOTION_KEY_ABSTRACT_SKUS => self::ABSTRACT_SKUS_OF_PATCH,
                    self::PROMOTION_KEY_QUANTITY => self::PROMOTION_QUANTITY,
                ],
            ],
            'given only a quantity, when the discount is patched, then the quantity is replaced and the skus survive' => [
                [self::PROMOTION_KEY_QUANTITY => self::UPDATED_PROMOTION_QUANTITY],
                [
                    self::PROMOTION_KEY_ABSTRACT_SKUS => self::ABSTRACT_SKUS_OF_DISCOUNT,
                    self::PROMOTION_KEY_QUANTITY => self::UPDATED_PROMOTION_QUANTITY,
                ],
            ],
        ];
    }

    public function testRejectsAPromotionPatchThatEmptiesTheSkuListOfAPromotionDiscount(): void
    {
        // Arrange
        $uuid = $this->havePromotionDiscount();

        // Act
        $response = $this->handleApiRequest(
            static::METHOD_PATCH,
            $this->tester->getDiscountUrl($uuid),
            $this->tester->buildDiscountRequestBody(
                [static::RESOURCE_KEY_PROMOTION => [static::PROMOTION_KEY_ABSTRACT_SKUS => []]],
                $uuid,
            ),
        );

        // Assert
        $this->assertRespondsWithErrorCode(
            $response,
            Response::HTTP_UNPROCESSABLE_ENTITY,
            DiscountConfig::RESPONSE_CODE_PROMOTION_INVALID,
        );
    }

    public function testStopsValidatingThePromotionOnceTheStrategyMovesAwayFromPromotion(): void
    {
        // Arrange
        $uuid = $this->havePromotionDiscount();

        // Act
        $response = $this->handleApiRequest(
            static::METHOD_PATCH,
            $this->tester->getDiscountUrl($uuid),
            $this->tester->buildDiscountRequestBody(
                [
                    'collectorStrategyType' => DiscountConstants::DISCOUNT_COLLECTOR_STRATEGY_QUERY_STRING,
                    'collector' => $this->tester->buildMatchAllCollectorRuleGroup(),
                    static::RESOURCE_KEY_PROMOTION => [static::PROMOTION_KEY_ABSTRACT_SKUS => []],
                ],
                $uuid,
            ),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertSame(
            DiscountConstants::DISCOUNT_COLLECTOR_STRATEGY_QUERY_STRING,
            $this->getResourceAttributes($response)['collectorStrategyType'],
        );
    }

    /**
     * @dataProvider provideRejectedUpdateRequests
     *
     * @param array<string, mixed> $attributes
     */
    public function testRejectsAnUpdateItCannotApply(
        bool $isAuthenticated,
        bool $isExistingUuid,
        array $attributes,
        int $expectedStatus,
        ?string $expectedErrorCode,
    ): void {
        // Arrange
        $uuid = $this->haveDiscountAndAuthenticate($isAuthenticated, $isExistingUuid);

        // Act
        $response = $this->handleApiRequest(
            static::METHOD_PATCH,
            $this->tester->getDiscountUrl($uuid),
            $this->tester->buildDiscountRequestBody($attributes, $uuid),
        );

        // Assert
        $this->assertRespondsWithStatus($response, $expectedStatus);

        if ($expectedErrorCode === null) {
            return;
        }

        $this->assertRespondsWithErrorCode($response, $expectedStatus, $expectedErrorCode);
    }

    /**
     * @return array<string, array{bool, bool, array<string, mixed>, int, string|null}>
     */
    public function provideRejectedUpdateRequests(): array
    {
        return [
            'given a uuid no discount carries, when it is patched, then it responds not found' => [
                true, false, ['description' => self::UPDATED_DESCRIPTION],
                Response::HTTP_NOT_FOUND, DiscountConfig::RESPONSE_CODE_DISCOUNT_NOT_FOUND,
            ],
            'given an unknown calculator plugin, when the discount is patched, then the calculator code answers' => [
                true, true, ['calculatorType' => self::UNKNOWN_CALCULATOR_PLUGIN],
                Response::HTTP_UNPROCESSABLE_ENTITY, DiscountConfig::RESPONSE_CODE_CALCULATOR_PLUGIN_INVALID,
            ],
            'given an unknown store name, when the discount is patched, then the store code answers' => [
                true, true, ['stores' => [self::UNKNOWN_STORE]],
                Response::HTTP_UNPROCESSABLE_ENTITY, DiscountConfig::RESPONSE_CODE_STORE_UNKNOWN,
            ],
            'given a priority above the configured maximum, when the discount is patched, then the framework validation code answers' => [
                true, true, ['priority' => 10000],
                Response::HTTP_UNPROCESSABLE_ENTITY, self::RESPONSE_CODE_FRAMEWORK_VALIDATION,
            ],
            'given null for the display name, when the discount is patched, then the framework validation code answers' => [
                true, true, ['displayName' => null],
                Response::HTTP_UNPROCESSABLE_ENTITY, self::RESPONSE_CODE_FRAMEWORK_VALIDATION,
            ],
            'given no token, when a discount is patched, then it responds unauthorized' => [
                false, false, ['description' => self::UPDATED_DESCRIPTION],
                Response::HTTP_UNAUTHORIZED, null,
            ],
        ];
    }

    /**
     * @dataProvider provideMoneyAmountClearingPatches
     *
     * @param array<string, mixed> $attributes
     */
    public function testTreatsAnEmptyMoneyAmountListAsAnInstructionToClearIt(
        array $attributes,
        int $expectedStatus,
        ?string $expectedErrorCode,
    ): void {
        // Arrange
        $this->tester->actingAsUser();
        $uuid = $this->haveDiscountViaApi($this->tester->buildValidFixedAmountDiscountAttributes());

        // Act
        $response = $this->handleApiRequest(
            static::METHOD_PATCH,
            $this->tester->getDiscountUrl($uuid),
            $this->tester->buildDiscountRequestBody($attributes, $uuid),
        );

        // Assert
        $this->assertRespondsWithStatus($response, $expectedStatus);

        if ($expectedErrorCode !== null) {
            $this->assertRespondsWithErrorCode($response, $expectedStatus, $expectedErrorCode);

            return;
        }

        $this->assertSame([], $this->getResourceAttributes($response)['fixedAmounts']);
    }

    /**
     * @return array<string, array{array<string, mixed>, int, string|null}>
     */
    public function provideMoneyAmountClearingPatches(): array
    {
        return [
            'given an empty money amount list on a money input calculator, when the discount is patched, then the money amount code answers' => [
                ['fixedAmounts' => []],
                Response::HTTP_UNPROCESSABLE_ENTITY,
                DiscountConfig::RESPONSE_CODE_MONEY_AMOUNT_INVALID,
            ],
            'given an empty money amount list alongside a percentage calculator, when the discount is patched, then the amounts are cleared' => [
                [
                    'fixedAmounts' => [],
                    'calculatorType' => DiscountConfig::CALCULATOR_TYPE_PERCENTAGE,
                    'percentage' => self::UPDATED_PERCENTAGE_AMOUNT,
                ],
                Response::HTTP_OK,
                null,
            ],
        ];
    }

    /**
     * @param array<string, mixed> $promotion
     * @param array<string, array<int, string>> $abstractSkusByPlaceholder
     *
     * @return array<string, mixed>
     */
    protected function resolveAbstractSkus(array $promotion, array $abstractSkusByPlaceholder): array
    {
        if (isset($promotion[static::PROMOTION_KEY_ABSTRACT_SKUS])) {
            $promotion[static::PROMOTION_KEY_ABSTRACT_SKUS] = $abstractSkusByPlaceholder[$promotion[static::PROMOTION_KEY_ABSTRACT_SKUS]];
        }

        return $promotion;
    }

    protected function havePromotionDiscount(): string
    {
        $this->tester->actingAsUser();

        return $this->haveDiscountViaApi(
            $this->tester->buildValidPromotionDiscountAttributes(
                $this->tester->getPromotionAbstractSkus(),
                static::PROMOTION_QUANTITY,
            ),
        );
    }
}
