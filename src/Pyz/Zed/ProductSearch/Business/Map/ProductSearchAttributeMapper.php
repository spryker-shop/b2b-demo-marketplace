<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Pyz\Zed\ProductSearch\Business\Map;

use Generated\Shared\Transfer\PageMapTransfer;
use Spryker\Zed\ProductSearch\Business\Map\Collector\ProductSearchAttributeMapCollectorInterface;
use Spryker\Zed\ProductSearch\Business\Map\ProductSearchAttributeMapper as SprykerProductSearchAttributeMapper;
use Spryker\Zed\Search\Business\Model\Elasticsearch\DataMapper\PageMapBuilderInterface;

class ProductSearchAttributeMapper extends SprykerProductSearchAttributeMapper
{
    protected function runAttributeMapCollector(
        ProductSearchAttributeMapCollectorInterface $attributeMapCollector,
        PageMapBuilderInterface $pageMapBuilder,
        PageMapTransfer $pageMapTransfer,
        array $attributes,
    ): PageMapTransfer {
        $attributeMap = $attributeMapCollector->getProductSearchAttributeMap();

        foreach ($attributeMap as $attributeMapTransfer) {
            $attributeName = $attributeMapTransfer->getAttributeName();

            if (!isset($attributes[$attributeName])) {
                continue;
            }

            foreach ($attributeMapTransfer->getTargetFields() as $targetFieldName) {
                $value = $attributes[$attributeName];
                if (!is_array($value)) {
                    $pageMapBuilder->add($pageMapTransfer, $targetFieldName, $attributeName, $value);

                    continue;
                }

                foreach ($value as $item) {
                    $pageMapBuilder->add($pageMapTransfer, $targetFieldName, $attributeName, $item);
                }
            }
        }

        return $pageMapTransfer;
    }
}
