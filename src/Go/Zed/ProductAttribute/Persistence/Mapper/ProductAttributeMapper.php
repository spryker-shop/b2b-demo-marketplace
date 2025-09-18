<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Go\Zed\ProductAttribute\Persistence\Mapper;

use Generated\Shared\Transfer\ProductManagementAttributeValueTransfer;
use Orm\Zed\ProductAttribute\Persistence\SpyProductManagementAttributeValue;

class ProductAttributeMapper extends \Spryker\Zed\ProductAttribute\Persistence\Mapper\ProductAttributeMapper
{
    /**
     * @param \Orm\Zed\ProductAttribute\Persistence\SpyProductManagementAttributeValue $productManagementAttributeValueEntity
     * @param \Generated\Shared\Transfer\ProductManagementAttributeValueTransfer $productManagementAttributeValueTransfer
     *
     * @return \Generated\Shared\Transfer\ProductManagementAttributeValueTransfer
     */
    protected function mapProductManagementAttributeValueEntityToTransfer(
        SpyProductManagementAttributeValue $productManagementAttributeValueEntity,
        ProductManagementAttributeValueTransfer $productManagementAttributeValueTransfer
    ): ProductManagementAttributeValueTransfer {
        return $productManagementAttributeValueTransfer->fromArray(
            $productManagementAttributeValueEntity->toArray(), true
        );
    }
}
