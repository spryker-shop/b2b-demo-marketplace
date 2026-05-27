<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\ProductManagement\Communication\Form\Product\Price;

use Spryker\Zed\ProductManagement\Communication\Form\Product\Price\ProductMoneyCollectionType as SprykerProductMoneyCollectionType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

/**
 * @method \Spryker\Zed\ProductManagement\Persistence\ProductManagementRepositoryInterface getRepository()
 * @method \Spryker\Zed\ProductManagement\Business\ProductManagementFacadeInterface getFacade()
 * @method \Pyz\Zed\ProductManagement\ProductManagementConfig getConfig()
 * @method \Demo\Zed\ProductManagement\Communication\ProductManagementCommunicationFactory getFactory()
 * @method \Spryker\Zed\ProductManagement\Persistence\ProductManagementQueryContainerInterface getQueryContainer()
 */
class ProductMoneyCollectionType extends SprykerProductMoneyCollectionType
{
    /**
     * @var string
     */
    protected const PRICE_TYPE_DEFAULT = 'DEFAULT';

    /**
     * @param \Symfony\Component\Form\FormView $productMoneyTypeFormView
     * @param \Symfony\Component\Form\FormView $moneyValueFormView
     * @param array<string, mixed> $priceTable
     *
     * @return array<string, mixed>
     */
    protected function buildPriceFormViewTable(
        FormView $productMoneyTypeFormView,
        FormView $moneyValueFormView,
        array $priceTable,
    ): array {
        $priceTypeTransfer = $this->extractPriceTypeTransfer($productMoneyTypeFormView);

        $grossPriceModeIdentifier = $this->getGrossPriceModeIdentifier();
        $netPriceModeIdentifier = $this->getNetPriceModeIdentifier();
        $costPriceModeIdentifier = $this->getCostPriceModeIdentifier();

        $priceType = $priceTypeTransfer->getName();
        $priceModeConfiguration = $priceTypeTransfer->getPriceModeConfiguration();

        $storeName = $moneyValueFormView->vars['store_name'];
        $currencyIsoCode = $this->extractCurrencyTransfer($moneyValueFormView)->getCode();

        if ($priceModeConfiguration !== $this->getPriceModeIdentifierForBothType()) {
            $priceTable[$storeName][$currencyIsoCode][$priceModeConfiguration][$priceType] = $productMoneyTypeFormView;

            return $priceTable;
        }

        $priceTable[$storeName][$currencyIsoCode][$netPriceModeIdentifier][$priceType] = $productMoneyTypeFormView;
        $priceTable[$storeName][$currencyIsoCode][$grossPriceModeIdentifier][$priceType] = $productMoneyTypeFormView;
        if ($priceType === static::PRICE_TYPE_DEFAULT) {
            $priceTable[$storeName][$currencyIsoCode][$costPriceModeIdentifier][$priceType] = $productMoneyTypeFormView;
        }

        return $priceTable;
    }

    /**
     * @param \Symfony\Component\Form\FormView $view
     * @param \Symfony\Component\Form\FormInterface $form
     * @param array<string, mixed> $options
     */
    public function finishView(FormView $view, FormInterface $form, array $options): void
    {
        unset($form);
        unset($options);

        $priceTypes = [
            $this->getGrossPriceModeIdentifier() => [],
            $this->getNetPriceModeIdentifier() => [],
            $this->getCostPriceModeIdentifier() => [],
        ];

        $priceTable = [];
        $currencies = [];
        $additionalParameters = [];

        foreach ($view as $productMoneyTypeFormView) {
            $moneyValueFormView = $productMoneyTypeFormView['moneyValue'];
            $additionalParameters = $this->buildAdditionalParameters($productMoneyTypeFormView, $moneyValueFormView, $additionalParameters);

            $priceTypes = $this->buildPriceTypeList($productMoneyTypeFormView, $priceTypes);
            $priceTable = $this->buildPriceFormViewTable($productMoneyTypeFormView, $moneyValueFormView, $priceTable);

            $currencyTransfer = $this->extractCurrencyTransfer($moneyValueFormView);
            $currencies[$currencyTransfer->getCode()] = $currencyTransfer;
        }

        $this->sortTable($priceTable);

        $view->vars['priceTable'] = $priceTable;
        $view->vars['priceTypes'] = $priceTypes;
        $view->vars['currencies'] = $currencies;

        $view->vars = array_merge($additionalParameters, $view->vars);
    }

    protected function getCostPriceModeIdentifier(): string
    {
        return $this->getFactory()->getPriceFacade()->getCostPriceModeIdentifier();
    }
}
