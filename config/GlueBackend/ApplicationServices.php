<?php

/**
 * This is an example configuration that can be used inside a project to tell Symfony which services it has to make
 * available through the Dependency Injection Container. It automatically loads all services from all project modules,
 * except for the ones that are explicitly excluded in the $excludedModuleConfiguration array.
 *
 * You can also write your custom solution as it is explained in the Symfony documentation.
 */

declare(strict_types = 1);

use Spryker\Service\Container\ProxyFactory;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $configurator): void {
    $services = $configurator->services()
        ->defaults()
        ->autowire()
        ->public()
        ->autoconfigure();

    /**
     * Make ProxyFactory available in the DIC. The Proxy is used to be able to lazy-load services which are not known to
     * the ContainerBuilder at compile time. The Container is compiled based on this configuration file, so services from modules
     * that are not included here will not be available at runtime unless they are proxied.
     */
    $services->set(ProxyFactory::class)->public();
};
