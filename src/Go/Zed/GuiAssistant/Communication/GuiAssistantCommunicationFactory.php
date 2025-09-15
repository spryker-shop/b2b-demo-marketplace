<?php

namespace Go\Zed\GuiAssistant\Communication;

use Go\Client\OpenAi\OpenAiClientInterface;
use Go\Zed\GuiAssistant\Communication\Builder\ProductTransferBuilder;
use Go\Zed\GuiAssistant\GuiAssistantDependencyProvider;
use Spryker\Zed\Kernel\Communication\AbstractCommunicationFactory;

class GuiAssistantCommunicationFactory extends AbstractCommunicationFactory
{
    public function getOpenAiClient(): OpenAiClientInterface
    {
        return $this->getProvidedDependency(GuiAssistantDependencyProvider::CLIENT_OPEN_AI);
    }

    public function createProductTransferBuilder(): ProductTransferBuilder
    {
        return new ProductTransferBuilder();
    }
}
