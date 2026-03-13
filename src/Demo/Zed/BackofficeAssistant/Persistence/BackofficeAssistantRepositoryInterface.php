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
}
