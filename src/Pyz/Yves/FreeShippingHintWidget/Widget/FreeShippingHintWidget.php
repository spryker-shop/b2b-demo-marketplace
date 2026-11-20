<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Yves\FreeShippingHintWidget\Widget;

use Generated\Shared\Transfer\QuoteTransfer;
use Spryker\Yves\Kernel\Widget\AbstractWidget;

class FreeShippingHintWidget extends AbstractWidget
{
    /**
     * @var string
     */
    protected const PARAMETER_FREE_SHIPPING_HINT = 'freeShippingHint';

    public function __construct(QuoteTransfer $quoteTransfer)
    {
        $this->addParameter(static::PARAMETER_FREE_SHIPPING_HINT, $quoteTransfer->getFreeShippingHint());
    }

    public static function getName(): string
    {
        return 'FreeShippingHintWidget';
    }

    public static function getTemplate(): string
    {
        return '@FreeShippingHintWidget/views/free-shipping-hint/free-shipping-hint.twig';
    }
}
