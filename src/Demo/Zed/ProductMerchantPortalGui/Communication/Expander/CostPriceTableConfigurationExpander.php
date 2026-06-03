<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\ProductMerchantPortalGui\Communication\Expander;

use Generated\Shared\Transfer\MoneyValueTransfer;
use Generated\Shared\Transfer\PriceProductTransfer;
use Spryker\Shared\GuiTable\Configuration\Builder\GuiTableConfigurationBuilderInterface;
use Spryker\Zed\ProductMerchantPortalGui\Dependency\Facade\ProductMerchantPortalGuiToPriceProductFacadeInterface;

class CostPriceTableConfigurationExpander
{
    /**
     * @uses \Spryker\Shared\PriceProduct\PriceProductConfig::PRICE_TYPE_DEFAULT
     *
     * @var string
     */
    protected const PRICE_TYPE_DEFAULT = 'default';

    /**
     * @var string
     */
    protected const TITLE_COLUMN_PREFIX_PRICE_TYPE_COST = 'Cost';

    /**
     * @var string
     */
    protected const PRICE_KEY_FORMAT = '%s[%s][%s]';

    /**
     * @var string
     */
    protected const INPUT_TYPE_NUMBER = 'number';

    public function __construct(
        protected ProductMerchantPortalGuiToPriceProductFacadeInterface $priceProductFacade,
    ) {
    }

    public function expand(GuiTableConfigurationBuilderInterface $guiTableConfigurationBuilder): GuiTableConfigurationBuilderInterface
    {
        foreach ($this->priceProductFacade->getPriceTypeValues() as $priceTypeTransfer) {
            $idPriceTypeName = mb_strtolower($priceTypeTransfer->getNameOrFail());

            if ($idPriceTypeName !== static::PRICE_TYPE_DEFAULT) {
                continue;
            }

            $idCostColumn = $this->createCostColumnId($idPriceTypeName);
            $title = sprintf('%s %s', static::TITLE_COLUMN_PREFIX_PRICE_TYPE_COST, ucfirst($idPriceTypeName));

            $guiTableConfigurationBuilder
                ->addColumnText($idCostColumn, $title, true, false)
                ->addEditableColumnInput(
                    $idCostColumn,
                    $title,
                    static::INPUT_TYPE_NUMBER,
                    [
                        'attrs' => [
                            'step' => '0.01',
                        ],
                    ],
                );
        }

        return $guiTableConfigurationBuilder;
    }

    protected function createCostColumnId(string $idPriceTypeName): string
    {
        return sprintf(
            static::PRICE_KEY_FORMAT,
            $idPriceTypeName,
            PriceProductTransfer::MONEY_VALUE,
            MoneyValueTransfer::COST_AMOUNT,
        );
    }
}
