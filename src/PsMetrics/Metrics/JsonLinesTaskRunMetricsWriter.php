<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Shared\PublishAndSynchronizeMetrics\Metrics;

use Spryker\Shared\Log\LoggerTrait;
use Throwable;

class JsonLinesTaskRunMetricsWriter implements TaskRunMetricsWriterInterface
{
    use LoggerTrait;

    /**
     * @param string $outputFilePath
     */
    public function __construct(protected string $outputFilePath)
    {
    }

    /**
     * @param array<string, mixed> $record
     *
     * @return void
     */
    public function write(array $record): void
    {
        try {
            $encodedRecord = json_encode($record, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);

            $this->ensureDirectoryExists();

            // Appended with a single LOCK_EX write so concurrent queue task processes, of which
            // there are several by design, cannot interleave partial lines.
            $bytesWritten = file_put_contents(
                $this->outputFilePath,
                $encodedRecord . PHP_EOL,
                FILE_APPEND | LOCK_EX,
            );

            if ($bytesWritten === false) {
                $this->getLogger()->warning(
                    sprintf('Could not append a queue metrics record to "%s".', $this->outputFilePath),
                );
            }
        } catch (Throwable $throwable) {
            // Deliberate boundary: instrumentation must never take down a queue worker. The error is
            // surfaced to the log rather than discarded, so a silently empty metrics file is diagnosable.
            $this->getLogger()->warning(
                sprintf(
                    'Writing a queue metrics record to "%s" failed: %s',
                    $this->outputFilePath,
                    $throwable->getMessage(),
                ),
                ['exception' => $throwable],
            );
        }
    }

    /**
     * @return void
     */
    protected function ensureDirectoryExists(): void
    {
        $directory = dirname($this->outputFilePath);

        if (is_dir($directory)) {
            return;
        }

        // Guarded against the race between several task processes starting at once: a concurrent
        // creation makes mkdir() fail, which is harmless as long as the directory now exists.
        if (!@mkdir($directory, 0775, true) && !is_dir($directory)) {
            $this->getLogger()->warning(
                sprintf('Could not create the queue metrics directory "%s".', $directory),
            );
        }
    }
}
