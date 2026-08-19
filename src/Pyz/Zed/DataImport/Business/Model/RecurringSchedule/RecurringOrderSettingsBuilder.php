<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\DataImport\Business\Model\RecurringSchedule;

use DateTimeImmutable;
use Generated\Shared\Transfer\RecurringOrderSettingsTransfer;
use Pyz\Zed\DataImport\Business\Exception\InvalidDataException;
use Spryker\Zed\DataImport\Business\Model\DataSet\DataSetInterface;

class RecurringOrderSettingsBuilder
{
    protected const string DATE_FORMAT = 'Y-m-d';

    protected const string TIMESTAMP_FORMAT = 'U';

    protected const string DATE_EXPRESSION_TODAY = 'today';

    public function buildRecurringOrderSettings(DataSetInterface $dataSet): RecurringOrderSettingsTransfer
    {
        $cadenceType = trim((string)$dataSet[RecurringScheduleDataSetInterface::COLUMN_CADENCE_TYPE]);

        if ($cadenceType === '') {
            throw new InvalidDataException(sprintf(
                'Column "%s" must not be empty for a recurring schedule.',
                RecurringScheduleDataSetInterface::COLUMN_CADENCE_TYPE,
            ));
        }

        $cadenceValue = trim((string)$dataSet[RecurringScheduleDataSetInterface::COLUMN_CADENCE_VALUE]);

        return (new RecurringOrderSettingsTransfer())
            ->setCadenceType($cadenceType)
            ->setCadenceValue($cadenceValue === '' ? null : (int)$cadenceValue)
            ->setScheduleName(trim((string)$dataSet[RecurringScheduleDataSetInterface::COLUMN_SCHEDULE_NAME]))
            ->setStartDate($this->resolveStartDate($dataSet));
    }

    protected function resolveStartDate(DataSetInterface $dataSet): string
    {
        $startDateExpression = trim((string)$dataSet[RecurringScheduleDataSetInterface::COLUMN_START_DATE]);

        if ($startDateExpression === '') {
            $startDateExpression = static::DATE_EXPRESSION_TODAY;
        }

        return $this->resolveRelativeDate($startDateExpression)->format(static::DATE_FORMAT);
    }

    public function resolveRelativeDate(string $dateExpression): DateTimeImmutable
    {
        $today = new DateTimeImmutable(static::DATE_EXPRESSION_TODAY);
        $timestamp = strtotime($dateExpression, (int)$today->format(static::TIMESTAMP_FORMAT));

        if ($timestamp === false) {
            throw new InvalidDataException(sprintf('Date expression "%s" could not be resolved.', $dateExpression));
        }

        return $today->setTimestamp($timestamp);
    }
}
