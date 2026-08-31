<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\ProductOfferMerchantPortalGui\Communication\Expander;

use Generated\Shared\Transfer\MoneyValueTransfer;
use Generated\Shared\Transfer\PriceTypeTransfer;
use Spryker\Shared\GuiTable\Configuration\Builder\GuiTableConfigurationBuilderInterface;
use Spryker\Zed\ProductOfferMerchantPortalGui\Communication\GuiTable\Column\ColumnIdCreatorInterface;

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
    protected const INPUT_TYPE_NUMBER = 'number';

    public function __construct(protected ColumnIdCreatorInterface $columnIdCreator)
    {
    }

    /**
     * @param array<\Generated\Shared\Transfer\PriceTypeTransfer> $priceTypeTransfers
     */
    public function expandWithColumns(
        GuiTableConfigurationBuilderInterface $guiTableConfigurationBuilder,
        array $priceTypeTransfers,
    ): GuiTableConfigurationBuilderInterface {
        foreach ($this->filterCostPriceTypeTransfers($priceTypeTransfers) as $priceTypeTransfer) {
            $priceTypeName = $this->getPriceTypeName($priceTypeTransfer);

            $guiTableConfigurationBuilder->addColumnText(
                $this->createCostAmountColumnId($priceTypeName),
                $this->createColumnTitle($priceTypeName),
                true,
                false,
            );
        }

        return $guiTableConfigurationBuilder;
    }

    /**
     * @param array<\Generated\Shared\Transfer\PriceTypeTransfer> $priceTypeTransfers
     */
    public function expandWithEditableColumns(
        GuiTableConfigurationBuilderInterface $guiTableConfigurationBuilder,
        array $priceTypeTransfers,
    ): GuiTableConfigurationBuilderInterface {
        foreach ($this->filterCostPriceTypeTransfers($priceTypeTransfers) as $priceTypeTransfer) {
            $priceTypeName = $this->getPriceTypeName($priceTypeTransfer);

            $guiTableConfigurationBuilder->addEditableColumnInput(
                $this->createCostAmountColumnId($priceTypeName),
                $this->createColumnTitle($priceTypeName),
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

    /**
     * @param array<\Generated\Shared\Transfer\PriceTypeTransfer> $priceTypeTransfers
     *
     * @return array<\Generated\Shared\Transfer\PriceTypeTransfer>
     */
    protected function filterCostPriceTypeTransfers(array $priceTypeTransfers): array
    {
        return array_filter(
            $priceTypeTransfers,
            fn (PriceTypeTransfer $priceTypeTransfer): bool => $this->getPriceTypeName($priceTypeTransfer) === static::PRICE_TYPE_DEFAULT,
        );
    }

    protected function getPriceTypeName(PriceTypeTransfer $priceTypeTransfer): string
    {
        return mb_strtolower($priceTypeTransfer->getNameOrFail());
    }

    protected function createCostAmountColumnId(string $priceTypeName): string
    {
        return $this->columnIdCreator->createPriceKey($priceTypeName, MoneyValueTransfer::COST_AMOUNT);
    }

    protected function createColumnTitle(string $priceTypeName): string
    {
        return sprintf('%s %s', static::TITLE_COLUMN_PREFIX_PRICE_TYPE_COST, ucfirst($priceTypeName));
    }
}
