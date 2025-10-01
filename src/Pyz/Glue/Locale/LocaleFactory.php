<?php

namespace Pyz\Glue\Locale;

use Spryker\Glue\Kernel\Backend\AbstractBackendApiFactory;

class LocaleFactory extends AbstractBackendApiFactory
{
    /**
     * @return (\Spryker\Glue\Kernel\Backend\Locator&\Spryker\Shared\Kernel\LocatorLocatorInterface&\Generated\GlueBackend\Ide\AutoCompletion)|\Spryker\Glue\Kernel\Backend\Locator
     */
    public function getLocator()
    {
        return $this->getContainer()->getLocator();
    }
}
