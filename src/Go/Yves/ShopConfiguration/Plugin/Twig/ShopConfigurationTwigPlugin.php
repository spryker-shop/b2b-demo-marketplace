<?php

namespace Go\Yves\ShopConfiguration\Plugin\Twig;

use Spryker\Service\Container\ContainerInterface;
use Spryker\Shared\TwigExtension\Dependency\Plugin\TwigPluginInterface;
use Spryker\Yves\Kernel\AbstractPlugin;
use Twig\Environment;
use Twig\TwigFunction;

class ShopConfigurationTwigPlugin extends AbstractPlugin implements TwigPluginInterface
{
    /**
     * @var string
     */
    protected const FUNCTION_NAME = 'getConfig';

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @param \Twig\Environment $twig
     * @param \Spryker\Service\Container\ContainerInterface $container
     *
     * @return \Twig\Environment
     */
    public function extend(Environment $twig, ContainerInterface $container): Environment
    {
        $function = new TwigFunction(static::FUNCTION_NAME, function (string $key, mixed $default = null) {
            /** @var \Go\Client\ShopConfiguration\ShopConfigurationClient $shopConfigurationClient */
            $shopConfigurationClient = \Spryker\Client\Kernel\Locator::getInstance()
                ->shopConfiguration()
                ->client();
            $config = $shopConfigurationClient->getConfig($key);

            return $config ?? $default;
        });

        $twig->addFunction($function);

        return $twig;
    }
}
