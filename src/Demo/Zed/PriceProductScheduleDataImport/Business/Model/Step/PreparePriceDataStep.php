<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\PriceProductScheduleDataImport\Business\Model\Step;

use Demo\Zed\PriceProductScheduleDataImport\Business\Model\DataSet\PriceProductScheduleDataSetInterface;
use Spryker\Zed\DataImport\Business\Exception\InvalidDataException;
use Spryker\Zed\DataImport\Business\Model\DataSet\DataSetInterface;
use Spryker\Zed\PriceProductScheduleDataImport\Business\Model\Step\PreparePriceDataStep as SprykerPreparePriceDataStep;

class PreparePriceDataStep extends SprykerPreparePriceDataStep
{
    /**
     * @param \Spryker\Zed\DataImport\Business\Model\DataSet\DataSetInterface $dataSet
     *
     * @throws \Spryker\Zed\DataImport\Business\Exception\InvalidDataException
     *
     * @return void
     */
    public function execute(DataSetInterface $dataSet): void
    {
        parent::execute($dataSet);

        $dataSet[PriceProductScheduleDataSetInterface::KEY_PRICE_COST] =
            empty($dataSet[PriceProductScheduleDataSetInterface::KEY_PRICE_COST])
                ? null
                : (int)$dataSet[PriceProductScheduleDataSetInterface::KEY_PRICE_COST];

        if (!$this->isPriceValid($dataSet[PriceProductScheduleDataSetInterface::KEY_PRICE_COST])) {
            throw new InvalidDataException(sprintf(
                static::WRONG_PRICE_EXCEPTION_MESSAGE,
                $dataSet[PriceProductScheduleDataSetInterface::KEY_PRICE_COST],
            ));
        }
    }
}
