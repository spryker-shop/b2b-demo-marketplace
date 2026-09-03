<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\DataImport\Business\Model\SalesOrder;

interface SalesOrderDataSetInterface
{
    /**
     * @var string
     */
    public const COLUMN_ORDER_REFERENCE = 'order_reference';

    /**
     * @var string
     */
    public const COLUMN_CUSTOMER_REFERENCE = 'customer_reference';

    /**
     * @var string
     */
    public const COLUMN_COMPANY_USER_KEY = 'company_user_key';

    /**
     * @var string
     */
    public const COLUMN_STORE = 'store';

    /**
     * @var string
     */
    public const COLUMN_CURRENCY = 'currency';

    /**
     * @var string
     */
    public const COLUMN_PAYMENT_METHOD_KEY = 'payment_method_key';

    /**
     * @var string
     */
    public const COLUMN_SHIPMENT_METHOD_KEY = 'shipment_method_key';

    /**
     * @var string
     */
    public const COLUMN_COST_CENTER_KEY = 'cost_center_key';

    /**
     * @var string
     */
    public const COLUMN_BUDGET_NAME = 'budget_name';

    /**
     * @var string
     */
    public const COLUMN_ITEMS = 'items';

    /**
     * @var string
     */
    public const COLUMN_OMS_EVENTS = 'oms_events';
}
