<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace PyzTest\Glue\Discount\Helper;

use Codeception\Module;
use Generated\Shared\Transfer\StoreTransfer;
use Orm\Zed\Discount\Persistence\SpyDiscountVoucherQuery;
use Propel\Runtime\ActiveQuery\Criteria;
use Spryker\Glue\Discount\DiscountConfig;
use Spryker\Shared\Discount\DiscountConstants;
use SprykerTest\Shared\Store\Helper\StoreDataHelper;

class DiscountBackendApiHelper extends Module
{
    public const string PATH_SEGMENT_VOUCHER_CODES = 'voucher-codes';

    public const string PATH_SEGMENT_GENERATE = 'generate';

    public const string PATH_SEGMENT_EXPORT = 'export';

    public const string PATH_SEGMENT_DELETE = 'delete';

    public const string CONDITION_AND = 'and';

    public const string CONDITION_OR = 'or';

    protected const string LISTED_DISPLAY_NAME_PREFIX = 'DiscountBackendApi';

    protected const string CUSTOM_CODE_PREFIX = 'SUITE-';

    protected const string VOUCHER_CODE_PREFIX = 'API';

    protected const string STORE_NAME = 'DE';

    protected const string STORE_NAME_SECOND = 'AT';

    protected const string CURRENCY_ISO_CODE = 'EUR';

    protected const string VALID_FROM = '2026-01-01 00:00:00';

    protected const string VALID_TO = '2036-12-31 23:59:59';

    protected const int PERCENTAGE_AMOUNT = 10;

    protected const int MINIMUM_ITEM_AMOUNT = 1;

    protected const int PRIORITY = 100;

    protected const int NET_AMOUNT = 500;

    protected const int GROSS_AMOUNT = 595;

    protected const string COLLECTOR_FIELD_SKU = 'sku';

    protected const string DECISION_RULE_FIELD_SUB_TOTAL = 'sub-total';

    protected const string OPERATOR_EQUAL = '=';

    protected const string OPERATOR_GREATER_EQUAL = '>=';

    protected const string OPERATOR_LESS = '<';

    protected const string MATCH_ALL_VALUE = '*';

    protected const string SUB_TOTAL_VALUE = '500';

    protected const string SUB_TOTAL_MAX_VALUE = '900';

    protected const int VOUCHER_CODE_QUANTITY = 3;

    protected const int VOUCHER_CODE_LENGTH = 6;

    protected const int VOUCHER_MAX_NUMBER_OF_USES = 1;

    /**
     * @uses \Spryker\Shared\DiscountPromotion\DiscountPromotionConfig::DISCOUNT_COLLECTOR_STRATEGY
     */
    protected const string COLLECTOR_STRATEGY_PROMOTION = 'promotion';

    protected const string PROMOTION_ABSTRACT_SKU = '001';

    protected const int PROMOTION_QUANTITY = 1;

    public function getStoreName(): string
    {
        return static::STORE_NAME;
    }

    public function getSecondStoreName(): string
    {
        return static::STORE_NAME_SECOND;
    }

    public function getCurrencyIsoCode(): string
    {
        return static::CURRENCY_ISO_CODE;
    }

    public function getValidFrom(): string
    {
        return static::VALID_FROM;
    }

    public function getValidTo(): string
    {
        return static::VALID_TO;
    }

    public function getPercentageAmount(): int
    {
        return static::PERCENTAGE_AMOUNT;
    }

    public function getPriority(): int
    {
        return static::PRIORITY;
    }

    public function haveStoresForDiscounts(): void
    {
        foreach ([static::STORE_NAME, static::STORE_NAME_SECOND] as $storeName) {
            $this->getStoreDataHelper()->haveStore([StoreTransfer::NAME => $storeName]);
        }
    }

    /**
     * @return non-empty-string
     */
    public function createUniqueDisplayName(): string
    {
        return uniqid(static::LISTED_DISPLAY_NAME_PREFIX, false);
    }

    /**
     * @return non-empty-string
     */
    public function createUniqueVoucherCustomCode(): string
    {
        return strtoupper(uniqid(static::CUSTOM_CODE_PREFIX, false));
    }

    /**
     * @return non-empty-string
     */
    public function createUniqueVoucherCodePrefix(): string
    {
        return strtoupper(uniqid(static::VOUCHER_CODE_PREFIX, false)) . '-';
    }

    public function haveUsedVoucherCodes(string $codePrefix, int $numberOfUses): void
    {
        $voucherEntities = SpyDiscountVoucherQuery::create()
            ->filterByCode(sprintf('%s%%', $codePrefix), Criteria::LIKE)
            ->find();

        foreach ($voucherEntities as $voucherEntity) {
            $voucherEntity->setNumberOfUses($numberOfUses)->save();
        }
    }

