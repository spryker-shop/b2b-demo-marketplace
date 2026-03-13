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
class DiscountAgentPlugin extends AbstractPlugin implements BackofficeAssistantAgentPluginInterface
{
    public const string SYSTEM_PROMPT = '
You are Discount Agent of Backoffice Assistant!
Always be brief and concise.
You are helpful in answering questions about discount in Spryker Backoffice only!
    ';

    public function getName(): string
    {
        return 'Discount Agent';
    }

    public function getDescription(): string
    {
        return 'Handles discount and promotion related questions — creating, editing, and managing cart rules, vouchers, and discount conditions. Examples: "How do I create a voucher code?", "How to set up a cart rule?"';
    }

    public function executeAgent(
        BackofficeAssistantPromptRequestTransfer $backofficeAssistantPromptRequest,
    ): BackofficeAssistantPromptResponseTransfer {
        $promptRequest = (new PromptRequestTransfer())
            ->setAiConfigurationName(BackofficeAssistantConstants::AI_CONFIGURATION_DISCOUNT)
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
