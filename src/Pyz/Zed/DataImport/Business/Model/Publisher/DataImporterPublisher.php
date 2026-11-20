<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\DataImport\Business\Model\Publisher;

use Propel\Runtime\Propel;
use Spryker\Shared\Kernel\Transfer\TransferInterface;
use Spryker\Zed\DataImport\Business\Model\Publisher\DataImporterPublisher as SprykerDataImporterPublisher;

/**
 * Does not send import events while the import transaction is open.
 *
 * The core publisher sends events every PUBLISHER_TRIGGER_CHUNK_SIZE events, also in the middle of an open
 * transaction. A queue worker that reads the rows before the commit finds nothing, acks the message and the
 * entity is never published (seen as missing URL storage after product abstract imports). Events are kept
 * until the transaction is committed; DataSetStepBrokerTransactionAware sends them after each commit and
 * DataImporterDataSetWriterAware commits before the final flush.
 */
class DataImporterPublisher extends SprykerDataImporterPublisher
{
    /**
     * @param string $eventName
     * @param int $entityId
     * @param \Spryker\Shared\Kernel\Transfer\TransferInterface|null $eventEntityTransfer
     *
     * @return void
     */
    public static function addEvent($eventName, $entityId, ?TransferInterface $eventEntityTransfer = null): void
    {
        if (isset(static::$triggeredEventIds[$eventName][$entityId])) {
            return;
        }

        static::$importedEntityEvents[$eventName][$entityId] = $eventEntityTransfer ?? true;

        if (!static::isInTransaction()) {
            static::triggerEventsWhenChunkIsFull();
        }
    }

    /**
     * @return void
     */
    public static function triggerEventsWhenChunkIsFull(): void
    {
        if (count(static::$importedEntityEvents, COUNT_RECURSIVE) >= static::getChunkSize()) {
            static::triggerEvents();
        }
    }

    /**
     * @return bool
     */
    protected static function isInTransaction(): bool
    {
        return Propel::getConnection()->inTransaction();
    }
}
