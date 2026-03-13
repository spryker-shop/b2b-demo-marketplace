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
}
