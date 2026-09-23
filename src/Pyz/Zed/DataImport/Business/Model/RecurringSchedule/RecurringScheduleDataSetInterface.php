<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\DataImport\Business\Model\RecurringSchedule;

/**
 * Columns specific to the recurring-schedule import. The columns describing the source order itself
 * (customer, store, currency, payment, shipment, items) reuse the sales-order column names so that
 * importer's collaborators can be reused unchanged.
 *
 * @see \Pyz\Zed\DataImport\Business\Model\SalesOrder\SalesOrderDataSetInterface
 */
interface RecurringScheduleDataSetInterface
{
    public const string COLUMN_CADENCE_TYPE = 'cadence_type';

    public const string COLUMN_CADENCE_VALUE = 'cadence_value';

    public const string COLUMN_SCHEDULE_NAME = 'schedule_name';

    public const string COLUMN_START_DATE = 'start_date';

    public const string COLUMN_NEXT_TRIGGER_DATE = 'next_trigger_date';

    public const string COLUMN_TARGET_STATUS = 'target_status';

    public const string COLUMN_FAILURE_SKU = 'failure_sku';
}
