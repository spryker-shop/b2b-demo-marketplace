<?php

/**
 * This configuration tells Symfony which services to make available through the Dependency Injection Container.
 * It automatically loads all services from project modules for Yves, Shared, and Client applications.
 */

declare(strict_types = 1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

// phpcs:ignore SlevomatCodingStandard.Functions.UnusedParameter
return static function (ContainerConfigurator $configurator): void {
};