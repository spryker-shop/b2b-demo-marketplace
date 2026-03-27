<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\BackofficeAssistant\Business\ConversationHistory;

use Demo\Service\BackofficeAssistant\BackofficeAssistantServiceInterface;
use Demo\Zed\BackofficeAssistant\Persistence\BackofficeAssistantEntityManagerInterface;
use Demo\Zed\BackofficeAssistant\Persistence\BackofficeAssistantRepositoryInterface;
use Generated\Shared\Transfer\BackofficeAssistantConversationHistoryTransfer;

class ConversationHistoryManager implements ConversationHistoryManagerInterface
{
    /**
     * @param \Demo\Zed\BackofficeAssistant\Persistence\BackofficeAssistantRepositoryInterface $repository
     * @param \Demo\Zed\BackofficeAssistant\Persistence\BackofficeAssistantEntityManagerInterface $entityManager
     * @param \Demo\Service\BackofficeAssistant\BackofficeAssistantServiceInterface $service
     */
    public function __construct(
        protected BackofficeAssistantRepositoryInterface $repository,
        protected BackofficeAssistantEntityManagerInterface $entityManager,
        protected BackofficeAssistantServiceInterface $service,
    ) {
    }

    public function create(
        BackofficeAssistantConversationHistoryTransfer $transfer,
    ): BackofficeAssistantConversationHistoryTransfer {
        $conversationReference = $this->service->generateConversationReference(
            (string)$transfer->getFkUser(),
        );

        $transfer->setConversationReference($conversationReference);

        return $this->entityManager->createConversationHistory($transfer);
    }

    public function getByFkUser(int $idUser): array
    {
        return $this->repository->getConversationHistoriesByFkUser($idUser);
    }

    /**
     * @param int $idUser
     * @param string $conversationReference
     *
     * @return bool
     */
    public function hasForUser(int $idUser, string $conversationReference): bool
    {
        return $this->repository->hasConversationHistoryForUser($idUser, $conversationReference);
    }

    /**
     * @param string $conversationReference
     * @param string $agent
     *
     * @return void
     */
    public function updateAgent(string $conversationReference, string $agent): void
    {
        $this->entityManager->updateConversationHistoryAgent($conversationReference, $agent);
    }

    /**
     * @param string $conversationReference
     * @param string|null $userSelectedAgent
     *
     * @return void
     */
    public function updateUserSelectedAgent(string $conversationReference, ?string $userSelectedAgent): void
    {
        $this->entityManager->updateConversationHistoryUserSelectedAgent($conversationReference, $userSelectedAgent);
    }

    public function findAgentByConversationReference(string $conversationReference): ?string
    {
        return $this->repository->findAgentByConversationReference($conversationReference);
    }

    public function findUserSelectedAgentByConversationReference(string $conversationReference): ?string
    {
        return $this->repository->findUserSelectedAgentByConversationReference($conversationReference);
    }

    /**
     * @param string $conversationReference
     *
     * @return void
     */
    public function deleteByReference(string $conversationReference): void
    {
        $this->entityManager->deleteConversationHistoryByReference($conversationReference);
    }
}
