<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\PriceProductOfferDataImport\Business;

use Demo\Zed\PriceProductOfferDataImport\Business\Step\CombinedPriceProductStoreWriterStep;
use Demo\Zed\PriceProductOfferDataImport\Business\Step\PriceProductStoreWriterStep;
use Pyz\Zed\PriceProductOfferDataImport\Business\PriceProductOfferDataImportBusinessFactory as PyzPriceProductOfferDataImportBusinessFactory;
use Spryker\Zed\DataImport\Business\Model\DataImportStep\DataImportStepInterface;

/**
 * @method \Pyz\Zed\PriceProductOfferDataImport\PriceProductOfferDataImportConfig getConfig()
 */
class PriceProductOfferDataImportBusinessFactory extends PyzPriceProductOfferDataImportBusinessFactory
{
    public function createPriceProductStoreWriterStep(): DataImportStepInterface
    {
        return new PriceProductStoreWriterStep();
    }

    public function createCombinedPriceProductStoreWriterStep(): DataImportStepInterface
    {
        return new CombinedPriceProductStoreWriterStep();
    }
}
