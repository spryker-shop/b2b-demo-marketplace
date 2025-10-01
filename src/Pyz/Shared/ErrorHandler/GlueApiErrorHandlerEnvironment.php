<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Shared\ErrorHandler;

use ErrorException;
use Pyz\Shared\ErrorHandler\Renderer\JsonApiErrorRenderer;
use Spryker\Shared\Config\Config;
use Spryker\Shared\ErrorHandler\ErrorHandlerConstants;
use Spryker\Shared\Log\LoggerTrait;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

/**
 * Error handler specifically for Glue API applications.
 * Renders errors in JSON:API format instead of HTML.
 */
class GlueApiErrorHandlerEnvironment
{
    use LoggerTrait;

    /**
     * Fatal error types that should trigger shutdown handler.
     *
     * @var array<int>
     */
    protected const ERRORS_FATAL = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR];

    /**
     * @var bool
     */
    protected bool $isInitialized = false;

    /**
     * @var \Pyz\Shared\ErrorHandler\Renderer\JsonApiErrorRenderer
     */
    protected JsonApiErrorRenderer $errorRenderer;

    /**
     * @var bool
     */
    protected bool $isDebugMode;

    /**
     * @var string
     */
    protected string $environment;

    public function __construct()
    {
        $this->errorRenderer = new JsonApiErrorRenderer();
        $this->isDebugMode = Config::get(ErrorHandlerConstants::IS_PRETTY_ERROR_HANDLER_ENABLED, false);
        $this->environment = defined('APPLICATION_ENV') ? APPLICATION_ENV : 'production';
        
        // Set error reporting level from config (same as Spryker's ErrorHandlerEnvironment)
        $errorCode = error_reporting();
        $configErrorCode = Config::get(ErrorHandlerConstants::ERROR_LEVEL);
        if ($configErrorCode !== $errorCode) {
            error_reporting($configErrorCode);
        }
    }

    /**
     * Initializes the error handler for Glue API applications.
     *
     * @return void
     */
    public function initialize(): void
    {
        if ($this->isInitialized) {
            return;
        }

        $this->registerErrorHandler();
        $this->registerExceptionHandler();
        $this->registerShutdownHandler();
        
        // Set display_errors setting (same as Spryker's ErrorHandlerEnvironment)
        ini_set('display_errors', Config::get(ErrorHandlerConstants::DISPLAY_ERRORS, false));

        $this->isInitialized = true;
    }

    /**
     * Registers custom error handler that converts PHP errors to exceptions.
     *
     * @return void
     */
    protected function registerErrorHandler(): void
    {
        $errorLevel = error_reporting();
        
        set_error_handler(function (int $severity, string $message, string $file, int $line) use ($errorLevel): bool {
            if (!(error_reporting() & $severity)) {
                return false;
            }

            $exception = new ErrorException($message, 0, $severity, $file, $line);
            
            // Check if this error level should only be logged (not thrown as exception)
            // This is how Spryker handles deprecations: log but don't throw
            $levelsNotThrowingExceptions = Config::get(ErrorHandlerConstants::ERROR_LEVEL_LOG_ONLY, 0);
            $shouldThrowException = ($severity & $levelsNotThrowingExceptions) === 0;
            
            if ($shouldThrowException) {
                throw $exception;
            }
            
            // Log only (e.g., for deprecations)
            $this->logException($exception);
            
            return true;
        }, $errorLevel);
    }

    /**
     * Registers exception handler that renders errors in JSON:API format.
     *
     * @return void
     */
    protected function registerExceptionHandler(): void
    {
        set_exception_handler(function (Throwable $exception): void {
            $this->handleException($exception);
        });
    }

    /**
     * Registers shutdown handler to catch fatal errors.
     *
     * @return void
     */
    protected function registerShutdownHandler(): void
    {
        register_shutdown_function(function (): void {
            $error = error_get_last();

            if ($error === null) {
                return;
            }

            // Only handle fatal errors (same as Spryker's ErrorHandlerEnvironment)
            if (!in_array($error['type'], static::ERRORS_FATAL, true)) {
                return;
            }

            $exception = new ErrorException(
                $error['message'],
                0,
                $error['type'],
                $error['file'],
                $error['line'],
            );

            $this->handleException($exception);
        });
    }

    /**
     * Handles exceptions by logging and rendering JSON:API error response.
     *
     * @param \Throwable $exception
     *
     * @return void
     */
    protected function handleException(Throwable $exception): void
    {
        try {
            // Log the exception
            $this->logException($exception);

            // Render JSON:API error response
            $response = $this->createErrorResponse($exception);

            // Send response
            $this->sendResponse($response);
        } catch (Throwable $e) {
            // Fallback if error handling itself fails
            $this->sendFallbackResponse($e);
        }
    }

    /**
     * Logs the exception using Spryker logging infrastructure.
     *
     * @param \Throwable $exception
     *
     * @return void
     */
    protected function logException(Throwable $exception): void
    {
        try {
            $context = [
                'exception' => $exception,
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString(),
            ];

            $this->getLogger()->error(
                sprintf('[%s] %s', get_class($exception), $exception->getMessage()),
                $context,
            );
        } catch (Throwable $e) {
            // Silently fail if logging fails
            error_log('Failed to log exception: ' . $e->getMessage());
        }
    }

    /**
     * Creates JSON:API formatted error response.
     *
     * @param \Throwable $exception
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    protected function createErrorResponse(Throwable $exception): JsonResponse
    {
        $statusCode = $this->getHttpStatusCode($exception);
        $includeTrace = $this->shouldIncludeStackTrace();

        $errorData = $this->errorRenderer->render($exception, $statusCode, $includeTrace);

        return new JsonResponse(
            $errorData,
            $statusCode,
            [
                'Content-Type' => 'application/vnd.api+json',
            ],
        );
    }

    /**
     * Determines HTTP status code from exception.
     *
     * @param \Throwable $exception
     *
     * @return int
     */
    protected function getHttpStatusCode(Throwable $exception): int
    {
        // Check if exception is Symfony HttpException
        if ($exception instanceof HttpExceptionInterface) {
            return $exception->getStatusCode();
        }

        // Check if exception has getCode and it's a valid HTTP status
        $code = $exception->getCode();
        if ($code >= 400 && $code < 600) {
            return $code;
        }

        // Default to 500 Internal Server Error
        return Response::HTTP_INTERNAL_SERVER_ERROR;
    }

    /**
     * Determines if stack trace should be included in error response.
     *
     * @return bool
     */
    protected function shouldIncludeStackTrace(): bool
    {
        return $this->isDebugMode || $this->environment === 'development';
    }

    /**
     * Sends the JSON response.
     *
     * @param \Symfony\Component\HttpFoundation\JsonResponse $response
     *
     * @return void
     */
    protected function sendResponse(JsonResponse $response): void
    {
        if (!headers_sent()) {
            $response->send();
        } else {
            echo $response->getContent();
        }

        exit(1);
    }

    /**
     * Sends fallback response if error handling itself fails.
     *
     * @param \Throwable $exception
     *
     * @return void
     */
    protected function sendFallbackResponse(Throwable $exception): void
    {
        $statusCode = Response::HTTP_INTERNAL_SERVER_ERROR;

        $errorResponse = [
            'errors' => [
                [
                    'status' => (string)$statusCode,
                    'code' => 'internal_error',
                    'title' => 'Internal Server Error',
                    'detail' => 'An unexpected error occurred while processing your request.',
                ],
            ],
        ];

        if (!headers_sent()) {
            header('Content-Type: application/vnd.api+json', true, $statusCode);
        }

        echo json_encode($errorResponse, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        exit(1);
    }
}
