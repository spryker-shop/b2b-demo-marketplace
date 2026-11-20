<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\DataImport\Business\Model;

use Propel\Runtime\Propel;
use Spryker\Zed\DataImport\Business\Model\DataImporterDataSetWriterAware as SprykerDataImporterDataSetWriterAware;

/**
 * Commits the last open import transaction before the writer flush sends the remaining events.
 *
 * In the core the last partial bulk is committed in DataSetStepBrokerTransactionAware::__destruct(), after the
 * writer flush has already sent its events, so a fast queue worker can read the rows before they are committed.
 *
 * @see \Pyz\Zed\DataImport\Business\Model\Publisher\DataImporterPublisher
 */
class DataImporterDataSetWriterAware extends SprykerDataImporterDataSetWriterAware
{
    /**
     * @return void
     */
    protected function flushDataSetWriter(): void
    {
        $connection = Propel::getConnection();

        if ($connection->inTransaction()) {
            $connection->commit();
        }

        parent::flushDataSetWriter();
    }
}
