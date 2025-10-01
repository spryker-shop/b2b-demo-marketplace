<?php

use Pyz\Glue\GlueApplication\Bootstrap\GlueBackendApiBootstrap;
use Pyz\Shared\ErrorHandler\GlueApiErrorHandlerEnvironment;
use Spryker\Shared\Config\Application\Environment;

define('APPLICATION', 'GLUE_BACKEND');
defined('APPLICATION_ROOT_DIR') || define('APPLICATION_ROOT_DIR', dirname(__DIR__, 2));

require_once APPLICATION_ROOT_DIR . '/vendor/autoload.php';

Environment::initialize();

$errorHandlerEnvironment = new GlueApiErrorHandlerEnvironment();
$errorHandlerEnvironment->initialize();

$bootstrap = new GlueBackendApiBootstrap();
$bootstrap
    ->boot()
    ->run();
