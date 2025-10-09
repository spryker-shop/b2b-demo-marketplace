<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Go\Zed\GuiAssistant\Communication;

use Go\Client\OpenAi\OpenAiClientInterface;
use Go\Zed\GuiAssistant\GuiAssistantDependencyProvider;
use Spryker\Zed\Kernel\Communication\AbstractCommunicationFactory;

class GuiAssistantCommunicationFactory extends AbstractCommunicationFactory
{
    public function getOpenAiClient(): OpenAiClientInterface
    {
        return $this->getProvidedDependency(GuiAssistantDependencyProvider::CLIENT_OPEN_AI);
    }
}
