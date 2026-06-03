<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\PriceProductScheduleDataImport\Business;

use Demo\Zed\PriceProductScheduleDataImport\Business\Model\PriceProductScheduleWriterStep;
use Demo\Zed\PriceProductScheduleDataImport\Business\Model\Step\PreparePriceDataStep;
use Spryker\Zed\DataImport\Business\Model\DataImportStep\DataImportStepInterface;
use Spryker\Zed\PriceProductScheduleDataImport\Business\PriceProductScheduleDataImportBusinessFactory as SprykerPriceProductScheduleDataImportBusinessFactory;

/**
 * @method \Spryker\Zed\PriceProductScheduleDataImport\PriceProductScheduleDataImportConfig getConfig()
 */
class PriceProductScheduleDataImportBusinessFactory extends SprykerPriceProductScheduleDataImportBusinessFactory
{
    public function createPreparePriceDataStep(): DataImportStepInterface
    {
        return new PreparePriceDataStep();
    }

    public function createPriceProductScheduleWriterStep(): DataImportStepInterface
    {
        return new PriceProductScheduleWriterStep();
    }
}
