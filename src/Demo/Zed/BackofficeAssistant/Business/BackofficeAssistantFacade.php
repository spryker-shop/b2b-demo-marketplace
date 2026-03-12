<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\BackofficeAssistant\Business;

use Generated\Shared\Transfer\BackofficeAssistantConversationHistoryTransfer;
use Spryker\Zed\Kernel\Business\AbstractFacade;

/**
 * @method \Demo\Zed\BackofficeAssistant\Business\BackofficeAssistantBusinessFactory getFactory()
 */
class BackofficeAssistantFacade extends AbstractFacade implements BackofficeAssistantFacadeInterface
{
    public function createConversationHistory(
        BackofficeAssistantConversationHistoryTransfer $transfer,
    ): BackofficeAssistantConversationHistoryTransfer {
        return $this->getFactory()->createConversationHistoryManager()->create($transfer);
    }

    public function getConversationHistoriesByFkUser(int $idUser): array
    {
        return $this->getFactory()->createConversationHistoryManager()->getByFkUser($idUser);
    }

    public function hasConversationHistoryForUser(int $idUser, string $conversationReference): bool
    {
        return $this->getFactory()->createConversationHistoryManager()->hasForUser($idUser, $conversationReference);
    }
}
