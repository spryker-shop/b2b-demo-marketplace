<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\BackofficeAssistant\Persistence;

use Spryker\Zed\Kernel\Persistence\AbstractRepository;

/**
 * @method \Demo\Zed\BackofficeAssistant\Persistence\BackofficeAssistantPersistenceFactory getFactory()
 */
class BackofficeAssistantRepository extends AbstractRepository implements BackofficeAssistantRepositoryInterface
{
    public function getConversationHistoriesByFkUser(int $idUser): array
    {
        $entities = $this->getFactory()
            ->createBackofficeAssistantConversationQuery()
            ->filterByFkUser($idUser)
            ->orderByIdBackofficeAssistantConversation('DESC')
            ->find();

        return $this->getFactory()
            ->createMapper()
            ->mapEntityCollectionToTransferCollection($entities, []);
    }

    public function hasConversationHistoryForUser(int $idUser, string $conversationReference): bool
    {
        return $this->getFactory()
            ->createBackofficeAssistantConversationQuery()
            ->filterByFkUser($idUser)
            ->filterByConversationReference($conversationReference)
            ->exists();
    }
}
