<?php

namespace Pyz\Zed\UserLocale\Business;

use Pyz\Zed\UserLocale\Business\UserLocaleReader\UserLocaleReader;

class UserLocaleBusinessFactory extends \Spryker\Zed\UserLocale\Business\UserLocaleBusinessFactory
{
    /**
     * @return \Spryker\Zed\UserLocale\Business\UserLocaleReader\UserLocaleReaderInterface
     */
    public function createUserLocaleReader(): \Spryker\Zed\UserLocale\Business\UserLocaleReader\UserLocaleReaderInterface
    {
        return new UserLocaleReader(
            $this->getUserFacade(),
            $this->getLocaleFacade(),
            $this->getStoreFacade(),
        );
    }
}
