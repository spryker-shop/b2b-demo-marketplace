<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Yves\Twig\Plugin;

use Spryker\Service\Container\ContainerInterface;
use Spryker\Shared\TwigExtension\Dependency\Plugin\TwigPluginInterface;
use Spryker\Yves\Kernel\AbstractPlugin;
use Twig\Environment;
use Twig\TwigFunction;

class GlobalStoreTwigPlugin extends AbstractPlugin implements TwigPluginInterface
{
    protected const string FUNCTION_NAME_SET_GLOBAL_STORE = 'setGlobalStore';

    protected const string FUNCTION_NAME_SET_GLOBAL_STORE_BY_NAME = 'setGlobalStoreByName';

    protected const string FUNCTION_NAME_GET_GLOBAL_STORE = 'getGlobalStore';

    protected const string FUNCTION_NAME_GET_GLOBAL_STORE_BY_NAME = 'getGlobalStoreByName';

    /**
     * @var array<string, mixed>
     */
    protected static array $store = [];

    /**
     * {@inheritDoc}
     * - Adds a functions to set and get global store data.
     *
     * @api
     */
    public function extend(Environment $twig, ContainerInterface $container): Environment // phpcs:ignore SlevomatCodingStandard.Functions.UnusedParameter
    {
        $twig = $this->addSetGlobalStoreFunction($twig);
        $twig = $this->addSetGlobalStoreByNameFunction($twig);
        $twig = $this->addGetGlobalStoreFunction($twig);
        $twig = $this->addGetGlobalStoreByNameFunction($twig);

        return $twig;
    }

    protected function addSetGlobalStoreFunction(Environment $twig): Environment
    {
        $twig->addFunction(new TwigFunction(
            static::FUNCTION_NAME_SET_GLOBAL_STORE,
            /**
             * @param array<string, mixed> $data
             */
            function (array $data): void {
                static::$store = [...static::$store, ...$data];
            },
        ));

        return $twig;
    }

    protected function addSetGlobalStoreByNameFunction(Environment $twig): Environment
    {
        $twig->addFunction(new TwigFunction(
            static::FUNCTION_NAME_SET_GLOBAL_STORE_BY_NAME,
            function (string $name, mixed $value): void {
                static::$store[$name] = $value;
            },
        ));

        return $twig;
    }

    protected function addGetGlobalStoreFunction(Environment $twig): Environment
    {
        $twig->addFunction(new TwigFunction(
            static::FUNCTION_NAME_GET_GLOBAL_STORE,
            /**
             * @return array<string, mixed>
             */
            function (): array {
                return static::$store;
            },
        ));

        return $twig;
    }

    protected function addGetGlobalStoreByNameFunction(Environment $twig): Environment
    {
        $twig->addFunction(new TwigFunction(
            static::FUNCTION_NAME_GET_GLOBAL_STORE_BY_NAME,
            function (string $name): mixed {
                return static::$store[$name] ?? null;
            },
        ));

        return $twig;
    }
}
