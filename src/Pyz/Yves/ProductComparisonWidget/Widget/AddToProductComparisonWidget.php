<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Pyz\Yves\ProductComparisonWidget\Widget;

use Generated\Shared\Transfer\ProductComparisonTransfer;
use Spryker\Yves\Kernel\Widget\AbstractWidget;

/**
 * @method \Pyz\Yves\ProductComparisonWidget\ProductComparisonWidgetFactory getFactory()
 */
class AddToProductComparisonWidget extends AbstractWidget
{
    private const PARAMETER_ID_PRODUCT_ABSTRACT = 'idProductAbstract';

    private const PARAMETER_ENABLE_PRODUCT_COMPARISON_ADD = 'enableProductComparisonAdd';

    public function __construct(int $idProductAbstract)
    {
        $this->addParameter(self::PARAMETER_ID_PRODUCT_ABSTRACT, $idProductAbstract)
            ->addParameter(self::PARAMETER_ENABLE_PRODUCT_COMPARISON_ADD, $this->enableProductComparisonAdd($idProductAbstract));
    }

    public static function getName(): string
    {
        return 'AddToProductComparisonWidget';
    }

    public static function getTemplate(): string
    {
        return '@ProductComparisonWidget/views/add-to-product-comparison/add-to-product-comparison.twig';
    }

    private function enableProductComparisonAdd(int $idProductAbstract): bool
    {
        $productComparisonTransfer = (new ProductComparisonTransfer())
            ->setIdCustomer($this->getIdCustomer());

        $productComparisonTransfer = $this->getFactory()->getProductComparisonClient()->get($productComparisonTransfer);
        $productAbstractIds = $productComparisonTransfer->getProductAbstractIds();

        if (!$productAbstractIds) {
            return true;
        }

        return !in_array($idProductAbstract, $productAbstractIds);
    }

    private function getIdCustomer(): ?int
    {
        $customerTransfer = $this->getFactory()->getCustomerClient()->getCustomer();

        return $customerTransfer?->getIdCustomer();
    }
}
