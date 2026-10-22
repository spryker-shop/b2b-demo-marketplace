<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace PyzTest\Zed\Oms;

use Codeception\Actor;

/**
 * @method \Spryker\Zed\Oms\Business\OmsFacadeInterface getFacade()
 *
 * @SuppressWarnings(\PyzTest\Zed\Oms\PHPMD)
 */
class OmsBusinessTester extends Actor
{
    use _generated\OmsBusinessTesterActions;
}
