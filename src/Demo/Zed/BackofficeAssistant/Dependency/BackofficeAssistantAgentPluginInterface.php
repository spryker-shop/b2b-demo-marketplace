<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\BackofficeAssistant\Dependency;

use Generated\Shared\Transfer\BackofficeAssistantPromptRequestTransfer;
use Generated\Shared\Transfer\BackofficeAssistantPromptResponseTransfer;

interface BackofficeAssistantAgentPluginInterface
{
    /**
     * Specification:
     * - Returns the unique agent name used for routing (e.g., "Product").
     * - This name is matched against IntentRouterResponseTransfer::agent.
     *
     * @api
     */
    public function getName(): string;

    /**
     * Specification:
     * - Returns a description of what this agent handles.
     * - Used by the intent router AI to decide which agent matches user intent.
     *
     * @api
     */
    public function getDescription(): string;

    /**
     * Specification:
     * - The caller executes the request via AiFoundationFacade::prompt().
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\BackofficeAssistantPromptRequestTransfer $backofficeAssistantPromptRequest
     *
     * @return \Generated\Shared\Transfer\BackofficeAssistantPromptResponseTransfer
     */
    public function executeAgent(
        BackofficeAssistantPromptRequestTransfer $backofficeAssistantPromptRequest,
    ): BackofficeAssistantPromptResponseTransfer;
}
