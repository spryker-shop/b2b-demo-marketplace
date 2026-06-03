<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Yves\ProductGrossMarginWidget\Widget;

use Spryker\Yves\Kernel\Widget\AbstractWidget;

/**
 * @method \Demo\Yves\ProductGrossMarginWidget\ProductGrossMarginWidgetFactory getFactory()
 */
class ProductGrossMarginWidget extends AbstractWidget
{
    public function __construct(int $grossMargin, string $htmlContainer)
    {
        $this->addParameter('grossMargin', $grossMargin);
        $this->addParameter('htmlContainer', $htmlContainer);
        $this->addParameter('isGrossMarginVisible', $this->isVisible());
    }

    public static function getName(): string
    {
        return 'ProductGrossMarginWidget';
    }

    public static function getTemplate(): string
    {
        return '@ProductGrossMarginWidget/views/product-gross-margin-widget.twig';
    }

    protected function isVisible(): bool
    {
        return $this->getFactory()->getAgentClient()->isLoggedIn()
            && $this->getFactory()->getCustomerClient()->getCustomer() !== null;
    }
}
