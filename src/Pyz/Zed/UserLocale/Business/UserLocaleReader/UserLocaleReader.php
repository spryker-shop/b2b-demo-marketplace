<?php

namespace Pyz\Zed\UserLocale\Business\UserLocaleReader;

use Generated\Shared\Transfer\LocaleTransfer;
use Spryker\Zed\Locale\Business\LocaleFacade;

class UserLocaleReader extends \Spryker\Zed\UserLocale\Business\UserLocaleReader\UserLocaleReader
{
    /**
     * @return \Generated\Shared\Transfer\LocaleTransfer
     */
    protected function getCurrentLocale(): LocaleTransfer
    {
        $backofficeLocales = (new LocaleFacade())->getSupportedLocaleCodes();

        return $this->localeFacade->getLocale(reset($backofficeLocales));
    }
}
