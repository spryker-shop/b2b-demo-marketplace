<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\BackofficeAssistant\Persistence\Propel\Mapper;

use Generated\Shared\Transfer\BackofficeAssistantConversationHistoryTransfer;
use Orm\Zed\BackofficeAssistant\Persistence\DemoBackofficeAssistantConversation;
use Propel\Runtime\Collection\Collection;

class BackofficeAssistantMapper
{
    /**
     * @param \Generated\Shared\Transfer\BackofficeAssistantConversationHistoryTransfer $transfer
     * @param \Orm\Zed\BackofficeAssistant\Persistence\DemoBackofficeAssistantConversation $entity
     *
     * @return \Orm\Zed\BackofficeAssistant\Persistence\DemoBackofficeAssistantConversation
     */
    public function mapTransferToEntity(
        BackofficeAssistantConversationHistoryTransfer $transfer,
        DemoBackofficeAssistantConversation $entity,
    ): DemoBackofficeAssistantConversation {
        $entity->fromArray($transfer->modifiedToArray());

        return $entity;
    }

    /**
     * @param \Orm\Zed\BackofficeAssistant\Persistence\DemoBackofficeAssistantConversation $entity
     * @param \Generated\Shared\Transfer\BackofficeAssistantConversationHistoryTransfer $transfer
     *
     * @return \Generated\Shared\Transfer\BackofficeAssistantConversationHistoryTransfer
     */
    public function mapEntityToTransfer(
        DemoBackofficeAssistantConversation $entity,
        BackofficeAssistantConversationHistoryTransfer $transfer,
    ): BackofficeAssistantConversationHistoryTransfer {
        return $transfer->fromArray($entity->toArray(), true);
    }

    /**
     * @param \Propel\Runtime\Collection\Collection<\Orm\Zed\BackofficeAssistant\Persistence\DemoBackofficeAssistantConversation> $entityCollection
     * @param array<\Generated\Shared\Transfer\BackofficeAssistantConversationHistoryTransfer> $transferCollection
     *
     * @return array<\Generated\Shared\Transfer\BackofficeAssistantConversationHistoryTransfer>
     */
    public function mapEntityCollectionToTransferCollection(
        Collection $entityCollection,
        array $transferCollection,
    ): array {
        foreach ($entityCollection as $entity) {
            $transferCollection[] = $this->mapEntityToTransfer(
                $entity,
                new BackofficeAssistantConversationHistoryTransfer(),
            );
        }

        return $transferCollection;
    }
}
