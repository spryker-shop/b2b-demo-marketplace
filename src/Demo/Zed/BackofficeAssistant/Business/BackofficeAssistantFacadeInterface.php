<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\BackofficeAssistant\Business;

use Generated\Shared\Transfer\BackofficeAssistantConversationHistoryTransfer;

interface BackofficeAssistantFacadeInterface
{
    /**
     * Specification:
     * - Generates a conversation reference and persists a new conversation history record for the user.
     * - Returns the transfer with the generated primary key and conversation reference set.
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
     * - Returns all conversation history records for the given user, ordered by most recent first.
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
     * - Returns true when the user owns a conversation with the given reference.
     *
     * @api
     *
     * @param int $idUser
     * @param string $conversationReference
     *
     * @return bool
     */
    public function hasConversationHistoryForUser(int $idUser, string $conversationReference): bool;
}
