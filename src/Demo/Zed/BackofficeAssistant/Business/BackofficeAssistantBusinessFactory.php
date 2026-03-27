<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\BackofficeAssistant\Business;

use Demo\Service\BackofficeAssistant\BackofficeAssistantServiceInterface;
use Demo\Zed\BackofficeAssistant\BackofficeAssistantDependencyProvider;
use Demo\Zed\BackofficeAssistant\Business\ConversationHistory\ConversationHistoryManager;
use Demo\Zed\BackofficeAssistant\Business\ConversationHistory\ConversationHistoryManagerInterface;
use Spryker\Zed\Kernel\Business\AbstractBusinessFactory;

/**
 * @method \Demo\Zed\BackofficeAssistant\Persistence\BackofficeAssistantEntityManagerInterface getEntityManager()
 * @method \Demo\Zed\BackofficeAssistant\Persistence\BackofficeAssistantRepositoryInterface getRepository()
 */
class BackofficeAssistantBusinessFactory extends AbstractBusinessFactory
{
    /**
     * @return \Demo\Zed\BackofficeAssistant\Business\ConversationHistory\ConversationHistoryManagerInterface
     */
    public function createConversationHistoryManager(): ConversationHistoryManagerInterface
    {
        return new ConversationHistoryManager(
            $this->getRepository(),
            $this->getEntityManager(),
            $this->getBackofficeAssistantService(),
        );
    }

    /**
     * @return \Demo\Service\BackofficeAssistant\BackofficeAssistantServiceInterface
     */
    public function getBackofficeAssistantService(): BackofficeAssistantServiceInterface
    {
        return $this->getProvidedDependency(BackofficeAssistantDependencyProvider::SERVICE_BACKOFFICE_ASSISTANT);
    }
}
