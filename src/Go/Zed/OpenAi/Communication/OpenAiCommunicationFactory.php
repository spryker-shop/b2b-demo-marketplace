<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Go\Zed\OpenAi\Communication;

use Go\Client\OpenAi\OpenAiClientInterface;
use Go\Zed\OpenAi\OpenAiDependencyProvider;
use Spryker\Zed\Kernel\Communication\AbstractCommunicationFactory;

class OpenAiCommunicationFactory extends AbstractCommunicationFactory
{
    public function getOpenAiClient(): OpenAiClientInterface
    {
        return $this->getProvidedDependency(OpenAiDependencyProvider::CLIENT_OPEN_AI);
    }
}
