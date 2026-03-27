<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\BackofficeAssistant\Communication\Plugin\Agent;

use Demo\Shared\BackofficeAssistant\BackofficeAssistantConstants;
use Demo\Zed\BackofficeAssistant\Dependency\BackofficeAssistantAgentPluginInterface;
use Generated\Shared\Transfer\BackofficeAssistantPromptRequestTransfer;
use Generated\Shared\Transfer\BackofficeAssistantPromptResponseTransfer;
use Generated\Shared\Transfer\PromptMessageTransfer;
use Generated\Shared\Transfer\PromptRequestTransfer;
use Spryker\Shared\AiFoundation\AiFoundationConstants;
use Spryker\Zed\Kernel\Communication\AbstractPlugin;

/**
 * @method \Demo\Zed\BackofficeAssistant\Communication\BackofficeAssistantCommunicationFactory getFactory()
 */
class GeneralPurposeAgentPlugin extends AbstractPlugin implements BackofficeAssistantAgentPluginInterface
{
    public const string SYSTEM_PROMPT = '
You are General Purpose Agent of Backoffice Assistant!
You are helpful in any Spryker Backoffice questions only!
Always be brief and concise.
Never answer questions that are not related to Spryker Backoffice!
';

    public function getName(): string
    {
        return 'General Purpose Agent';
    }

    public function getDescription(): string
    {
        return 'Handles questions only about Spryker Backoffice navigation. Examples: "Where can I manage user roles?", "How do I access the CMS pages?"';
    }

    public function executeAgent(
        BackofficeAssistantPromptRequestTransfer $backofficeAssistantPromptRequest,
    ): BackofficeAssistantPromptResponseTransfer {
        $promptRequest = (new PromptRequestTransfer())
            ->setAiConfigurationName(BackofficeAssistantConstants::AI_CONFIGURATION_GENERAL_PURPOSE)
            ->setConversationReference($backofficeAssistantPromptRequest->getConversationReference())
            ->setPromptMessage(
                (new PromptMessageTransfer())
                    ->setType(AiFoundationConstants::MESSAGE_TYPE_USER)
                    ->setContent($backofficeAssistantPromptRequest->getPrompt())
                    ->setAttachments($backofficeAssistantPromptRequest->getAttachments()),
            );

        $promptResponse = $this->getFactory()->getAiFoundationFacade()->prompt($promptRequest);

        return (new BackofficeAssistantPromptResponseTransfer())
            ->setAiResponse($promptResponse->getMessage()?->getContent() ?? 'No response received.')
            ->setConversationReference($backofficeAssistantPromptRequest->getConversationReference());
    }
}
