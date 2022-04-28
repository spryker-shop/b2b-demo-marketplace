<?php

use Monolog\Logger;
use Pyz\Shared\Console\ConsoleConstants;
use Spryker\Shared\Api\ApiConstants;
use Spryker\Shared\Application\ApplicationConstants;
use Spryker\Shared\DocumentationGeneratorRestApi\DocumentationGeneratorRestApiConstants;
use Spryker\Shared\ErrorHandler\ErrorHandlerConstants;
use Spryker\Shared\ErrorHandler\ErrorRenderer\ApiDebugErrorRenderer;
use Spryker\Shared\ErrorHandler\ErrorRenderer\ApiErrorRenderer;
use Spryker\Shared\ErrorHandler\ErrorRenderer\WebExceptionErrorRenderer;
use Spryker\Shared\ErrorHandler\ErrorRenderer\WebHtmlErrorRenderer;
use Spryker\Shared\Event\EventConstants;
use Spryker\Shared\GlueApplication\GlueApplicationConstants;
use Spryker\Shared\Kernel\KernelConstants;
use Spryker\Shared\Log\LogConstants;
use Spryker\Shared\Propel\PropelConstants;
use Spryker\Shared\PropelOrm\PropelOrmConstants;
use Spryker\Shared\Queue\QueueConfig;
use Spryker\Shared\Queue\QueueConstants;
use Spryker\Shared\WebProfiler\WebProfilerConstants;
use Spryker\Shared\ZedRequest\ZedRequestConstants;
use SprykerShop\Shared\CalculationPage\CalculationPageConstants;
use SprykerShop\Shared\ErrorPage\ErrorPageConstants;
use SprykerShop\Shared\ShopApplication\ShopApplicationConstants;
use SprykerShop\Shared\WebProfilerWidget\WebProfilerWidgetConstants;

// ############################################################################
// ############################## DEVELOPMENT CONFIGURATION ###################
// ############################################################################

// ----------------------------------------------------------------------------
// ------------------------------ CODEBASE ------------------------------------
// ----------------------------------------------------------------------------

// >>> Debug

$config[KernelConstants::RESOLVABLE_CLASS_NAMES_CACHE_ENABLED] = false;
$config[KernelConstants::RESOLVED_INSTANCE_CACHE_ENABLED] = false;

$config[ApplicationConstants::ENABLE_APPLICATION_DEBUG]
    = $config[ShopApplicationConstants::ENABLE_APPLICATION_DEBUG]
    = (bool)getenv('SPRYKER_DEBUG_ENABLED');

$config[ApiConstants::ENABLE_API_DEBUG] = (bool)getenv('SPRYKER_DEBUG_ENABLED');
$config[PropelConstants::PROPEL_DEBUG] = (bool)getenv('SPRYKER_DEBUG_PROPEL_ENABLED');
$config[CalculationPageConstants::ENABLE_CART_DEBUG] = (bool)getenv('SPRYKER_DEBUG_ENABLED');
$config[ErrorPageConstants::ENABLE_ERROR_404_STACK_TRACE] = (bool)getenv('SPRYKER_DEBUG_ENABLED');
$config[GlueApplicationConstants::GLUE_APPLICATION_REST_DEBUG] = (bool)getenv('SPRYKER_DEBUG_ENABLED');

// >>> Dev tools

if (interface_exists(WebProfilerConstants::class, true)) {
    $config[WebProfilerConstants::IS_WEB_PROFILER_ENABLED]
        = $config[WebProfilerWidgetConstants::IS_WEB_PROFILER_ENABLED]
        = (getenv('SPRYKER_DEBUG_ENABLED') && !getenv('SPRYKER_TESTING_ENABLED'));
}
$config[KernelConstants::ENABLE_CONTAINER_OVERRIDING] = (bool)getenv('SPRYKER_TESTING_ENABLED');
$config[DocumentationGeneratorRestApiConstants::ENABLE_REST_API_DOCUMENTATION_GENERATION] = true;
$config[ConsoleConstants::ENABLE_DEVELOPMENT_CONSOLE_COMMANDS] = false;

// >>> Error handler

$config[ErrorHandlerConstants::DISPLAY_ERRORS] = true;
$config[ErrorHandlerConstants::ERROR_RENDERER] = getenv('SPRYKER_DEBUG_ENABLED') ? WebExceptionErrorRenderer::class : WebHtmlErrorRenderer::class;
$config[ErrorHandlerConstants::API_ERROR_RENDERER] = getenv('SPRYKER_DEBUG_ENABLED') ? ApiDebugErrorRenderer::class : ApiErrorRenderer::class;
$config[ErrorHandlerConstants::IS_PRETTY_ERROR_HANDLER_ENABLED] = (bool)getenv('SPRYKER_DEBUG_ENABLED');
$config[ErrorHandlerConstants::ERROR_LEVEL] = getenv('SPRYKER_DEBUG_DEPRECATIONS_ENABLED') ? E_ALL : $config[ErrorHandlerConstants::ERROR_LEVEL];

// ----------------------------------------------------------------------------
// ------------------------------ SERVICES ------------------------------------
// ----------------------------------------------------------------------------

// >>> QUEUE

$config[QueueConstants::QUEUE_ADAPTER_CONFIGURATION][EventConstants::EVENT_QUEUE][QueueConfig::CONFIG_MAX_WORKER_NUMBER] = 1;

// >>> LOGGER

$config[EventConstants::LOGGER_ACTIVE] = true;
$config[PropelOrmConstants::PROPEL_SHOW_EXTENDED_EXCEPTION] = true;
$config[LogConstants::LOG_LEVEL] = getenv('SPRYKER_DEBUG_ENABLED') ? Logger::INFO : Logger::DEBUG;

// >>> ZED REQUEST

$config[ZedRequestConstants::TRANSFER_DEBUG_SESSION_FORWARD_ENABLED] = (bool)getenv('SPRYKER_DEBUG_ENABLED');
$config[ZedRequestConstants::SET_REPEAT_DATA] = (bool)getenv('SPRYKER_DEBUG_ENABLED');

// ----------------------------------------------------------------------------
// ------------------------------ OMS -----------------------------------------
// ----------------------------------------------------------------------------

require 'common/config_oms-development.php';

// ----------------------------------------------------------------------------
// ------------------------------ PAYMENTS ------------------------------------
// ----------------------------------------------------------------------------

// >>> PAYONE

require 'common/config_payone-development.php';
