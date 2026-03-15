<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\BackofficeAssistant\Persistence;

use Generated\Shared\Transfer\BackofficeAssistantConversationHistoryTransfer;

interface BackofficeAssistantEntityManagerInterface
{
    /**
     * Specification:
     * - Persists a new conversation history record linked to a backoffice user.
     * - Returns the transfer with the generated primary key set.
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\BackofficeAssistantConversationHistoryTransfer $transfer
     *
     * @return \Generated\Shared\Transfer\BackofficeAssistantConversationHistoryTransfer
     */
    public function createConversationHistory(
        BackofficeAssistantConversationHistoryTransfer $transfer,
    ): BackofficeAssistantConversationHistoryTransfer;

    /**
     * Specification:
     * - Updates the agent field on the conversation identified by the given reference.
     *
     * @api
     *
     * @param string $conversationReference
     * @param string $agent
     *
     * @return void
     */
    public function updateConversationHistoryAgent(string $conversationReference, string $agent): void;

    /**
     * Specification:
     * - Deletes the conversation history record identified by the given reference.
     *
     * @api
     *
     * @param string $conversationReference
     *
     * @return void
     */
    /**
     * Specification:
     * - Updates the user_selected_agent field on the conversation identified by the given reference.
     * - Null means the user chose Auto (intent router decides).
     *
     * @api
     */
    public function updateConversationHistoryUserSelectedAgent(string $conversationReference, ?string $userSelectedAgent): void;

    /**
     * @param string $conversationReference
     *
     * @return void
     */
    public function deleteConversationHistoryByReference(string $conversationReference): void;
}
