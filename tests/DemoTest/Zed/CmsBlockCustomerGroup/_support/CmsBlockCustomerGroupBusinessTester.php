<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace DemoTest\Zed\CmsBlockCustomerGroup;

use Codeception\Actor;
use Orm\Zed\CustomerGroup\Persistence\SpyCustomerGroupToCustomer;

/**
 * Inherited Methods
 *
 * @method void wantToTest($text)
 * @method void wantTo($text)
 * @method void execute($callable)
 * @method void expectTo($prediction)
 * @method void expect($prediction)
 * @method void amGoingTo($argumentation)
 * @method void am($role)
 * @method void lookForwardTo($achieveValue)
 * @method void comment($description)
 * @method \Codeception\Lib\Friend haveFriend($name, $actorClass = NULL)
 * @method \Generated\Shared\Transfer\CmsBlockTransfer haveCmsBlock(array $seedData = [])
 * @method \Generated\Shared\Transfer\CustomerTransfer haveCustomer(array $override = [])
 * @method \Generated\Shared\Transfer\CustomerGroupTransfer haveCustomerGroup(array $seed = [])
 *
 * @SuppressWarnings(\DemoTest\Zed\CmsBlockCustomerGroup\PHPMD)
 */
class CmsBlockCustomerGroupBusinessTester extends Actor
{
    use _generated\CmsBlockCustomerGroupBusinessTesterActions;

    public function assignCustomerToGroup(int $idCustomer, int $idCustomerGroup): void
    {
        $customerGroupToCustomerEntity = new SpyCustomerGroupToCustomer();
        $customerGroupToCustomerEntity->setFkCustomer($idCustomer);
        $customerGroupToCustomerEntity->setFkCustomerGroup($idCustomerGroup);
        $customerGroupToCustomerEntity->save();
    }
}
