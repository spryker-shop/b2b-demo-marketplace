<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\PriceProductSchedule\Business;

use Demo\Zed\PriceProductSchedule\Business\PriceProductSchedule\Executor\PriceProductScheduleApplyTransactionExecutor;
use Demo\Zed\PriceProductSchedule\Business\PriceProductSchedule\PriceProductScheduleMapper;
use Spryker\Zed\PriceProductSchedule\Business\PriceProductSchedule\Executor\PriceProductScheduleApplyTransactionExecutorInterface;
use Spryker\Zed\PriceProductSchedule\Business\PriceProductSchedule\PriceProductScheduleMapperInterface;
use Spryker\Zed\PriceProductSchedule\Business\PriceProductScheduleBusinessFactory as SprykerPriceProductScheduleBusinessFactory;

/**
 * @method \Spryker\Zed\PriceProductSchedule\Persistence\PriceProductScheduleRepositoryInterface getRepository()
 * @method \Spryker\Zed\PriceProductSchedule\Persistence\PriceProductScheduleEntityManagerInterface getEntityManager()
 * @method \Demo\Zed\PriceProductSchedule\PriceProductScheduleConfig getConfig()
 */
class PriceProductScheduleBusinessFactory extends SprykerPriceProductScheduleBusinessFactory
{
    public function createPriceProductScheduleMapper(): PriceProductScheduleMapperInterface
    {
        return new PriceProductScheduleMapper();
    }

    public function createPriceProductScheduleApplyTransactionExecutor(): PriceProductScheduleApplyTransactionExecutorInterface
    {
        return new PriceProductScheduleApplyTransactionExecutor(
            $this->createPriceProductScheduleDisabler(),
            $this->getPriceProductFacade(),
            $this->getEntityManager(),
        );
    }
}
