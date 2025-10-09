<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Shared\ErrorHandler\Renderer;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

/**
 * Renders exceptions in JSON:API format.
 * @see https://jsonapi.org/format/#errors
 */
class JsonApiErrorRenderer
{
    /**
     * @var array<string, string>
     */
    protected const HTTP_STATUS_TEXTS = [
        Response::HTTP_BAD_REQUEST => 'Bad Request',
        Response::HTTP_UNAUTHORIZED => 'Unauthorized',
        Response::HTTP_FORBIDDEN => 'Forbidden',
        Response::HTTP_NOT_FOUND => 'Not Found',
        Response::HTTP_METHOD_NOT_ALLOWED => 'Method Not Allowed',
        Response::HTTP_NOT_ACCEPTABLE => 'Not Acceptable',
        Response::HTTP_CONFLICT => 'Conflict',
        Response::HTTP_GONE => 'Gone',
        Response::HTTP_UNPROCESSABLE_ENTITY => 'Unprocessable Entity',
        Response::HTTP_TOO_MANY_REQUESTS => 'Too Many Requests',
        Response::HTTP_INTERNAL_SERVER_ERROR => 'Internal Server Error',
        Response::HTTP_NOT_IMPLEMENTED => 'Not Implemented',
        Response::HTTP_BAD_GATEWAY => 'Bad Gateway',
        Response::HTTP_SERVICE_UNAVAILABLE => 'Service Unavailable',
        Response::HTTP_GATEWAY_TIMEOUT => 'Gateway Timeout',
    ];

    /**
     * Renders exception as JSON:API error object.
     *
     * @param \Throwable $exception
     * @param int $statusCode
     * @param bool $includeTrace
     *
     * @return array<string, mixed>
     */
    public function render(Throwable $exception, int $statusCode, bool $includeTrace = false): array
    {
        return [
            'errors' => [
                $this->createErrorObject($exception, $statusCode, $includeTrace),
            ],
        ];
    }

    /**
     * Creates a single error object according to JSON:API specification.
     *
     * @param \Throwable $exception
     * @param int $statusCode
     * @param bool $includeTrace
     *
     * @return array<string, mixed>
     */
    protected function createErrorObject(Throwable $exception, int $statusCode, bool $includeTrace): array
    {
        $error = [
            'status' => (string)$statusCode,
            'code' => $this->getErrorCode($exception),
            'title' => $this->getErrorTitle($statusCode),
            'detail' => $this->getErrorDetail($exception, $includeTrace),
        ];

        // Add source information if available
        $source = $this->getErrorSource($exception);
        if ($source) {
            $error['source'] = $source;
        }

        // Add meta information for debugging
        if ($includeTrace) {
            $error['meta'] = $this->getErrorMeta($exception);
        }

        return $error;
    }

    /**
     * Gets error code from exception.
     *
     * @param \Throwable $exception
     *
     * @return string
     */
    protected function getErrorCode(Throwable $exception): string
    {
        // Use exception class name as error code (convert to snake_case)
        $className = (new \ReflectionClass($exception))->getShortName();
        
        return $this->convertToSnakeCase($className);
    }

    /**
     * Gets error title based on HTTP status code.
     *
     * @param int $statusCode
     *
     * @return string
     */
    protected function getErrorTitle(int $statusCode): string
    {
        return static::HTTP_STATUS_TEXTS[$statusCode] ?? 'Error';
    }

    /**
     * Gets error detail from exception message.
     *
     * @param \Throwable $exception
     * @param bool $includeTrace
     *
     * @return string
     */
    protected function getErrorDetail(Throwable $exception, bool $includeTrace): string
    {
        $detail = $exception->getMessage();

        // In production, sanitize error messages for security
        if (!$includeTrace && $this->shouldSanitizeMessage($exception)) {
            $detail = 'An error occurred while processing your request.';
        }

        return $detail;
    }

    /**
     * Gets error source information (file and line).
     *
     * @param \Throwable $exception
     *
     * @return array<string, mixed>|null
     */
    protected function getErrorSource(Throwable $exception): ?array
    {
        // Only include source in development mode
        if (!$this->isDebugMode()) {
            return null;
        }

        return [
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
        ];
    }

    /**
     * Gets error meta information including stack trace.
     *
     * @param \Throwable $exception
     *
     * @return array<string, mixed>
     */
    protected function getErrorMeta(Throwable $exception): array
    {
        $meta = [
            'exception' => get_class($exception),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
        ];

        // Add stack trace
        $meta['trace'] = $this->formatStackTrace($exception);

        // Add previous exception if exists
        if ($exception->getPrevious()) {
            $meta['previous'] = [
                'exception' => get_class($exception->getPrevious()),
                'message' => $exception->getPrevious()->getMessage(),
                'file' => $exception->getPrevious()->getFile(),
                'line' => $exception->getPrevious()->getLine(),
            ];
        }

        return $meta;
    }

    /**
     * Formats stack trace into a readable array.
     *
     * @param \Throwable $exception
     *
     * @return array<int, array<string, mixed>>
     */
    protected function formatStackTrace(Throwable $exception): array
    {
        $trace = [];
        
        foreach ($exception->getTrace() as $index => $frame) {
            $trace[] = [
                'index' => $index,
                'file' => $frame['file'] ?? 'unknown',
                'line' => $frame['line'] ?? 0,
                'function' => $frame['function'] ?? 'unknown',
                'class' => $frame['class'] ?? null,
                'type' => $frame['type'] ?? null,
            ];
        }

        return $trace;
    }

    /**
     * Converts string to snake_case.
     *
     * @param string $string
     *
     * @return string
     */
    protected function convertToSnakeCase(string $string): string
    {
        $string = preg_replace('/(?<!^)[A-Z]/', '_$0', $string);
        
        return strtolower($string ?? '');
    }

    /**
     * Determines if error message should be sanitized.
     *
     * @param \Throwable $exception
     *
     * @return bool
     */
    protected function shouldSanitizeMessage(Throwable $exception): bool
    {
        // Sanitize messages for internal server errors in production
        $statusCode = $exception instanceof HttpExceptionInterface
            ? $exception->getStatusCode() 
            : Response::HTTP_INTERNAL_SERVER_ERROR;

        return $statusCode >= 500;
    }

    /**
     * Checks if debug mode is enabled.
     *
     * @return bool
     */
    protected function isDebugMode(): bool
    {
        return defined('APPLICATION_ENV') && APPLICATION_ENV === 'development';
    }
}
