<?php

/**
 * This is an example configuration that can be used inside a project to tell Symfony which services it has to make
 *  available through the Dependency Injection Container. It automatically loads all services from all project modules,
 *  except for the ones that are explicitly excluded in the $excludedModuleConfiguration array.
 *
 *  You can also write your custom solution as it is explained in the Symfony documentation.
 */

declare(strict_types = 1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

// phpcs:ignore SlevomatCodingStandard.Functions.UnusedParameter
return static function (ContainerConfigurator $configurator): void {
};
