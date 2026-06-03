<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\MoneyGui\Communication\Form\Type;

use Spryker\Zed\MoneyGui\Communication\Form\Type\MoneyType as SprykerMoneyType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

/**
 * @method \Spryker\Zed\MoneyGui\MoneyGuiConfig getConfig()
 * @method \Spryker\Zed\MoneyGui\Communication\MoneyGuiCommunicationFactory getFactory()
 */
class MoneyType extends SprykerMoneyType
{
    public const FIELD_COST_AMOUNT = 'cost_amount';

    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     * @param array<string, mixed> $options
     *
     * @return void
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $validationGroups = $options[static::OPTION_VALIDATION_GROUPS];

        $this
            ->addAmountField($builder, static::FIELD_NET_AMOUNT, $validationGroups, [
                static::OPTION_LOCALE => $options[static::OPTION_LOCALE],
            ])
            ->addAmountField($builder, static::FIELD_GROSS_AMOUNT, $validationGroups, [
                static::OPTION_LOCALE => $options[static::OPTION_LOCALE],
            ])
            ->addAmountField($builder, static::FIELD_COST_AMOUNT, $validationGroups, [
                static::OPTION_LOCALE => $options[static::OPTION_LOCALE],
            ])
            ->addFkCurrencyField($builder)
            ->addFkStoreField($builder);

        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) use ($validationGroups): void {
                $moneyCurrencyOptions = $this->getFactory()
                    ->createMoneyTypeDataProvider()
                    ->getMoneyCurrencyOptions($event->getData());

                $this->configureMoneyInputs(
                    $event->getForm(),
                    static::FIELD_NET_AMOUNT,
                    $validationGroups,
                    $moneyCurrencyOptions,
                );
                $this->configureMoneyInputs(
                    $event->getForm(),
                    static::FIELD_GROSS_AMOUNT,
                    $validationGroups,
                    $moneyCurrencyOptions,
                );
                $this->configureMoneyInputs(
                    $event->getForm(),
                    static::FIELD_COST_AMOUNT,
                    $validationGroups,
                    $moneyCurrencyOptions,
                );
            },
        );
    }
}
