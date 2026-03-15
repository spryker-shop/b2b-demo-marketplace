<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\BackofficeAssistant\Business\ConversationHistory;

use Generated\Shared\Transfer\BackofficeAssistantConversationHistoryTransfer;

interface ConversationHistoryManagerInterface
{
    /**
     * Specification:
     * - Generates a conversation reference using the user ID, persists the conversation history record and returns it.
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\BackofficeAssistantConversationHistoryTransfer $transfer
     *
     * @return \Generated\Shared\Transfer\BackofficeAssistantConversationHistoryTransfer
     */
    public function create(
        BackofficeAssistantConversationHistoryTransfer $transfer,
    ): BackofficeAssistantConversationHistoryTransfer;

    /**
     * Specification:
     * - Returns all conversation history records for the given user ID, ordered by most recent first.
     *
     * @api
     *
     * @param int $idUser
     *
     * @return array<\Generated\Shared\Transfer\BackofficeAssistantConversationHistoryTransfer>
     */
    public function getByFkUser(int $idUser): array;

    /**
     * Specification:
     * - Returns true when the user owns a conversation with the given reference.
     *
     * @api
     *
     * @param int $idUser
     * @param string $conversationReference
     *
     * @return bool
     */
    public function hasForUser(int $idUser, string $conversationReference): bool;

    /**
     * Specification:
     * - Updates the agent field on the conversation identified by the given reference.
     *
     * @param string $conversationReference
     * @param string $agent
     *
     * @return void
     */
    public function updateAgent(string $conversationReference, string $agent): void;

    /**
     * Specification:
     * - Persists the user's explicit agent choice for the conversation (null = Auto).
     *
     * @param string $conversationReference
     * @param string|null $userSelectedAgent
     */
    public function updateUserSelectedAgent(string $conversationReference, ?string $userSelectedAgent): void;

    /**
     * Specification:
     * - Returns the agent string for the conversation with the given reference, or null if not found.
     *
     * @param string $conversationReference
     *
     * @return string|null
     */
    public function findAgentByConversationReference(string $conversationReference): ?string;

    /**
     * Specification:
     * - Returns the user_selected_agent for the conversation, or null if not set (Auto).
     *
     * @param string $conversationReference
     *
     * @return string|null
     */
    public function findUserSelectedAgentByConversationReference(string $conversationReference): ?string;

    /**
     * Specification:
     * - Deletes the conversation history record identified by the given reference.
     *
     * @param string $conversationReference
     *
     * @return void
     */
    public function deleteByReference(string $conversationReference): void;
}
