<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\BackofficeAssistant\Persistence;

interface BackofficeAssistantRepositoryInterface
{
    /**
     * Specification:
     * - Returns all conversation history records for the given user, ordered by id descending.
     *
     * @api
     *
     * @param int $idUser
     *
     * @return array<\Generated\Shared\Transfer\BackofficeAssistantConversationHistoryTransfer>
     */
    public function getConversationHistoriesByFkUser(int $idUser): array;

    /**
     * Specification:
     * - Returns true when a conversation record owned by the user with the given reference exists.
     *
     * @api
     *
     * @param int $idUser
     * @param string $conversationReference
     *
     * @return bool
     */
    public function hasConversationHistoryForUser(int $idUser, string $conversationReference): bool;

    /**
     * Specification:
     * - Returns the agent string for the conversation with the given reference, or null if not found.
     *
     * @api
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
     * @api
     *
     * @param string $conversationReference
     *
     * @return string|null
     */
    public function findUserSelectedAgentByConversationReference(string $conversationReference): ?string;
}
