<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Shared\ErrorHandler;

use ErrorException;
use Spryker\Shared\Config\Config;
use Spryker\Shared\ErrorHandler\ErrorHandlerConstants;
use Spryker\Shared\ErrorHandler\ErrorHandlerEnvironment as SprykerErrorHandlerEnvironment;
use Spryker\Shared\ErrorHandler\ErrorLogger;

class ErrorHandlerEnvironment extends SprykerErrorHandlerEnvironment
{
    /**
     * Unlike the core error handler, respects the `@` error suppression operator: PHP still invokes
     * a registered error handler for suppressed errors and signals suppression only through a reduced
     * `error_reporting()` mask. Vendor code relies on this contract for benign failures, e.g. the
     * `@mkdir()` race guard in `\Symfony\Component\Cache\Traits\FilesystemCommonTrait`, which the core
     * handler escalates to a fatal `ErrorException` under concurrent requests.
     *
     * @throws \ErrorException
     *
     * @return void
     */
    protected function setErrorHandler(): void
    {
        $errorLevel = error_reporting();
        $errorHandler = function (int $severity, string $message, string $file, int $line): bool {
            if ((error_reporting() & $severity) === 0) {
                return false;
            }

            $exception = new ErrorException($message, 0, $severity, $file, $line);

            $levelsNotThrowingExceptions = Config::get(ErrorHandlerConstants::ERROR_LEVEL_LOG_ONLY, 0);
            $shouldThrowException = ($severity & $levelsNotThrowingExceptions) === 0;
            if ($shouldThrowException) {
                throw $exception;
            }

            ErrorLogger::getInstance()->log($exception);

            return true;
        };

        set_error_handler($errorHandler, $errorLevel);
    }
}
