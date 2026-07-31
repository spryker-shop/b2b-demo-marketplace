<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace PyzTest\Shared\Store\Helper;

use Codeception\TestInterface;
use SprykerTest\Shared\Store\Helper\StoreDependencyHelper as SprykerStoreDependencyHelper;
use SprykerTest\Shared\Testify\Helper\LocatorHelperTrait;

/**
 * Resolves the current store from the database instead of the core helper's hardcoded `DE`.
 */
class StoreDependencyHelper extends SprykerStoreDependencyHelper
{
    use LocatorHelperTrait;

    /**
     * @param \Codeception\TestInterface $test
     *
     * @return void
     */
    public function _before(TestInterface $test)
    {
        parent::_before($test);

        $this->getContainerHelper()
            ->getContainer()
            ->set(static::SERVICE_STORE, $this->getDefaultStoreName());
    }

    /**
     * @return string
     */
    public function getDefaultStoreName(): string
    {
        $storeTransfers = $this->getLocator()->store()->facade()->getAllStores();

        return current($storeTransfers)->getNameOrFail();
    }
}
