<?php
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
        return $this->getSharedConfig()->get(OpenAiConstants::OPEN_AI_DEFAULT_MODEL, 'gpt-5');
    }

    /**
     * @return int
     */
    public function getDefaultTimeout(): int
    {
        return $this->getSharedConfig()->get(OpenAiConstants::OPEN_AI_DEFAULT_TIMEOUT, 120);
    }
}

