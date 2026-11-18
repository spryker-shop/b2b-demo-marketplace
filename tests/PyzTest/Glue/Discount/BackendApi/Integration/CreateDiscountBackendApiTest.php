<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace PyzTest\Glue\Discount\BackendApi\Integration;

use PyzTest\Glue\Discount\AbstractDiscountBackendApiTestCase;
use Spryker\Glue\Discount\DiscountConfig;
use Spryker\Shared\Discount\DiscountConfig as SharedDiscountConfig;
use Spryker\Shared\Discount\DiscountConstants;
use Spryker\Shared\DiscountPromotion\DiscountPromotionConfig;
use Spryker\Zed\Discount\Business\QueryString\Converter\DiscountQueryStringBuilder;
use Symfony\Component\HttpFoundation\Response;

/**
 * Auto-generated group annotations
 *
 * @group PyzTest
 * @group Glue
 * @group Discount
 * @group BackendApi
 * @group Integration
 * @group CreateDiscountBackendApiTest
 * Add your own group annotations below this line
 */
class CreateDiscountBackendApiTest extends AbstractDiscountBackendApiTestCase
{
    protected const string UNKNOWN_STORE = 'CreateDiscountUnknownStore';

    protected const string UNKNOWN_CURRENCY_ISO_CODE = 'XTS';

    protected const string UNKNOWN_RULE_FIELD = 'create-discount-unknown-field';

    protected const string UNKNOWN_ABSTRACT_SKU = 'CREATE-DISCOUNT-UNKNOWN-SKU';

    /**
     * @uses \Spryker\Glue\Discount\DiscountConfig::RESPONSE_DETAILS_ERROR
     */
    protected const string DETAIL_SEPARATOR = ' => ';

    protected const string PLACEHOLDER_DELIMITER = '%';

    /**
     * @uses \Pyz\Glue\Discount\DiscountConfig::getAttributeByEntityIdentifier()
     */
    protected const string ATTRIBUTE_PROMOTION_ABSTRACT_SKUS = 'promotion.abstractSkus';

    /**
     * @uses \Pyz\Glue\Discount\DiscountConfig::getAttributeByEntityIdentifier()
     */
    protected const string ATTRIBUTE_PROMOTION_QUANTITY = 'promotion.quantity';

    /**
     * @dataProvider provideCreatedDiscounts
     */
    public function testCreatesTheDiscountAndReturnsItAsStored(string $discountType, bool $isMoneyInputCalculator): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $this->tester->haveStoresForDiscounts();
        $displayName = $this->tester->createUniqueDisplayName();

        $attributes = $isMoneyInputCalculator
            ? $this->tester->buildValidFixedAmountDiscountAttributes(['displayName' => $displayName, 'discountType' => $discountType])
            : $this->tester->buildValidDiscountAttributes(['displayName' => $displayName, 'discountType' => $discountType]);

        // Act
        $response = $this->handleApiRequest(
            static::METHOD_POST,
            $this->tester->getDiscountCollectionUrl(),
            $this->tester->buildDiscountRequestBody($attributes),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_CREATED);

        $expectedAttributes = [
            'displayName' => $displayName,
            'discountType' => $discountType,
            'isActive' => false,
            'calculatorType' => $isMoneyInputCalculator
                ? DiscountConfig::CALCULATOR_TYPE_FIXED
                : DiscountConfig::CALCULATOR_TYPE_PERCENTAGE,
            'percentage' => $isMoneyInputCalculator ? 0 : $this->tester->getPercentageAmount(),
            'fixedAmounts' => $isMoneyInputCalculator ? [$this->tester->buildMoneyAmount()] : [],
            'stores' => [$this->tester->getStoreName()],
        ];

        $createdAttributes = $this->getResourceAttributes($response);
        $this->assertAttributesMatch($expectedAttributes, $createdAttributes);

        $uuid = (string)($this->decodeJsonApi($response)[static::JSON_API_KEY_DATA][static::JSON_API_KEY_ID] ?? '');
        $this->assertNotSame('', $uuid);

        $readBackResponse = $this->handleApiRequest(static::METHOD_GET, $this->tester->getDiscountUrl($uuid));
        $this->assertRespondsWithStatus($readBackResponse, Response::HTTP_OK);

