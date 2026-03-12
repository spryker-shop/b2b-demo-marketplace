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
}
