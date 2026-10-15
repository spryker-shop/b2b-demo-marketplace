<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace PyzTest\Glue\Discount\BackendApi\Integration;

use PyzTest\Glue\Discount\AbstractDiscountBackendApiTestCase;
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
 * @group GetSingleDiscountBackendApiTest
 * Add your own group annotations below this line
 */
class GetSingleDiscountBackendApiTest extends AbstractDiscountBackendApiTestCase
{
    /**
     * @dataProvider provideStoredDiscounts
     */
    public function testReturnsTheDiscountAddressedByItsUuid(bool $isMoneyInputCalculator): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $displayName = $this->tester->createUniqueDisplayName();
        $uuid = $this->haveDiscountViaApi(
            $isMoneyInputCalculator
                ? $this->tester->buildValidFixedAmountDiscountAttributes(['displayName' => $displayName])
                : ['displayName' => $displayName],
        );

        // Act
        $response = $this->handleApiRequest(static::METHOD_GET, $this->tester->getDiscountUrl($uuid));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);

        $data = $this->decodeJsonApi($response)[static::JSON_API_KEY_DATA];
        $this->assertSame($uuid, $data[static::JSON_API_KEY_ID]);

        $attributes = $this->getResourceAttributes($response);
        $this->assertAttributesMatch(
            [
                'displayName' => $displayName,
                'discountType' => DiscountConstants::TYPE_CART_RULE,
                'isActive' => false,
                'isExclusive' => false,
                'calculatorType' => $isMoneyInputCalculator
                    ? DiscountConfig::CALCULATOR_TYPE_FIXED
                    : DiscountConfig::CALCULATOR_TYPE_PERCENTAGE,
                'percentage' => $isMoneyInputCalculator ? 0 : $this->tester->getPercentageAmount(),
                'fixedAmounts' => $isMoneyInputCalculator ? [$this->tester->buildMoneyAmount()] : [],
                'collectorStrategyType' => DiscountConstants::DISCOUNT_COLLECTOR_STRATEGY_QUERY_STRING,
                'priority' => $this->tester->getPriority(),
                'stores' => [$this->tester->getStoreName()],
            ],
            $attributes,
        );
        $this->assertNull($attributes['pagination'] ?? null);
        $this->assertNotEmpty($attributes['decisionRuleQueryString']);
        $this->assertNotEmpty($attributes['collectorQueryString']);
        $this->assertSame(
            $this->tester->buildSubTotalDecisionRuleGroup()['rules'],
            $this->normalizeRules($attributes['decisionRule']),
        );
        $this->assertSame(
            $this->tester->buildMatchAllCollectorRuleGroup()['rules'],
            $this->normalizeRules($attributes['collector']),
        );
    }

    /**
     * @return array<string, array{bool}>
     */
    public function provideStoredDiscounts(): array
    {
        return [
            'given a percentage discount, when it is read by uuid, then the percentage amount is returned without money amounts' => [
                false,
            ],
            'given a fixed amount discount, when it is read by uuid, then the money amounts are returned in cents' => [
                true,
            ],
        ];
    }

    /**
     * @dataProvider provideRejectedSingleDiscountRequests
     */
    public function testRejectsARequestItCannotAnswer(
        bool $isAuthenticated,
        bool $isExistingUuid,
        bool $hasAclAccess,
        int $expectedStatus,
        ?string $expectedErrorCode,
    ): void {
        // Arrange
        $uuid = $this->haveDiscountAndAuthenticate($isAuthenticated, $isExistingUuid);

        if (!$hasAclAccess) {
            $this->tester->actingAsUserWithoutAclAccess();
        }

        // Act
        $response = $this->handleApiRequest(static::METHOD_GET, $this->tester->getDiscountUrl($uuid));

        // Assert
        $this->assertRespondsWithStatus($response, $expectedStatus);

        if ($expectedErrorCode === null) {
            return;
        }

        $this->assertJsonApiError(
            $response,
            $expectedStatus,
            $expectedErrorCode,
            sprintf(DiscountConfig::RESPONSE_DETAILS_DISCOUNT_NOT_FOUND, $uuid),
        );
    }

    /**
     * @return array<string, array{bool, bool, bool, int, string|null}>
     */
    public function provideRejectedSingleDiscountRequests(): array
    {
        return [
            'given an unknown uuid, when the discount is requested, then it responds not found' => [
                true, false, true, Response::HTTP_NOT_FOUND, DiscountConfig::RESPONSE_CODE_DISCOUNT_NOT_FOUND,
            ],
            'given an operator without acl access, when the discount is requested, then it responds forbidden' => [
                true, true, false, Response::HTTP_FORBIDDEN, null,
            ],
            'given no token, when the discount is requested, then it responds unauthorized' => [
                false, false, true, Response::HTTP_UNAUTHORIZED, null,
            ],
        ];
    }

    /**
     * @dataProvider provideDiscountTypesForTheExportLink
     */
    public function testExposesTheVoucherCodesExportLinkNextToTheSelfLinkOnVoucherDiscountsOnly(
        string $discountType,
        bool $isExportLinkExpected,
    ): void {
        // Arrange
        $this->tester->actingAsUser();
        $uuid = $this->haveDiscountViaApi(['discountType' => $discountType]);

        // Act
        $response = $this->handleApiRequest(static::METHOD_GET, $this->tester->getDiscountUrl($uuid));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);

        $links = (array)($this->decodeJsonApi($response)[static::JSON_API_KEY_DATA][static::JSON_API_KEY_LINKS] ?? []);
        $this->assertArrayHasKey(static::JSON_API_KEY_SELF, $links);
        $this->assertSame($isExportLinkExpected, array_key_exists(static::LINK_VOUCHER_CODES_EXPORT, $links));

        if (!$isExportLinkExpected) {
            return;
        }

        $this->assertStringEndsWith(
            $this->tester->getDiscountVoucherCodesExportUrl($uuid),
            (string)$links[static::LINK_VOUCHER_CODES_EXPORT],
        );
    }

    /**
     * @return array<string, array{string, bool}>
     */
    public function provideDiscountTypesForTheExportLink(): array
    {
        return [
            'given a voucher discount, when it is read, then the export link is offered beside the self link' => [
                DiscountConstants::TYPE_VOUCHER, true,
            ],
            'given a cart rule discount, when it is read, then only the self link is offered' => [
                DiscountConstants::TYPE_CART_RULE, false,
            ],
        ];
    }

    /**
     * @param array<string, mixed>|null $ruleGroup
     *
     * @return array<int, array<string, mixed>>
     */
    protected function normalizeRules(?array $ruleGroup): array
    {
        return array_map(
            static fn (array $rule): array => [
                'field' => $rule['field'] ?? null,
                'attribute' => $rule['attribute'] ?? null,
                'operator' => $rule['operator'] ?? null,
                'value' => $rule['value'] ?? null,
            ],
            $ruleGroup['rules'] ?? [],
        );
    }
}
