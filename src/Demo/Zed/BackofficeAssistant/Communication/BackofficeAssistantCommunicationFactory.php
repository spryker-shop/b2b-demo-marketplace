<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\BackofficeAssistant\Communication;

use Demo\Zed\BackofficeAssistant\BackofficeAssistantDependencyProvider;
use Spryker\Zed\AiFoundation\Business\AiFoundationFacadeInterface;
use Spryker\Zed\Kernel\Communication\AbstractCommunicationFactory;
use Spryker\Zed\User\Business\UserFacadeInterface;

class BackofficeAssistantCommunicationFactory extends AbstractCommunicationFactory
{
    public function getAiFoundationFacade(): AiFoundationFacadeInterface
    {
        return $this->getProvidedDependency(BackofficeAssistantDependencyProvider::FACADE_AI_FOUNDATION);
    }

    public function getUserFacade(): UserFacadeInterface
    {
        return $this->getProvidedDependency(BackofficeAssistantDependencyProvider::FACADE_USER);
    }

    /**
     * @return array<\Demo\Zed\BackofficeAssistant\Dependency\BackofficeAssistantAgentPluginInterface>
     */
    public function getBackofficeAssistantAgentPlugins(): array
    {
        return $this->getProvidedDependency(BackofficeAssistantDependencyProvider::PLUGINS_BACKOFFICE_ASSISTANT_AGENT);
    }
}