    /**
     * @param array<string, mixed> $query
     */
    public function getDiscountOptionsUrl(array $query = []): string
    {
        return sprintf('/%s', DiscountConfig::RESOURCE_DISCOUNT_OPTIONS) . $this->formatQuery($query);
    }

    /**
     * @param array<string, mixed> $query
     */
    public function getDiscountCollectionUrl(array $query = []): string
    {
        return sprintf('/%s', DiscountConfig::RESOURCE_DISCOUNTS) . $this->formatQuery($query);
    }

    /**
     * @return non-empty-string
     */
    public function getDiscountUrl(string $uuid): string
    {
        return sprintf('/%s/%s', DiscountConfig::RESOURCE_DISCOUNTS, $uuid);
    }

    /**
     * @param array<string, mixed> $query
     */
    public function getDiscountVoucherCodeCollectionUrl(string $discountUuid, array $query = []): string
    {
        return sprintf(
            '%s/%s',
            $this->getDiscountUrl($discountUuid),
            static::PATH_SEGMENT_VOUCHER_CODES,
        ) . $this->formatQuery($query);
    }

    /**
     * @return non-empty-string
     */
    public function getDiscountVoucherCodesDeleteUrl(string $discountUuid): string
    {
        return sprintf(
            '%s/%s/%s',
            $this->getDiscountUrl($discountUuid),
            static::PATH_SEGMENT_VOUCHER_CODES,
            static::PATH_SEGMENT_DELETE,
        );
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function buildDiscountVoucherCodesDeleteRequestBody(array $attributes): string
    {
        return $this->buildRequestBody(DiscountConfig::RESOURCE_DISCOUNT_VOUCHER_CODES, $attributes);
    }

    /**
     * @return non-empty-string
     */
    public function getDiscountVoucherCodesGenerateUrl(string $discountUuid): string
    {
        return sprintf(
            '%s/%s/%s',
            $this->getDiscountUrl($discountUuid),
            static::PATH_SEGMENT_VOUCHER_CODES,
            static::PATH_SEGMENT_GENERATE,
        );
    }

    /**
     * @return non-empty-string
     */
    public function getDiscountVoucherCodesExportUrl(string $discountUuid): string
    {
        return sprintf(
            '%s/%s/%s',
            $this->getDiscountUrl($discountUuid),
            static::PATH_SEGMENT_VOUCHER_CODES,
            static::PATH_SEGMENT_EXPORT,
        );
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function buildDiscountRequestBody(array $attributes, ?string $uuid = null): string
    {
        return $this->buildRequestBody(DiscountConfig::RESOURCE_DISCOUNTS, $attributes, $uuid);
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function buildDiscountVoucherCodesGenerateRequestBody(array $attributes): string
    {
        return $this->buildRequestBody(DiscountConfig::RESOURCE_DISCOUNT_VOUCHER_CODES_GENERATE, $attributes);
    }

    /**
     * @param array<string, mixed> $override
     *
     * @return array<string, mixed>
     */
    public function buildValidDiscountAttributes(array $override = []): array
    {
        return $override + [
            'displayName' => $this->createUniqueDisplayName(),
            'description' => 'Created via the Discount Backend API test suite.',
            'discountType' => DiscountConstants::TYPE_CART_RULE,
            'isExclusive' => false,
            'validFrom' => static::VALID_FROM,
            'validTo' => static::VALID_TO,
            'calculatorType' => DiscountConfig::CALCULATOR_TYPE_PERCENTAGE,
            'percentage' => static::PERCENTAGE_AMOUNT,
            'collectorStrategyType' => DiscountConstants::DISCOUNT_COLLECTOR_STRATEGY_QUERY_STRING,
            'collector' => $this->buildMatchAllCollectorRuleGroup(),
            'decisionRule' => $this->buildSubTotalDecisionRuleGroup(),
            'minimumItemQuantity' => static::MINIMUM_ITEM_AMOUNT,
            'priority' => static::PRIORITY,
            'stores' => [static::STORE_NAME],
        ];
    }

    /**
     * @param array<string, mixed> $override
     *
     * @return array<string, mixed>
     */
    public function buildValidVoucherDiscountAttributes(array $override = []): array
    {
        return $this->buildValidDiscountAttributes(
            $override + ['discountType' => DiscountConstants::TYPE_VOUCHER],
        );
    }

    /**
     * @param array<string, mixed> $override
     *
     * @return array<string, mixed>
     */
    public function buildValidFixedAmountDiscountAttributes(array $override = []): array
    {
        return $this->buildValidDiscountAttributes(
            $override + [
                'calculatorType' => DiscountConfig::CALCULATOR_TYPE_FIXED,
                'percentage' => null,
                'fixedAmounts' => [$this->buildMoneyAmount()],
            ],
        );
    }

    /**
     * @param array<int, string> $abstractSkus
     * @param array<string, mixed> $override
     *
     * @return array<string, mixed>
     */
    public function buildValidPromotionDiscountAttributes(
        array $abstractSkus,
        ?int $quantity = null,
        array $override = [],
    ): array {
        return $this->buildValidDiscountAttributes(
            $override + [
                'collectorStrategyType' => static::COLLECTOR_STRATEGY_PROMOTION,
                'collector' => null,
                'promotion' => $this->buildPromotion($abstractSkus, $quantity),
            ],
        );
    }

    /**
     * @param array<int, string> $abstractSkus
     *
     * @return array<string, mixed>
     */
    public function buildPromotion(array $abstractSkus, ?int $quantity = null): array
    {
        return [
            'abstractSkus' => $abstractSkus,
            'quantity' => $quantity ?? static::PROMOTION_QUANTITY,
        ];
    }

    /**
     * @return array<int, string>
     */
    public function getPromotionAbstractSkus(): array
    {
        return [static::PROMOTION_ABSTRACT_SKU];
    }

    public function getPromotionCollectorStrategyType(): string
    {
        return static::COLLECTOR_STRATEGY_PROMOTION;
    }

    /**
     * @param array<string, mixed> $override
     *
     * @return array<string, mixed>
     */
    public function buildValidVoucherCodesGenerateAttributes(array $override = []): array
    {
        return $override + [
            'quantity' => static::VOUCHER_CODE_QUANTITY,
            'codeLength' => static::VOUCHER_CODE_LENGTH,
            'maxNumberOfUses' => static::VOUCHER_MAX_NUMBER_OF_USES,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function buildMoneyAmount(
        ?string $currencyIsoCode = null,
        ?int $netAmount = null,
        ?int $grossAmount = null,
    ): array {
        return [
            'currencyIsoCode' => $currencyIsoCode ?? static::CURRENCY_ISO_CODE,
            'netAmount' => $netAmount ?? static::NET_AMOUNT,
            'grossAmount' => $grossAmount ?? static::GROSS_AMOUNT,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function buildMatchAllCollectorRuleGroup(): array
    {
        return $this->buildRuleGroup([
            $this->buildRule(static::COLLECTOR_FIELD_SKU, static::OPERATOR_EQUAL, static::MATCH_ALL_VALUE),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function buildSubTotalDecisionRuleGroup(): array
    {
        return $this->buildRuleGroup([
            $this->buildRule(
                static::DECISION_RULE_FIELD_SUB_TOTAL,
                static::OPERATOR_GREATER_EQUAL,
                static::SUB_TOTAL_VALUE,
            ),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function buildThreeLevelDecisionRuleGroup(): array
    {
        return $this->buildRuleGroup(
            [
                $this->buildRule(
                    static::DECISION_RULE_FIELD_SUB_TOTAL,
                    static::OPERATOR_GREATER_EQUAL,
                    static::SUB_TOTAL_VALUE,
                ),
            ],
            [
                $this->buildRuleGroup(
                    [
                        $this->buildRule(
                            static::DECISION_RULE_FIELD_SUB_TOTAL,
                            static::OPERATOR_LESS,
                            static::SUB_TOTAL_MAX_VALUE,
                        ),
                    ],
                    [
                        $this->buildRuleGroup([
                            $this->buildRule(
                                static::DECISION_RULE_FIELD_SUB_TOTAL,
                                static::OPERATOR_EQUAL,
                                static::SUB_TOTAL_VALUE,
                            ),
                        ]),
                    ],
                    static::CONDITION_OR,
                ),
            ],
        );
    }

    /**
     * @param array<int, array<string, mixed>> $rules
     * @param array<int, array<string, mixed>> $groups
     *
     * @return array<string, mixed>
     */
    public function buildRuleGroup(array $rules = [], array $groups = [], string $condition = self::CONDITION_AND): array
    {
        return [
            'condition' => $condition,
            'rules' => $rules,
            'groups' => $groups,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function buildRule(string $field, string $operator, string $value, ?string $attribute = null): array
    {
        return [
            'field' => $field,
            'attribute' => $attribute,
            'operator' => $operator,
            'value' => $value,
        ];
    }

    /**
     * @param array<string, mixed> $attributes
     */
    protected function buildRequestBody(string $type, array $attributes, ?string $id = null): string
    {
        $data = [
            'type' => $type,
            'attributes' => $attributes,
        ];

        if ($id !== null) {
            $data['id'] = $id;
        }

        return (string)json_encode(['data' => $data]);
    }

    /**
     * @param array<string, mixed> $query
     */
    protected function formatQuery(array $query): string
    {
        if ($query === []) {
            return '';
        }

        return '?' . http_build_query($query);
    }

    protected function getStoreDataHelper(): StoreDataHelper
    {
        /** @var \SprykerTest\Shared\Store\Helper\StoreDataHelper $storeDataHelper */
        $storeDataHelper = $this->getModule('\\' . StoreDataHelper::class);

        return $storeDataHelper;
    }
}
