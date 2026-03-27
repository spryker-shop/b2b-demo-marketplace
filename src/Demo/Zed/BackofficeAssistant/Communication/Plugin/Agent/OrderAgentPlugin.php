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
class OrderAgentPlugin extends AbstractPlugin implements BackofficeAssistantAgentPluginInterface
{
    public const string SYSTEM_PROMPT = '
You are Order Agent of Backoffice Assistant!
You are Responsible for order management related tasks and questions in Spryker.
Always be brief and concise.
Never answer questions that are not related to order management in Spryker!
';

    public function getName(): string
    {
        return 'Order Agent';
    }

    public function getDescription(): string
    {
        return 'Handles order management questions — order details, state machine transitions, returns, refunds, and order processing workflows. Examples: "Show me the order details", "How do I process a return?"';
    }

    public function executeAgent(
        BackofficeAssistantPromptRequestTransfer $backofficeAssistantPromptRequest,
    ): BackofficeAssistantPromptResponseTransfer {
        $promptRequest = (new PromptRequestTransfer())
            ->setAiConfigurationName(BackofficeAssistantConstants::AI_CONFIGURATION_ORDER)
            ->setConversationReference($backofficeAssistantPromptRequest->getConversationReference())
            ->addToolSetName('order_management')
            ->setPromptMessage(
                (new PromptMessageTransfer())
                    ->setType(AiFoundationConstants::MESSAGE_TYPE_USER)
                    ->setContent($backofficeAssistantPromptRequest->getPrompt())
                    ->setAttachments($backofficeAssistantPromptRequest->getAttachments()),
            );

        $promptResponse = $this->getFactory()->getAiFoundationFacade()->prompt($promptRequest);

        return (new BackofficeAssistantPromptResponseTransfer())
            ->setAiResponse($promptResponse->getMessage()?->getContent() ?? 'No response received.')
            ->setConversationReference($backofficeAssistantPromptRequest->getConversationReference())
            ->setToolInvocations($promptResponse->getToolInvocations());
    }
}
