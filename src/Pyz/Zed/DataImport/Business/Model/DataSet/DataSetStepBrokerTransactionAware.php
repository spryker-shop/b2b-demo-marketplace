<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\DataImport\Business\Model\DataSet;

use Pyz\Zed\DataImport\Business\Model\Publisher\DataImporterPublisher;
use Spryker\Zed\DataImport\Business\Model\DataSet\DataSetStepBrokerTransactionAware as SprykerDataSetStepBrokerTransactionAware;

/**
 * Sends the import events that waited for the transaction right after it is committed.
 *
 * @see \Pyz\Zed\DataImport\Business\Model\Publisher\DataImporterPublisher
 */
class DataSetStepBrokerTransactionAware extends SprykerDataSetStepBrokerTransactionAware
{
    /**
     * @return void
     */
    protected function afterDataSetExecution()
    {
        parent::afterDataSetExecution();

        if (!$this->propelConnection->inTransaction()) {
            DataImporterPublisher::triggerEventsWhenChunkIsFull();
        }
    }

    public function __destruct()
    {
        parent::__destruct();

        if (!$this->propelConnection->inTransaction()) {
            DataImporterPublisher::triggerEvents();
        }
    }
}
