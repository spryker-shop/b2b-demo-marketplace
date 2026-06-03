<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\PriceProductVolumeGui\Communication\Form;

use Spryker\Zed\PriceProductVolumeGui\Communication\Form\PriceVolumeCollectionFormType as SprykerPriceVolumeCollectionFormType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * @method \Spryker\Zed\PriceProductVolumeGui\PriceProductVolumeGuiConfig getConfig()
 * @method \Demo\Zed\PriceProductVolumeGui\Communication\PriceProductVolumeGuiCommunicationFactory getFactory()
 */
class PriceVolumeCollectionFormType extends SprykerPriceVolumeCollectionFormType
{
    /**
     * @var string
     */
    public const FIELD_COST_PRICE = 'cost_price';

    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     * @param array<string, mixed> $options
     *
     * @return void
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);
        $this->addCostPriceField($builder, $options);
    }

    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     * @param array<string, mixed> $options
     *
     * @return \Spryker\Zed\PriceProductVolumeGui\Communication\Form\PriceVolumeCollectionFormType
     */
    protected function addCostPriceField(
        FormBuilderInterface $builder,
        array $options,
    ): SprykerPriceVolumeCollectionFormType {
        $this->addPriceField($builder, $options, static::FIELD_COST_PRICE);

        return $this;
    }
}
