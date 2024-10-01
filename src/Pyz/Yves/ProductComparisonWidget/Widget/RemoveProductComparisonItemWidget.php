<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Pyz\Yves\ProductComparisonWidget\Widget;

use Spryker\Yves\Kernel\Widget\AbstractWidget;

/**
 * @method \Pyz\Yves\ProductComparisonWidget\ProductComparisonWidgetFactory getFactory()
 */
class RemoveProductComparisonItemWidget extends AbstractWidget
{
    private const PARAMETER_ID_PRODUCT_ABSTRACT = 'idProductAbstract';

    public function __construct(int $idProductConcrete)
    {
        $this->addParameter(self::PARAMETER_ID_PRODUCT_ABSTRACT, $idProductConcrete);
    }

    public static function getName(): string
    {
        return 'RemoveProductComparisonItemWidget';
    }

    public static function getTemplate(): string
    {
        return '@ProductComparisonWidget/views/remove-product-comparison-item/remove-product-comparison-item.twig';
    }
}
