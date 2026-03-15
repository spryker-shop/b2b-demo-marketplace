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
    /**
     * @param \Generated\Shared\Transfer\BackofficeAssistantConversationHistoryTransfer $transfer
     *
     * @return \Generated\Shared\Transfer\BackofficeAssistantConversationHistoryTransfer
     */
    public function createConversationHistory(
        BackofficeAssistantConversationHistoryTransfer $transfer,
    ): BackofficeAssistantConversationHistoryTransfer {
        return $this->getFactory()->createConversationHistoryManager()->create($transfer);
    }

    /**
     * @param int $idUser
     *
     * @return array<\Generated\Shared\Transfer\BackofficeAssistantConversationHistoryTransfer>
     */
    public function getConversationHistoriesByFkUser(int $idUser): array
    {
        return $this->getFactory()->createConversationHistoryManager()->getByFkUser($idUser);
    }

    /**
     * @param int $idUser
     * @param string $conversationReference
     *
     * @return bool
     */
    public function hasConversationHistoryForUser(int $idUser, string $conversationReference): bool
    {
        return $this->getFactory()->createConversationHistoryManager()->hasForUser($idUser, $conversationReference);
    }

    /**
     * @param string $conversationReference
     * @param string $agent
     *
     * @return void
     */
    public function updateConversationAgent(string $conversationReference, string $agent): void
    {
        $this->getFactory()->createConversationHistoryManager()->updateAgent($conversationReference, $agent);
    }

    /**
     * @return void
     */
    public function updateConversationUserSelectedAgent(string $conversationReference, ?string $userSelectedAgent): void
    {
        $this->getFactory()->createConversationHistoryManager()->updateUserSelectedAgent($conversationReference, $userSelectedAgent);
    }

    /**
     * @param string $conversationReference
     *
     * @return string|null
     */
    public function findAgentByConversationReference(string $conversationReference): ?string
    {
        return $this->getFactory()->createConversationHistoryManager()->findAgentByConversationReference($conversationReference);
    }

    public function findUserSelectedAgentByConversationReference(string $conversationReference): ?string
    {
        return $this->getFactory()->createConversationHistoryManager()->findUserSelectedAgentByConversationReference($conversationReference);
    }

    /**
     * @param string $conversationReference
     *
     * @return void
     */
    public function deleteConversationByReference(string $conversationReference): void
    {
        $this->getFactory()->createConversationHistoryManager()->deleteByReference($conversationReference);
    }
}
