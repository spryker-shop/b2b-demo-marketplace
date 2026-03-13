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
}