        $readBackAttributes = $this->getResourceAttributes($readBackResponse);
        $this->assertAttributesMatch($expectedAttributes, $readBackAttributes);
        $this->assertAttributesMatch($createdAttributes, $readBackAttributes);
    }

    /**
     * @return array<string, array{string, bool}>
     */
    public function provideCreatedDiscounts(): array
    {
        return [
            'given a cart rule with the percentage calculator, when it is created, then it is stored inactive with the sent amount' => [
                DiscountConstants::TYPE_CART_RULE, false,
            ],
            'given a voucher discount, when it is created, then it is stored inactive' => [
                DiscountConstants::TYPE_VOUCHER, false,
            ],
            'given a money input calculator with one money amount, when it is created, then it is stored inactive' => [
                DiscountConstants::TYPE_CART_RULE, true,
            ],
        ];
    }

    /**
     * @dataProvider provideRejectedDiscountPayloads
     */
    public function testRejectsAPayloadTheValidationDoesNotAccept(
        callable $buildAttributes,
        string $expectedErrorCode,
    ): void {
        // Arrange
        $this->tester->actingAsUser();
        $this->tester->haveStoresForDiscounts();

        // Act
        $response = $this->handleApiRequest(
            static::METHOD_POST,
            $this->tester->getDiscountCollectionUrl(),
            $this->tester->buildDiscountRequestBody($buildAttributes($this->tester, $this)),
        );

        // Assert
        $this->assertRespondsWithErrorCode($response, Response::HTTP_UNPROCESSABLE_ENTITY, $expectedErrorCode);
    }

    public function testCreatesAnActiveDiscountWhenIsActiveIsSent(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $this->tester->haveStoresForDiscounts();

        // Act
        $response = $this->handleApiRequest(
            static::METHOD_POST,
            $this->tester->getDiscountCollectionUrl(),
            $this->tester->buildDiscountRequestBody($this->tester->buildValidDiscountAttributes(['isActive' => true])),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_CREATED);
        $this->assertTrue($this->getResourceAttributes($response)['isActive']);
    }

    /**
     * @dataProvider provideBusinessValidationErrorDetails
     */
    public function testNamesTheResourceAttributeAndFillsThePlaceholdersInABusinessValidationError(
        callable $buildAttributes,
        string $expectedErrorCode,
        string $expectedDetailPrefix,
        ?string $expectedDetail,
    ): void {
        // Arrange
        $this->tester->actingAsUser();
        $this->tester->haveStoresForDiscounts();

        // Act
        $response = $this->handleApiRequest(
            static::METHOD_POST,
            $this->tester->getDiscountCollectionUrl(),
            $this->tester->buildDiscountRequestBody($buildAttributes($this->tester)),
        );

        // Assert
        $this->assertRespondsWithErrorCode($response, Response::HTTP_UNPROCESSABLE_ENTITY, $expectedErrorCode);

        $detail = (string)$this->decodeJsonApi($response)['errors'][0]['detail'];
        $this->assertStringStartsWith($expectedDetailPrefix, $detail);
        $this->assertStringNotContainsString(static::PLACEHOLDER_DELIMITER, $detail);

        if ($expectedDetail === null) {
            return;
        }

        $this->assertSame($expectedDetail, $detail);
    }

    /**
     * @return array<string, array{callable, string, string, string|null}>
     */
    public function provideBusinessValidationErrorDetails(): array
    {
        return [
            'given an unknown calculator type, when the discount is created, then the detail names calculatorType and the sent value' => [
                static fn ($tester): array => $tester->buildValidDiscountAttributes(['calculatorType' => self::UNKNOWN_CALCULATOR_PLUGIN]),
                DiscountConfig::RESPONSE_CODE_CALCULATOR_PLUGIN_INVALID,
                DiscountConfig::ATTRIBUTE_CALCULATOR_TYPE . static::DETAIL_SEPARATOR,
                DiscountConfig::ATTRIBUTE_CALCULATOR_TYPE . static::DETAIL_SEPARATOR
                    . strtr(SharedDiscountConfig::TRANSLATION_KEY_CALCULATOR_PLUGIN_INVALID, ['%key%' => self::UNKNOWN_CALCULATOR_PLUGIN]),
            ],
            'given an unknown store, when the discount is created, then the detail names the store list position and the name' => [
                static fn ($tester): array => $tester->buildValidDiscountAttributes(['stores' => [self::UNKNOWN_STORE]]),
                DiscountConfig::RESPONSE_CODE_STORE_UNKNOWN,
                DiscountConfig::ATTRIBUTE_STORES . '.0' . static::DETAIL_SEPARATOR,
                DiscountConfig::ATTRIBUTE_STORES . '.0' . static::DETAIL_SEPARATOR
                    . strtr(SharedDiscountConfig::TRANSLATION_KEY_STORE_UNKNOWN, ['%name%' => self::UNKNOWN_STORE]),
            ],
            'given an unknown rule field, when the discount is created, then the detail names collector and the rule problem' => [
                static fn ($tester): array => $tester->buildValidDiscountAttributes([
                    'collector' => $tester->buildRuleGroup([$tester->buildRule(self::UNKNOWN_RULE_FIELD, '=', '1')]),
                ]),
                DiscountConfig::RESPONSE_CODE_QUERY_STRING_INVALID,
                DiscountConfig::ATTRIBUTE_COLLECTOR . static::DETAIL_SEPARATOR,
                null,
            ],
            'given a rule value with a quote, when the discount is created, then the detail names the quote limitation instead of a parser error' => [
                static fn ($tester): array => $tester->buildValidDiscountAttributes([
                    'collector' => $tester->buildRuleGroup([$tester->buildRule('sku', '=', "Levi's")]),
                ]),
                DiscountConfig::RESPONSE_CODE_QUERY_STRING_INVALID,
                DiscountConfig::ATTRIBUTE_COLLECTOR . static::DETAIL_SEPARATOR,
                DiscountConfig::ATTRIBUTE_COLLECTOR . static::DETAIL_SEPARATOR
                    . strtr(SharedDiscountConfig::TRANSLATION_KEY_QUERY_STRING_INVALID, ['%message%' => DiscountQueryStringBuilder::ERROR_RULE_CONTAINS_QUOTES]),
            ],
            'given a money amount in an unknown currency, when the discount is created, then the detail names the fixed amount position' => [
                static fn ($tester): array => $tester->buildValidFixedAmountDiscountAttributes([
                    'fixedAmounts' => [$tester->buildMoneyAmount(self::UNKNOWN_CURRENCY_ISO_CODE)],
                ]),
                DiscountConfig::RESPONSE_CODE_MONEY_AMOUNT_INVALID,
                DiscountConfig::ATTRIBUTE_FIXED_AMOUNTS . '.0' . static::DETAIL_SEPARATOR,
                DiscountConfig::ATTRIBUTE_FIXED_AMOUNTS . '.0' . static::DETAIL_SEPARATOR . SharedDiscountConfig::TRANSLATION_KEY_MONEY_AMOUNT_INVALID,
            ],
            'given the promotion strategy with an empty sku list, when the discount is created, then the detail names promotion.abstractSkus' => [
                static fn ($tester): array => $tester->buildValidPromotionDiscountAttributes([]),
                DiscountConfig::RESPONSE_CODE_PROMOTION_INVALID,
                static::ATTRIBUTE_PROMOTION_ABSTRACT_SKUS . static::DETAIL_SEPARATOR,
                static::ATTRIBUTE_PROMOTION_ABSTRACT_SKUS . static::DETAIL_SEPARATOR . DiscountPromotionConfig::TRANSLATION_KEY_ABSTRACT_SKUS_REQUIRED,
            ],
            'given the promotion strategy with an unknown sku next to a known one, when the discount is created, then the detail lists only the unknown sku' => [
                static fn ($tester): array => $tester->buildValidPromotionDiscountAttributes(array_merge($tester->getPromotionAbstractSkus(), [self::UNKNOWN_ABSTRACT_SKU])),
                DiscountConfig::RESPONSE_CODE_PROMOTION_INVALID,
                static::ATTRIBUTE_PROMOTION_ABSTRACT_SKUS . static::DETAIL_SEPARATOR,
                static::ATTRIBUTE_PROMOTION_ABSTRACT_SKUS . static::DETAIL_SEPARATOR
                    . strtr(DiscountPromotionConfig::TRANSLATION_KEY_ABSTRACT_SKUS_UNKNOWN, ['%non_existing_skus%' => self::UNKNOWN_ABSTRACT_SKU]),
            ],
            'given the promotion strategy with a quantity of zero, when the discount is created, then the detail names promotion.quantity and the minimum' => [
                static fn ($tester): array => $tester->buildValidPromotionDiscountAttributes($tester->getPromotionAbstractSkus(), 0),
                DiscountConfig::RESPONSE_CODE_PROMOTION_INVALID,
                static::ATTRIBUTE_PROMOTION_QUANTITY . static::DETAIL_SEPARATOR,
                static::ATTRIBUTE_PROMOTION_QUANTITY . static::DETAIL_SEPARATOR
                    . strtr(DiscountPromotionConfig::TRANSLATION_KEY_QUANTITY_INVALID, ['%min%' => '1']),
            ],
        ];
    }

    /**
     * @return array<string, array{callable, string}>
     */
    public function provideRejectedDiscountPayloads(): array
    {
        return [
            'given an unknown calculator plugin, when the discount is created, then the calculator code answers' => [
                static fn ($tester): array => $tester->buildValidDiscountAttributes(['calculatorType' => self::UNKNOWN_CALCULATOR_PLUGIN]),
                DiscountConfig::RESPONSE_CODE_CALCULATOR_PLUGIN_INVALID,
            ],
            'given a money input calculator without any money amount, when the discount is created, then the money amount code answers' => [
                static fn ($tester): array => $tester->buildValidFixedAmountDiscountAttributes(['fixedAmounts' => []]),
                DiscountConfig::RESPONSE_CODE_MONEY_AMOUNT_INVALID,
            ],
            'given a money amount in a currency no selected store carries, when the discount is created, then the money amount code answers' => [
                static fn ($tester): array => $tester->buildValidFixedAmountDiscountAttributes([
                    'fixedAmounts' => [$tester->buildMoneyAmount(self::UNKNOWN_CURRENCY_ISO_CODE)],
                ]),
                DiscountConfig::RESPONSE_CODE_MONEY_AMOUNT_INVALID,
            ],
            'given an unknown rule field, when the discount is created, then the query string code answers' => [
                static fn ($tester): array => $tester->buildValidDiscountAttributes([
                    'collector' => $tester->buildRuleGroup([$tester->buildRule(self::UNKNOWN_RULE_FIELD, '=', '1')]),
                ]),
                DiscountConfig::RESPONSE_CODE_QUERY_STRING_INVALID,
            ],
            'given an empty nested rule group, when the discount is created, then the query string code answers' => [
                static fn ($tester): array => $tester->buildValidDiscountAttributes([
                    'decisionRule' => $tester->buildRuleGroup([], [$tester->buildRuleGroup()]),
                ]),
                DiscountConfig::RESPONSE_CODE_QUERY_STRING_INVALID,
            ],
            'given the query string strategy without a collector, when the discount is created, then the query string code answers' => [
                static fn ($tester): array => $tester->buildValidDiscountAttributes(['collector' => null]),
                DiscountConfig::RESPONSE_CODE_QUERY_STRING_INVALID,
            ],
            'given an unknown store name, when the discount is created, then the store code answers' => [
                static fn ($tester): array => $tester->buildValidDiscountAttributes(['stores' => [self::UNKNOWN_STORE]]),
                DiscountConfig::RESPONSE_CODE_STORE_UNKNOWN,
            ],
            'given the promotion strategy with an empty sku list, when the discount is created, then the promotion code answers' => [
                static fn ($tester): array => $tester->buildValidPromotionDiscountAttributes([]),
                DiscountConfig::RESPONSE_CODE_PROMOTION_INVALID,
            ],
            'given the promotion strategy with a sku no product carries, when the discount is created, then the promotion code answers' => [
                static fn ($tester): array => $tester->buildValidPromotionDiscountAttributes([self::UNKNOWN_ABSTRACT_SKU]),
                DiscountConfig::RESPONSE_CODE_PROMOTION_INVALID,
            ],
            'given the promotion strategy with a quantity of zero, when the discount is created, then the promotion code answers' => [
                static fn ($tester): array => $tester->buildValidPromotionDiscountAttributes($tester->getPromotionAbstractSkus(), 0),
                DiscountConfig::RESPONSE_CODE_PROMOTION_INVALID,
            ],
            'given the promotion strategy without a promotion object, when the discount is created, then the promotion code answers' => [
                static fn ($tester): array => $tester->buildValidPromotionDiscountAttributes([], null, ['promotion' => null]),
                DiscountConfig::RESPONSE_CODE_PROMOTION_INVALID,
            ],
            'given a display name longer than the column, when the discount is created, then the framework validation code answers' => [
                static fn ($tester): array => $tester->buildValidDiscountAttributes(['displayName' => str_repeat('n', 256)]),
                self::RESPONSE_CODE_FRAMEWORK_VALIDATION,
            ],
            'given a description longer than the column, when the discount is created, then the framework validation code answers' => [
                static fn ($tester): array => $tester->buildValidDiscountAttributes(['description' => str_repeat('d', 1025)]),
                self::RESPONSE_CODE_FRAMEWORK_VALIDATION,
            ],
            'given a valid to date before the valid from date, when the discount is created, then the framework validation code answers' => [
                static fn ($tester): array => $tester->buildValidDiscountAttributes([
                    'validFrom' => $tester->getValidTo(),
                    'validTo' => $tester->getValidFrom(),
                ]),
                self::RESPONSE_CODE_FRAMEWORK_VALIDATION,
            ],
            'given a display name another discount already uses, when the discount is created, then the uniqueness code answers' => [
                static fn ($tester, self $test): array => $tester->buildValidDiscountAttributes([
                    'displayName' => $test->haveDiscountWithGeneratedDisplayName(),
                ]),
                DiscountConfig::RESPONSE_CODE_DISPLAY_NAME_NOT_UNIQUE,
            ],
        ];
    }

    /**
     * @dataProvider provideRejectedRequestContracts
     *
     * @param array<string, mixed> $override
     */
    public function testRejectsAPayloadThatBreaksTheRequestContract(array $override): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $this->tester->haveStoresForDiscounts();

        // Act
        $response = $this->handleApiRequest(
            static::METHOD_POST,
            $this->tester->getDiscountCollectionUrl(),
            $this->tester->buildDiscountRequestBody($this->tester->buildValidDiscountAttributes($override)),
        );

        // Assert
        $this->assertRespondsWithErrorCode(
            $response,
            Response::HTTP_UNPROCESSABLE_ENTITY,
            static::RESPONSE_CODE_FRAMEWORK_VALIDATION,
        );
    }

    /**
     * @return array<string, array{array<string, mixed>}>
     */
    public function provideRejectedRequestContracts(): array
    {
        return [
            'given no display name, when the discount is created, then the framework validation code answers' => [
                ['displayName' => null],
            ],
            'given a discount type outside the enum, when the discount is created, then the framework validation code answers' => [
                ['discountType' => 'promotion_rule'],
            ],
            'given a percentage amount of zero, when the discount is created, then the framework validation code answers' => [
                ['percentage' => 0],
            ],
            'given a percentage amount above one hundred, when the discount is created, then the framework validation code answers' => [
                ['percentage' => 101],
            ],
            'given a negative net amount, when the discount is created, then the framework validation code answers' => [
                ['fixedAmounts' => [['currencyIsoCode' => 'EUR', 'netAmount' => -1, 'grossAmount' => 595]]],
            ],
            'given a negative gross amount, when the discount is created, then the framework validation code answers' => [
                ['fixedAmounts' => [['currencyIsoCode' => 'EUR', 'netAmount' => 500, 'grossAmount' => -1]]],
            ],
            'given a priority of zero, when the discount is created, then the framework validation code answers' => [
                ['priority' => 0],
            ],
            'given a priority above the configured maximum, when the discount is created, then the framework validation code answers' => [
                ['priority' => 10000],
            ],
            'given a minimum item amount of zero, when the discount is created, then the framework validation code answers' => [
                ['minimumItemQuantity' => 0],
            ],
            'given a promotion sku list that is not an array, when the discount is created, then the framework validation code answers' => [
                ['promotion' => ['abstractSkus' => self::UNKNOWN_RULE_FIELD, 'quantity' => 1]],
            ],
        ];
    }

    public function testCreatesAPromotionDiscountAndReturnsThePromotionAsStored(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $this->tester->haveStoresForDiscounts();

        // Act
        $response = $this->handleApiRequest(
            static::METHOD_POST,
            $this->tester->getDiscountCollectionUrl(),
            $this->tester->buildDiscountRequestBody(
                $this->tester->buildValidPromotionDiscountAttributes($this->tester->getPromotionAbstractSkus()),
            ),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_CREATED);

        $createdAttributes = $this->getResourceAttributes($response);
        $this->assertAttributesMatch(
            [
                'collectorStrategyType' => $this->tester->getPromotionCollectorStrategyType(),
                'promotion' => $this->tester->buildPromotion($this->tester->getPromotionAbstractSkus()),
            ],
            $createdAttributes,
        );

        $uuid = (string)($this->decodeJsonApi($response)[static::JSON_API_KEY_DATA][static::JSON_API_KEY_ID] ?? '');
        $readBackResponse = $this->handleApiRequest(static::METHOD_GET, $this->tester->getDiscountUrl($uuid));

        $this->assertRespondsWithStatus($readBackResponse, Response::HTTP_OK);
        $this->assertAttributesMatch(
            ['promotion' => $this->tester->buildPromotion($this->tester->getPromotionAbstractSkus())],
            $this->getResourceAttributes($readBackResponse),
        );
    }

    public function testDefaultsTheCollectorStrategyTypeWhenThePayloadOmitsIt(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $this->tester->haveStoresForDiscounts();

        $attributes = $this->tester->buildValidDiscountAttributes();
        unset($attributes['collectorStrategyType']);

        // Act
        $response = $this->handleApiRequest(
            static::METHOD_POST,
            $this->tester->getDiscountCollectionUrl(),
            $this->tester->buildDiscountRequestBody($attributes),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_CREATED);
        $this->assertSame(
            DiscountConstants::DISCOUNT_COLLECTOR_STRATEGY_QUERY_STRING,
            $this->getResourceAttributes($response)['collectorStrategyType'] ?? null,
        );
    }

    public function testStoresAndReturnsADecisionRuleNestedThreeLevelsDeep(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $this->tester->haveStoresForDiscounts();

        $decisionRule = $this->tester->buildThreeLevelDecisionRuleGroup();

        // Act
        $response = $this->handleApiRequest(
            static::METHOD_POST,
            $this->tester->getDiscountCollectionUrl(),
            $this->tester->buildDiscountRequestBody(
                $this->tester->buildValidDiscountAttributes(['decisionRule' => $decisionRule]),
            ),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_CREATED);

        $uuid = (string)($this->decodeJsonApi($response)[static::JSON_API_KEY_DATA][static::JSON_API_KEY_ID] ?? '');
        $readBackResponse = $this->handleApiRequest(static::METHOD_GET, $this->tester->getDiscountUrl($uuid));
        $this->assertRespondsWithStatus($readBackResponse, Response::HTTP_OK);

        $this->assertSame(
            $this->flattenRuleGroup($decisionRule),
            $this->flattenRuleGroup((array)$this->getResourceAttributes($readBackResponse)['decisionRule']),
        );
    }

    public function testAcceptsAPayloadWithoutADecisionRule(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $this->tester->haveStoresForDiscounts();

        // Act
        $response = $this->handleApiRequest(
            static::METHOD_POST,
            $this->tester->getDiscountCollectionUrl(),
            $this->tester->buildDiscountRequestBody($this->tester->buildValidDiscountAttributes(['decisionRule' => null])),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_CREATED);
        $this->assertNull($this->getResourceAttributes($response)['decisionRuleQueryString'] ?? null);
    }

    public function haveDiscountWithGeneratedDisplayName(): string
    {
        $displayName = $this->tester->createUniqueDisplayName();
        $this->haveDiscountViaApi(['displayName' => $displayName]);

        return $displayName;
    }

    public function testRejectsARequestWithoutAToken(): void
    {
        // Act
        $response = $this->handleApiRequest(
            static::METHOD_POST,
            $this->tester->getDiscountCollectionUrl(),
            $this->tester->buildDiscountRequestBody($this->tester->buildValidDiscountAttributes()),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_UNAUTHORIZED);
    }
}
