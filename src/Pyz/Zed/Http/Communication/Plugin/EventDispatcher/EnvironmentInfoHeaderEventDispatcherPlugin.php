<?php

namespace Pyz\Zed\Http\Communication\Plugin\EventDispatcher;

use Symfony\Component\HttpKernel\Event\ResponseEvent;

class EnvironmentInfoHeaderEventDispatcherPlugin extends \Spryker\Zed\Http\Communication\Plugin\EventDispatcher\EnvironmentInfoHeaderEventDispatcherPlugin
{
    /**
     * @param \Symfony\Component\HttpKernel\Event\ResponseEvent $event A ResponseEvent instance
     *
     * @return void
     */
    protected function onKernelResponse(ResponseEvent $event): void
    {
        if (!$this->isMainRequest($event)) {
            return;
        }

        $response = $event->getResponse();

        $localeFacade = $this->getFactory()->getLocaleFacade();

        $response->headers->set(static::HEADER_X_CODE_BUCKET_NAME, APPLICATION_CODE_BUCKET);
        $response->headers->set(static::HEADER_X_ENV_NAME, APPLICATION_ENV);
        $response->headers->set(static::HEADER_X_LOCALE_NAME, (string)$localeFacade->getCurrentLocale()->getLocaleName());
    }
}
