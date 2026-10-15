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
use Spryker\Shared\DiscountPromotion\DiscountPromotionConfig;
use Symfony\Component\HttpFoundation\Response;

/**
 * Auto-generated group annotations
 *
 * @group PyzTest
 * @group Glue
 * @group Discount
 * @group BackendApi
 * @group Integration
 * @group GetDiscountOptionsBackendApiTest
 * Add your own group annotations below this line
 */
class GetDiscountOptionsBackendApiTest extends AbstractDiscountBackendApiTestCase
{
    /**
     * @var array<int, string>
     */
    protected const array EXPECTED_ATTRIBUTE_KEYS = [
        'calculatorTypes',
        'collectorFields',
        'collectorStrategyTypes',
        'decisionRuleFields',
        'discountTypes',
        'logicalOperators',
        'priority',
        'stores',
        'voucherCodeLength',
        'voucherCodesQuantity',
    ];

    public function testReturnsEveryValueTheBackOfficeRendersAsADropdown(): void
    {
        // Arrange
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest(static::METHOD_GET, $this->tester->getDiscountOptionsUrl());

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);

        $data = $this->decodeJsonApi($response)[static::JSON_API_KEY_DATA];
        $this->assertSame(DiscountConfig::RESOURCE_DISCOUNT_OPTIONS, $data[static::JSON_API_KEY_TYPE]);
        $this->assertSame(DiscountConfig::RESOURCE_DISCOUNT_OPTIONS, $data[static::JSON_API_KEY_ID]);

        $attributes = $this->getResourceAttributes($response);
        $attributeKeys = array_keys($attributes);
        sort($attributeKeys);

        $this->assertSame(static::EXPECTED_ATTRIBUTE_KEYS, $attributeKeys);
        $this->assertSame(
            [DiscountConstants::TYPE_CART_RULE, DiscountConstants::TYPE_VOUCHER],
            $attributes['discountTypes'],
        );
        $this->assertSame(['and', 'or'], $attributes['logicalOperators']);

        $this->assertSame(
            [DiscountConfig::CALCULATOR_TYPE_PERCENTAGE, DiscountConfig::CALCULATOR_TYPE_FIXED],
            array_column($attributes['calculatorTypes'], 'key'),
        );

        foreach ($attributes['calculatorTypes'] as $calculatorType) {
            $this->assertSame(['key', 'label', 'inputType'], array_keys($calculatorType));
            $this->assertNotSame('', $calculatorType['label']);
            $this->assertNotSame($calculatorType['key'], $calculatorType['label']);
        }
        $this->assertContains(
            DiscountConstants::DISCOUNT_COLLECTOR_STRATEGY_QUERY_STRING,
            array_column($attributes['collectorStrategyTypes'], 'key'),
        );
        $this->assertContains(
            DiscountPromotionConfig::DISCOUNT_COLLECTOR_STRATEGY,
            array_column($attributes['collectorStrategyTypes'], 'key'),
        );
        $this->assertContains(
            $this->tester->getStoreName(),
            array_column($attributes['stores'], 'name'),
        );
    }

    /**
     * @dataProvider provideUnauthenticatedRequests
     */
    public function testRejectsARequestWithoutAValidToken(bool $hasInvalidToken, bool $hasAclAccess): void
    {
        // Arrange
        $this->authenticateAs($hasInvalidToken, $hasAclAccess);

        // Act
        $response = $this->handleApiRequest(static::METHOD_GET, $this->tester->getDiscountOptionsUrl());

        // Assert
        $this->assertRespondsWithStatus(
            $response,
            $hasAclAccess ? Response::HTTP_UNAUTHORIZED : Response::HTTP_FORBIDDEN,
        );
    }

    /**
     * @return array<string, array{bool, bool}>
     */
    public function provideUnauthenticatedRequests(): array
    {
        return [
            'given no token, when the options are requested, then the request is unauthorized' => [false, true],
            'given an invalid token, when the options are requested, then the request is unauthorized' => [true, true],
            'given an operator without acl access, when the options are requested, then the request is forbidden' => [false, false],
        ];
    }

    protected function authenticateAs(bool $hasInvalidToken, bool $hasAclAccess): void
    {
        if (!$hasAclAccess) {
            $this->tester->actingAsUserWithoutAclAccess();

            return;
        }

        if (!$hasInvalidToken) {
            return;
        }

        $this->tester->actingWithInvalidToken();
    }
}
