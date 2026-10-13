<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Glue\Discount;

use Spryker\Glue\Discount\DiscountConfig as SprykerDiscountConfig;
use Spryker\Shared\DiscountPromotion\DiscountPromotionConfig;

class DiscountConfig extends SprykerDiscountConfig
{
    protected const string ATTRIBUTE_PROMOTION = 'promotion';

    protected const string TRANSFER_PATH_DISCOUNT_PROMOTION = 'discountCalculator.discountPromotion';

    protected const string ATTRIBUTE_PROMOTION_ABSTRACT_SKUS = 'promotion.abstractSkus';

    protected const string ATTRIBUTE_PROMOTION_QUANTITY = 'promotion.quantity';

    /**
     * @uses \Spryker\Zed\DiscountPromotion\Business\Validator\Rule\DiscountPromotion\AbstractSkusDiscountPromotionValidatorRule::ENTITY_IDENTIFIER_ABSTRACT_SKUS
     */
    protected const string ENTITY_IDENTIFIER_PROMOTION_ABSTRACT_SKUS = 'discountCalculator.discountPromotion.abstractSkus';

    /**
     * @uses \Spryker\Zed\DiscountPromotion\Business\Validator\Rule\DiscountPromotion\QuantityDiscountPromotionValidatorRule::ENTITY_IDENTIFIER_QUANTITY
     */
    protected const string ENTITY_IDENTIFIER_PROMOTION_QUANTITY = 'discountCalculator.discountPromotion.quantity';

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @return array<string, string>
     */
    public function getTransferPathByAttribute(): array
    {
        return parent::getTransferPathByAttribute() + [
            static::ATTRIBUTE_PROMOTION => static::TRANSFER_PATH_DISCOUNT_PROMOTION,
        ];
    }

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @return array<string, string>
     */
    public function getAttributeByEntityIdentifier(): array
    {
        return parent::getAttributeByEntityIdentifier() + [
            static::ENTITY_IDENTIFIER_PROMOTION_ABSTRACT_SKUS => static::ATTRIBUTE_PROMOTION_ABSTRACT_SKUS,
            static::ENTITY_IDENTIFIER_PROMOTION_QUANTITY => static::ATTRIBUTE_PROMOTION_QUANTITY,
        ];
    }

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @return array<string, string>
     */
    public function getResponseCodeByErrorMessage(): array
    {
        return parent::getResponseCodeByErrorMessage() + [
            DiscountPromotionConfig::TRANSLATION_KEY_ABSTRACT_SKUS_REQUIRED => static::RESPONSE_CODE_PROMOTION_INVALID,
            DiscountPromotionConfig::TRANSLATION_KEY_QUANTITY_INVALID => static::RESPONSE_CODE_PROMOTION_INVALID,
            DiscountPromotionConfig::TRANSLATION_KEY_ABSTRACT_SKUS_UNKNOWN => static::RESPONSE_CODE_PROMOTION_INVALID,
        ];
    }
}
