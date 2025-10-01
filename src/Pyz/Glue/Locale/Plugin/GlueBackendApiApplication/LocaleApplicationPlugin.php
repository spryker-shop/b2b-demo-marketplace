<?php

namespace Pyz\Glue\Locale\Plugin\GlueBackendApiApplication;

use Spryker\Service\Container\ContainerInterface;
use Spryker\Shared\ApplicationExtension\Dependency\Plugin\ApplicationPluginInterface;
use Spryker\Shared\Config\Application\Environment as ApplicationEnvironment;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @method \Pyz\Glue\Locale\LocaleFactory getFactory()
 * @method \Spryker\Client\Locale\LocaleClientInterface getClient()
 */
class LocaleApplicationPlugin extends \Spryker\Glue\Kernel\Backend\AbstractPlugin implements ApplicationPluginInterface
{
    /**
     * @uses \Spryker\Client\Locale\LocaleDependencyProvider::SERVICE_LOCALE
     *
     * @var string
     */
    protected const SERVICE_LOCALE = 'locale';

    /**
     * @uses \Spryker\Glue\GlueApplication\Rest\RequestConstantsInterface::HEADER_ACCEPT_LANGUAGE
     *
     * @var string
     */
    protected const HEADER_ACCEPT_LANGUAGE = 'accept-language';

    /**
     * @var string
     */
    protected const SERVICE_REQUEST_STACK = 'request_stack';

    /**
     * {@inheritDoc}
     * - Negotiates and provides application language ISO code.
     * - Sets the negotiated language ISO code to the container based on `Accept-Language` header.
     * - If the `Accept-Language` header is either empty or invalid, then language ISO code of the current store is used.
     * - If dynamic store is enabled, the store default language ISO code is used, otherwise the first of available store ISO codes.
     * - Throws exception {@link \Exception} while current store has no locale codes defined.
     *
     * @api
     *
     * @param \Spryker\Service\Container\ContainerInterface $container
     *
     * @return \Spryker\Service\Container\ContainerInterface
     */
    public function provide(ContainerInterface $container): ContainerInterface
    {
        $container = $this->addLocale($container);

        return $container;
    }

    /**
     * @param \Spryker\Service\Container\ContainerInterface $container
     *
     * @return \Spryker\Service\Container\ContainerInterface
     */
    protected function addLocale(ContainerInterface $container): ContainerInterface
    {
        $container->set(static::SERVICE_LOCALE, function (ContainerInterface $container) {
            $acceptLanguageHeader = $this->getAcceptLanguageHeader($container);
            $locale = $this->getLanguageIsoCode($acceptLanguageHeader);

            ApplicationEnvironment::initializeLocale($locale);

            return $locale;
        });

        return $container;
    }

    /**
     * @param \Spryker\Service\Container\ContainerInterface $container
     *
     * @return string|null
     */
    protected function getAcceptLanguageHeader(ContainerInterface $container): ?string
    {
        if ($this->getRequestStack($container)->getCurrentRequest() === null) {
            return null;
        }

        return $this->getRequestStack($container)
            ->getCurrentRequest()
            ->headers
            ->get(static::HEADER_ACCEPT_LANGUAGE);
    }

    /**
     * @param \Spryker\Service\Container\ContainerInterface $container
     *
     * @return \Symfony\Component\HttpFoundation\RequestStack
     */
    protected function getRequestStack(ContainerInterface $container): RequestStack
    {
        return $container->get(static::SERVICE_REQUEST_STACK);
    }

    /**
     * @param string|null $headerAcceptLanguage
     *
     * @throws \Exception
     *
     * @return string
     */
    public function getLanguageIsoCode(?string $headerAcceptLanguage = null): string
    {
        $storeLocaleCodes = $this->getFactory()->getLocator()->locale()->facade()->getAvailableLocales();

        if ($storeLocaleCodes === []) {
            throw new \Exception('Unable to get locale codes by current store.');
        }

        if (!$headerAcceptLanguage) {
            return $this->getDefaultLanguage($storeLocaleCodes);
        }

        foreach ($storeLocaleCodes as $localeName) {
            if ($localeName === $headerAcceptLanguage) {
                return $localeName;
            }
        }

        $acceptLanguageTransfer = $this->getFactory()->getLocator()->locale()->service()->getAcceptLanguage($headerAcceptLanguage, array_keys($storeLocaleCodes));

        if (!$acceptLanguageTransfer || $acceptLanguageTransfer->getType() === null) {
            return $this->getDefaultLanguage($storeLocaleCodes);
        }

        if (!isset($storeLocaleCodes[$acceptLanguageTransfer->getType()])) {
            return $this->getDefaultLanguage($storeLocaleCodes);
        }

        return $storeLocaleCodes[$acceptLanguageTransfer->getType()];
    }

    /**
     * @param array<string, string> $storeLocaleCodes
     *
     * @return string
     */
    protected function getDefaultLanguage(array $storeLocaleCodes): string
    {
        return (string)array_shift($storeLocaleCodes);
    }
}
