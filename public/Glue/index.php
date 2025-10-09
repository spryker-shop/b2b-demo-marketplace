<?php

use Pyz\Glue\GlueApplication\Bootstrap\GlueBootstrap;
use Pyz\Shared\ErrorHandler\GlueApiErrorHandlerEnvironment;
use Spryker\Shared\Config\Application\Environment;

define('APPLICATION', 'GLUE');
defined('APPLICATION_ROOT_DIR') || define('APPLICATION_ROOT_DIR', dirname(__DIR__, 2));

require_once APPLICATION_ROOT_DIR . '/vendor/autoload.php';

Environment::initialize();

$errorHandlerEnvironment = new GlueApiErrorHandlerEnvironment();
$errorHandlerEnvironment->initialize();

$bootstrap = new GlueBootstrap();
$bootstrap
    ->boot()
    ->run();
