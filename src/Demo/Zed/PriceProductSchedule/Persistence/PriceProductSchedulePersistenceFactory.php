<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\PriceProductSchedule\Persistence;

use Demo\Zed\PriceProductSchedule\Persistence\Propel\Mapper\PriceProductScheduleMapper;
use Spryker\Zed\PriceProductSchedule\Persistence\PriceProductSchedulePersistenceFactory as SprykerPriceProductSchedulePersistenceFactory;
use Spryker\Zed\PriceProductSchedule\Persistence\Propel\Mapper\PriceProductScheduleMapperInterface;

/**
 * @method \Spryker\Zed\PriceProductSchedule\Persistence\PriceProductScheduleRepositoryInterface getRepository()
 * @method \Spryker\Zed\PriceProductSchedule\Persistence\PriceProductScheduleEntityManagerInterface getEntityManager()
 * @method \Demo\Zed\PriceProductSchedule\PriceProductScheduleConfig getConfig()
 */
class PriceProductSchedulePersistenceFactory extends SprykerPriceProductSchedulePersistenceFactory
{
    public function createPriceProductScheduleMapper(): PriceProductScheduleMapperInterface
    {
        return new PriceProductScheduleMapper(
            $this->createPriceProductScheduleListMapper(),
            $this->getConfig(),
        );
    }
}
