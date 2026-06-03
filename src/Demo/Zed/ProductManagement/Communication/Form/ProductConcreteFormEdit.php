<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\ProductManagement\Communication\Form;

use Demo\Zed\ProductManagement\Communication\Form\Product\Price\ProductMoneyCollectionType;
use Generated\Shared\Transfer\PriceProductTransfer;
use Spryker\Zed\ProductManagement\Communication\Form\Product\Price\ProductMoneyType;
use Spryker\Zed\ProductManagement\Communication\Form\ProductConcreteFormEdit as SprykerProductConcreteFormEdit;
use Spryker\Zed\ProductManagement\Communication\Form\Validator\Constraints\ProductPriceNotBlank;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * @method \Demo\Zed\ProductManagement\Communication\ProductManagementCommunicationFactory getFactory()
 * @method \Pyz\Zed\ProductManagement\ProductManagementConfig getConfig()
 * @method \Spryker\Zed\ProductManagement\Persistence\ProductManagementRepositoryInterface getRepository()
 * @method \Spryker\Zed\ProductManagement\Business\ProductManagementFacadeInterface getFacade()
 */
class ProductConcreteFormEdit extends SprykerProductConcreteFormEdit
{
    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     * @param array<string, mixed> $options
     *
     * @return $this
     */
    // phpcs:ignore SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
    protected function addPriceForm(FormBuilderInterface $builder, array $options = [])
    {
        $builder->add(
            static::FIELD_PRICES,
            ProductMoneyCollectionType::class,
            [
                'entry_options' => [
                    'data_class' => PriceProductTransfer::class,
                ],
                'entry_type' => ProductMoneyType::class,
                'constraints' => [
                    new ProductPriceNotBlank([
                        'groups' => [self::VALIDATION_GROUP_PRICE_SOURCE],
                    ]),
                ],
            ],
        );

        return $this;
    }
}
