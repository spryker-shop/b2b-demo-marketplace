<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\DataImport\Business\Model\CustomerGroup;

use Orm\Zed\Customer\Persistence\SpyCustomerQuery;
use Orm\Zed\CustomerGroup\Persistence\SpyCustomerGroupQuery;
use Orm\Zed\CustomerGroup\Persistence\SpyCustomerGroupToCustomerQuery;
use Spryker\Zed\DataImport\Business\Model\DataImportStep\DataImportStepInterface;
use Spryker\Zed\DataImport\Business\Model\DataSet\DataSetInterface;

class CustomerGroupToCustomerWriterStep implements DataImportStepInterface
{
    /**
     * @var string
     */
    public const KEY_CUSTOMER_GROUP_NAME = 'customer_group_name';

    /**
     * @var string
     */
    public const KEY_CUSTOMER_REFERENCE = 'customer_reference';

    /**
     * @var array<int> Keys are customer group names, values are customer group ids.
     */
    protected static $idCustomerGroupBuffer = [];

    /**
     * @param \Spryker\Zed\DataImport\Business\Model\DataSet\DataSetInterface $dataSet
     *
     * @return void
     */
    public function execute(DataSetInterface $dataSet): void
    {
        (new SpyCustomerGroupToCustomerQuery())
            ->filterByFkCustomerGroup($this->getIdCustomerGroupByName($dataSet[static::KEY_CUSTOMER_GROUP_NAME]))
            ->filterByFkCustomer($this->getIdCustomerByReference($dataSet[static::KEY_CUSTOMER_REFERENCE]))
            ->findOneOrCreate()
            ->save();
    }

    /**
     * @param string $customerGroupName
     *
     * @return int
     */
    protected function getIdCustomerGroupByName(string $customerGroupName): int
    {
        if (!isset(static::$idCustomerGroupBuffer[$customerGroupName])) {
            static::$idCustomerGroupBuffer[$customerGroupName] =
                SpyCustomerGroupQuery::create()->findOneByName($customerGroupName)->getIdCustomerGroup();
        }

        return static::$idCustomerGroupBuffer[$customerGroupName];
    }

    /**
     * @param string $customerReference
     *
     * @return int
     */
    protected function getIdCustomerByReference(string $customerReference): int
    {
        return SpyCustomerQuery::create()->findOneByCustomerReference($customerReference)->getIdCustomer();
    }
}
