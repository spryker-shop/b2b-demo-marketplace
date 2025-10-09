<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Go\Zed\ProductAttribute\Persistence;

use Go\Zed\ProductAttribute\Persistence\Mapper\ProductAttributeMapper;
use Spryker\Zed\ProductAttribute\Persistence\Mapper\ProductAttributeMapperInterface;

/**
 * @method \Spryker\Zed\ProductAttribute\ProductAttributeConfig getConfig()
 * @method \Spryker\Zed\ProductAttribute\Persistence\ProductAttributeQueryContainerInterface getQueryContainer()
 * @method \Spryker\Zed\ProductAttribute\Persistence\ProductAttributeRepositoryInterface getRepository()
 */
class ProductAttributePersistenceFactory extends \Spryker\Zed\ProductAttribute\Persistence\ProductAttributePersistenceFactory
{
    /**
     * @return \Spryker\Zed\ProductAttribute\Persistence\Mapper\ProductAttributeMapperInterface
     */
    public function createProductAttributeMapper(): ProductAttributeMapperInterface
    {
        return new ProductAttributeMapper();
    }
}
