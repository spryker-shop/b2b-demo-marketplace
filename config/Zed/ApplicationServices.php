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
use Spryker\Zed\ModuleFinder\Business\ModuleFinderFacade;
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

    /**
     * Configuration array to exclude specific modules or specific applications within modules from being loaded into the DIC.
     * If a module is fully excluded, it will not be loaded at all. If only specific applications within a module are to be excluded,
     * the module name is the key and the value is an array with application names as keys and exclusion patterns as values.
     */
    $excludedModuleConfiguration = [
        'DataImport' => true,
        'ProductPageSearch' => true,
        'ProductStorage' => true,
        'PriceProductStorage' => true,
        'UrlStorage' => true,
        'DocumentationGeneratorRestApi' => true,
        'WebProfiler' => true,
    ];

    /**
     * Find all on the project level defined modules, including code bucket ones as well. The ModuleFinder also takes care of
     * filtering out modules that are not relevant for the current environment.
     *
     * F.e. In a dev environment you want to include all modules (composer:require-dev is installed), while in a production
     * environment you don't have all modules available but code in your project that extends it for such cases the compilation
     * process would fail if those modules are not found.
     */
    $moduleFinder = new ModuleFinderFacade();
    $projectModules = $moduleFinder->getProjectModules();

    foreach ($projectModules as $moduleTransfer) {
        /**
         * Skip excluded modules entirely when configured in the `$excludedModuleConfiguration`.
         */
        if (isset($excludedModuleConfiguration[$moduleTransfer->getName()]) && !is_array($excludedModuleConfiguration[$moduleTransfer->getName()])) {
            continue;
        }

        // Organization may be Pyz, or any of on the project level defined ones
        $organization = $moduleTransfer->getOrganization()->getName();

        foreach ($moduleTransfer->getApplications() as $applicationTransfer) {
            if ($applicationTransfer->getName() === 'Yves' || $applicationTransfer->getName() === 'Glue') {
                continue;
            }

            $namespace = sprintf('%s\\%s\\%s\\', $organization, $applicationTransfer->getName(), $moduleTransfer->getName());

            /**
             * Here is the path built to the services directory of the module for the specific application.
             *
             * This path structure is based on the standard Spryker project structure.
             */
            $path = sprintf(
                '../../src/%1$s/%2$s/%3$s/',
                $organization,
                $applicationTransfer->getName(),
                $moduleTransfer->getName(),
            );

            /**
             * This is the important part: Load all services from the module's application services directory into the DIC.
             *
             * You can also only use this line instead of the whole code in this file. But then you would have to make sure
             * that all modules you want to be available in the DIC are included manually here, which is not the preferred way.
             */
            $services->load($namespace, $path);
        }
    }
};
