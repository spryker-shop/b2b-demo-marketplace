<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Go\Client\OpenAi;

use Go\Shared\OpenAi\OpenAiConstants;
use Spryker\Client\Kernel\AbstractBundleConfig;

class OpenAiConfig extends AbstractBundleConfig
{
    /**
     * @return string
     */
    public function getDefaultApiKey(): string
    {
        return $this->getSharedConfig()->get(OpenAiConstants::OPEN_AI_DEFAULT_API_KEY);
    }

    /**
     * @return string
     */
    public function getDefaultModel(): string
    {
        return $this->getSharedConfig()->get(OpenAiConstants::OPEN_AI_DEFAULT_MODEL, 'o3');
    }

    /**
     * @return int
     */
    public function getDefaultTimeout(): int
    {
        return $this->getSharedConfig()->get(OpenAiConstants::OPEN_AI_DEFAULT_TIMEOUT, 60);
    }
}
