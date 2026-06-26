<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace DemoTest\Zed\CustomerGroup\Business;

use Codeception\Test\Unit;
use Demo\Zed\CustomerGroup\Business\CustomerGroupFacadeInterface;
use DemoTest\Zed\CustomerGroup\CustomerGroupBusinessTester;
use Generated\Shared\Transfer\CustomerGroupTransfer;

/**
 * @group DemoTest
 * @group Zed
 * @group CustomerGroup
 * @group Business
 * @group CustomerGroupFacadeTest
 */
class CustomerGroupFacadeTest extends Unit
{
    protected CustomerGroupBusinessTester $tester;

    public function testGetCustomerGroupCollectionMapsIdAndNameFromDatabase(): void
    {
        // Arrange
        $customerGroupTransfer = $this->tester->haveCustomerGroup();

        // Act
        $customerGroupCollectionTransfer = $this->getFacade()->getCustomerGroupCollection();

        // Assert
        $matchingGroupTransfer = $this->findGroupById(
            $customerGroupCollectionTransfer->getGroups()->getArrayCopy(),
            $customerGroupTransfer->getIdCustomerGroup(),
        );
        $this->assertNotNull($matchingGroupTransfer);
        $this->assertSame($customerGroupTransfer->getName(), $matchingGroupTransfer->getName());
    }

    /**
     * @param array<\Generated\Shared\Transfer\CustomerGroupTransfer> $customerGroupTransfers
     * @param int $idCustomerGroup
     *
     * @return \Generated\Shared\Transfer\CustomerGroupTransfer|null
     */
    protected function findGroupById(array $customerGroupTransfers, int $idCustomerGroup): ?CustomerGroupTransfer
    {
        foreach ($customerGroupTransfers as $customerGroupTransfer) {
            if ($customerGroupTransfer->getIdCustomerGroup() === $idCustomerGroup) {
                return $customerGroupTransfer;
            }
        }

        return null;
    }

    protected function getFacade(): CustomerGroupFacadeInterface
    {
        return $this->tester->getLocator()->customerGroup()->facade();
    }
}
