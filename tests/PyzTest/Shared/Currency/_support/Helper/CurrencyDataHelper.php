<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace PyzTest\Shared\Currency\Helper;

use Generated\Shared\Transfer\CurrencyTransfer;
use SprykerTest\Shared\Currency\Helper\CurrencyDataHelper as SprykerCurrencyDataHelper;

class CurrencyDataHelper extends SprykerCurrencyDataHelper
{
    public function getCurrencyByIsoCode(string $isoCode): CurrencyTransfer
    {
        return $this->getCurrencyFacade()->fromIsoCode($isoCode);
    }
}
