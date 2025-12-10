<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Pyz\Zed\Oms\Persistence;

use Pyz\Zed\Oms\Persistence\Propel\Mapper\OrderItemMapper;
use Spryker\Zed\Oms\Persistence\OmsPersistenceFactory as SprykerOmsPersistenceFactory;
use Spryker\Zed\Oms\Persistence\Propel\Mapper\OrderItemMapperInterface;

class OmsPersistenceFactory extends SprykerOmsPersistenceFactory
{
     /**
     * @return \Spryker\Zed\Oms\Persistence\Propel\Mapper\OrderItemMapperInterface
     */
    public function createOrderItemMapper(): OrderItemMapperInterface
    {
        return new OrderItemMapper();
    }
}
