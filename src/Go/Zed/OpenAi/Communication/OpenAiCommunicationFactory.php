<?php

declare(strict_types=1);

namespace Go\Zed\OpenAi\Communication;

use Go\Client\OpenAi\OpenAiClientInterface;
use Spryker\Zed\Kernel\Communication\AbstractCommunicationFactory;

class OpenAiCommunicationFactory extends AbstractCommunicationFactory
{
    public function getOpenAiClient(): OpenAiClientInterface
    {
        return $this->getProvidedDependency(\Go\Zed\OpenAi\OpenAiDependencyProvider::CLIENT_OPEN_AI);
    }
}

