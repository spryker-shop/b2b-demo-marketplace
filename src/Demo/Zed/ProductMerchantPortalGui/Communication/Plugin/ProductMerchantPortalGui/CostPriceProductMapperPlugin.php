<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\ProductMerchantPortalGui\Communication\Plugin\ProductMerchantPortalGui;

use Generated\Shared\Transfer\PriceProductCollectionDeleteCriteriaTransfer;
use Generated\Shared\Transfer\PriceProductCriteriaTransfer;
use Generated\Shared\Transfer\PriceProductTableViewTransfer;
use Generated\Shared\Transfer\PriceProductTransfer;
use Spryker\Zed\Kernel\Communication\AbstractPlugin;
use Spryker\Zed\ProductMerchantPortalGuiExtension\Dependency\Plugin\PriceProductMapperPluginInterface;

/**
 * Surfaces `MoneyValueTransfer::costAmount` on the Merchant Portal price grid
 * for DEFAULT price-type rows: emits the cost-amount table-view key on read
 * and writes the value back from "Add" row submissions.
 *
 * @method \Demo\Zed\ProductMerchantPortalGui\Communication\ProductMerchantPortalGuiCommunicationFactory getFactory()
 * @method \Spryker\Zed\ProductMerchantPortalGui\ProductMerchantPortalGuiConfig getConfig()
 */
class CostPriceProductMapperPlugin extends AbstractPlugin implements PriceProductMapperPluginInterface
{
    /**
     * {@inheritDoc}
     * - Adds the cost-amount price key/value to the table-view prices for the DEFAULT price type.
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\PriceProductTransfer $priceProductTransfer
     * @param \Generated\Shared\Transfer\PriceProductTableViewTransfer $priceProductTableViewTransfer
     *
     * @return \Generated\Shared\Transfer\PriceProductTableViewTransfer
     */
    public function mapPriceProductTransferToPriceProductTableViewTransfer(
        PriceProductTransfer $priceProductTransfer,
        PriceProductTableViewTransfer $priceProductTableViewTransfer,
    ): PriceProductTableViewTransfer {
        return $this->getFactory()
            ->createCostPriceProductMapper()
            ->mapPriceProductTransferToPriceProductTableViewTransfer($priceProductTransfer, $priceProductTableViewTransfer);
    }

    /**
     * {@inheritDoc}
     * - No-op: single-cell cost edits are handled by the project-level `PriceFieldMapperStrategy`.
     *
     * @api
     *
     * @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
     *
     * @param array<string, mixed> $data
     * @param \Generated\Shared\Transfer\PriceProductTransfer $priceProductTransfer
     *
     * @return \Generated\Shared\Transfer\PriceProductTransfer
     */
    public function mapRequestDataToPriceProductTransfer(
        array $data,
        PriceProductTransfer $priceProductTransfer,
    ): PriceProductTransfer {
        return $priceProductTransfer;
    }

    /**
     * {@inheritDoc}
     * - Extracts `cost_amount` from a newly-added row and sets it on the MoneyValueTransfer (DEFAULT price type only).
     *
     * @api
     *
     * @param array<string, mixed> $data
     * @param \Generated\Shared\Transfer\PriceProductTransfer $priceProductTransfer
     *
     * @return \Generated\Shared\Transfer\PriceProductTransfer
     */
    public function mapTableDataToPriceProductTransfer(
        array $data,
        PriceProductTransfer $priceProductTransfer,
    ): PriceProductTransfer {
        return $this->getFactory()
            ->createCostPriceProductMapper()
            ->mapTableDataToPriceProductTransfer($data, $priceProductTransfer);
    }

    /**
     * {@inheritDoc}
     * - No-op: cost amount has no role in price product criteria filtering.
     *
     * @api
     *
     * @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
     *
     * @param array<string, mixed> $data
     * @param \Generated\Shared\Transfer\PriceProductCriteriaTransfer $priceProductCriteriaTransfer
     *
     * @return \Generated\Shared\Transfer\PriceProductCriteriaTransfer
     */
    public function mapRequestDataToPriceProductCriteriaTransfer(
        array $data,
        PriceProductCriteriaTransfer $priceProductCriteriaTransfer,
    ): PriceProductCriteriaTransfer {
        return $priceProductCriteriaTransfer;
    }

    /**
     * {@inheritDoc}
     * - No-op: cost-only rows are never deleted; deletion is keyed off store + dimension.
     *
     * @api
     *
     * @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
     *
     * @param array<\Generated\Shared\Transfer\PriceProductTransfer> $priceProductTransfers
     * @param \Generated\Shared\Transfer\PriceProductCollectionDeleteCriteriaTransfer $priceProductCollectionDeleteCriteriaTransfer
     *
     * @return \Generated\Shared\Transfer\PriceProductCollectionDeleteCriteriaTransfer
     */
    public function mapPriceProductTransfersToPriceProductCollectionDeleteCriteriaTransfer(
        array $priceProductTransfers,
        PriceProductCollectionDeleteCriteriaTransfer $priceProductCollectionDeleteCriteriaTransfer,
    ): PriceProductCollectionDeleteCriteriaTransfer {
        return $priceProductCollectionDeleteCriteriaTransfer;
    }
}
