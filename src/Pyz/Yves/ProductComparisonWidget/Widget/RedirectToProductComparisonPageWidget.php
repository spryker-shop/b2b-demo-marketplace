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
 * @method \pyz\Yves\ProductComparisonWidget\ProductComparisonWidgetFactory getFactory()
 */
class RedirectToProductComparisonPageWidget extends AbstractWidget
{
    public function __construct()
    {
        $this->addParameter('existingComparisonListLength', $this->getExistingComparisonListLength());
    }

    public static function getName(): string
    {
        return 'RedirectToProductComparisonPageWidget';
    }

    public static function getTemplate(): string
    {
        return '@ProductComparisonWidget/views/redirect-to-product-comparison-page/redirect-to-product-comparison-page.twig';
    }

    private function getExistingComparisonListLength(): int
    {
        $productComparisonTransfer = $this->prepareProductComparisonTransfer();
        $productComparisonTransfer = $this->getFactory()->getProductComparisonClient()->get($productComparisonTransfer);

        if ($productComparisonTransfer->getProductAbstractIds()) {
            $comparisonItemsIdsArray = $productComparisonTransfer->getProductAbstractIds();
            $uniqComparisonItemsIdsArray = array_unique($comparisonItemsIdsArray);

            return count($uniqComparisonItemsIdsArray);
        }

        return 0;
    }

    private function prepareProductComparisonTransfer(): ProductComparisonTransfer
    {
        $customerTransfer = $this->getFactory()->getCustomerClient()->getCustomer();

        return (new ProductComparisonTransfer())
            ->setIdCustomer($customerTransfer?->getIdCustomer());
    }
}
