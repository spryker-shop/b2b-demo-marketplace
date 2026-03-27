<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\BackofficeAssistant\Persistence;

use Generated\Shared\Transfer\BackofficeAssistantConversationHistoryTransfer;
use Orm\Zed\BackofficeAssistant\Persistence\DemoBackofficeAssistantConversation;
use Spryker\Zed\Kernel\Persistence\AbstractEntityManager;

/**
 * @method \Demo\Zed\BackofficeAssistant\Persistence\BackofficeAssistantPersistenceFactory getFactory()
 */
class BackofficeAssistantEntityManager extends AbstractEntityManager implements BackofficeAssistantEntityManagerInterface
{
    public function createConversationHistory(
        BackofficeAssistantConversationHistoryTransfer $transfer,
    ): BackofficeAssistantConversationHistoryTransfer {
        $entity = $this->getFactory()
            ->createMapper()
            ->mapTransferToEntity($transfer, new DemoBackofficeAssistantConversation());

        $entity->save();

        return $this->getFactory()
            ->createMapper()
            ->mapEntityToTransfer($entity, $transfer);
    }

    /**
     * @param string $conversationReference
     * @param string $agent
     *
     * @return void
     */
    public function updateConversationHistoryAgent(string $conversationReference, string $agent): void
    {
        $entity = $this->getFactory()
            ->createBackofficeAssistantConversationQuery()
            ->filterByConversationReference($conversationReference)
            ->findOne();

        if ($entity === null) {
            return;
        }

        $entity->setAgent($agent);
        $entity->save();
    }

    /**
     * @param string $conversationReference
     * @param string|null $userSelectedAgent
     *
     * @return void
     */
    public function updateConversationHistoryUserSelectedAgent(string $conversationReference, ?string $userSelectedAgent): void
    {
        $entity = $this->getFactory()
            ->createBackofficeAssistantConversationQuery()
            ->filterByConversationReference($conversationReference)
            ->findOne();

        if ($entity === null) {
            return;
        }

        $entity->setUserSelectedAgent($userSelectedAgent);
        $entity->save();
    }

    /**
     * @param string $conversationReference
     *
     * @return void
     */
    public function deleteConversationHistoryByReference(string $conversationReference): void
    {
        $entity = $this->getFactory()
            ->createBackofficeAssistantConversationQuery()
            ->filterByConversationReference($conversationReference)
            ->findOne();

        if ($entity === null) {
            return;
        }

        $entity->delete();
    }
}
