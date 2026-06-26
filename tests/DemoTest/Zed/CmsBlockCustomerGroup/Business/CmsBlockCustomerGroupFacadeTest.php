<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace DemoTest\Zed\CmsBlockCustomerGroup\Business;

use Codeception\Test\Unit;
use Demo\Zed\CmsBlockCustomerGroup\Business\CmsBlockCustomerGroupFacadeInterface;
use DemoTest\Zed\CmsBlockCustomerGroup\CmsBlockCustomerGroupBusinessTester;
use Generated\Shared\Transfer\CmsBlockTransfer;
use Generated\Shared\Transfer\CmsBlockValidationRequestTransfer;
use Generated\Shared\Transfer\CustomerGroupCollectionTransfer;
use Generated\Shared\Transfer\CustomerGroupTransfer;
use Generated\Shared\Transfer\CustomerTransfer;
use Orm\Zed\CmsBlockCustomerGroup\Persistence\PyzCmsBlockCustomerGroupQuery;

/**
 * @group DemoTest
 * @group Zed
 * @group CmsBlockCustomerGroup
 * @group Business
 * @group CmsBlockCustomerGroupFacadeTest
 */
class CmsBlockCustomerGroupFacadeTest extends Unit
{
    protected CmsBlockCustomerGroupBusinessTester $tester;

    public function testValidateAccessGivenBlockWithoutAssignedGroupsWhenAnyCustomerThenAccessGranted(): void
    {
        // Arrange
        $cmsBlockTransfer = $this->tester->haveCmsBlock();
        $customerTransfer = $this->tester->haveCustomer();
        $requestTransfer = $this->createValidationRequest($cmsBlockTransfer, $customerTransfer);

        // Act
        $responseTransfer = $this->getFacade()->validateAccessToCmsBlock($requestTransfer);

        // Assert
        $this->assertTrue($responseTransfer->getIsValid());
    }

    public function testValidateAccessGivenCustomerInAssignedGroupThenAccessGranted(): void
    {
        // Arrange
        $cmsBlockTransfer = $this->tester->haveCmsBlock();
        $customerTransfer = $this->tester->haveCustomer();
        $customerGroupTransfer = $this->tester->haveCustomerGroup();
        $this->tester->assignCustomerToGroup($customerTransfer->getIdCustomer(), $customerGroupTransfer->getIdCustomerGroup());
        $this->assignGroupsToCmsBlock($cmsBlockTransfer, [$customerGroupTransfer->getIdCustomerGroup()]);

        // Act
        $responseTransfer = $this->getFacade()->validateAccessToCmsBlock(
            $this->createValidationRequest($cmsBlockTransfer, $customerTransfer),
        );

        // Assert
        $this->assertTrue($responseTransfer->getIsValid());
    }

    public function testValidateAccessGivenCustomerNotInAssignedGroupThenAccessDenied(): void
    {
        // Arrange
        $cmsBlockTransfer = $this->tester->haveCmsBlock();
        $customerTransfer = $this->tester->haveCustomer();
        $customerGroupTransfer = $this->tester->haveCustomerGroup();
        $this->assignGroupsToCmsBlock($cmsBlockTransfer, [$customerGroupTransfer->getIdCustomerGroup()]);

        // Act
        $responseTransfer = $this->getFacade()->validateAccessToCmsBlock(
            $this->createValidationRequest($cmsBlockTransfer, $customerTransfer),
        );

        // Assert
        $this->assertFalse($responseTransfer->getIsValid());
    }

    public function testValidateAccessGivenNoCustomerThenAccessDenied(): void
    {
        // Arrange
        $cmsBlockTransfer = $this->tester->haveCmsBlock();
        $requestTransfer = (new CmsBlockValidationRequestTransfer())
            ->setCmsBlock($cmsBlockTransfer)
            ->setCustomer(null);

        // Act
        $responseTransfer = $this->getFacade()->validateAccessToCmsBlock($requestTransfer);

        // Assert
        $this->assertFalse($responseTransfer->getIsValid());
    }

    public function testSaveCmsBlockCustomerGroupsDiffsAddsAndRemoves(): void
    {
        // Arrange
        $cmsBlockTransfer = $this->tester->haveCmsBlock();
        $idCustomerGroupA = $this->tester->haveCustomerGroup()->getIdCustomerGroup();
        $idCustomerGroupB = $this->tester->haveCustomerGroup()->getIdCustomerGroup();
        $idCustomerGroupC = $this->tester->haveCustomerGroup()->getIdCustomerGroup();
        $this->assignGroupsToCmsBlock($cmsBlockTransfer, [$idCustomerGroupA, $idCustomerGroupB]);

        // Act
        $this->assignGroupsToCmsBlock($cmsBlockTransfer, [$idCustomerGroupB, $idCustomerGroupC]);

        // Assert
        $this->assertSame(
            $this->sortedUnique([$idCustomerGroupB, $idCustomerGroupC]),
            $this->getAssignedCustomerGroupIds($cmsBlockTransfer),
        );
        $this->assertSame(2, $this->countCmsBlockCustomerGroupRows($cmsBlockTransfer->getIdCmsBlock()));
    }

    public function testGetCmsBlockCustomerGroupsReturnsAssignedCollection(): void
    {
        // Arrange
        $cmsBlockTransfer = $this->tester->haveCmsBlock();
        $idCustomerGroupA = $this->tester->haveCustomerGroup()->getIdCustomerGroup();
        $this->assignGroupsToCmsBlock($cmsBlockTransfer, [$idCustomerGroupA]);

        // Act
        $customerGroupCollectionTransfer = $this->getFacade()->getCmsBlockCustomerGroups($cmsBlockTransfer);

        // Assert
        $this->assertCount(1, $customerGroupCollectionTransfer->getGroups());
        $this->assertSame($idCustomerGroupA, $customerGroupCollectionTransfer->getGroups()[0]->getIdCustomerGroup());
    }

    /**
     * @param \Generated\Shared\Transfer\CmsBlockTransfer $cmsBlockTransfer
     * @param array<int> $customerGroupIds
     *
     * @return void
     */
    protected function assignGroupsToCmsBlock(CmsBlockTransfer $cmsBlockTransfer, array $customerGroupIds): void
    {
        $customerGroupCollectionTransfer = new CustomerGroupCollectionTransfer();
        foreach ($customerGroupIds as $idCustomerGroup) {
            $customerGroupCollectionTransfer->addGroup(
                (new CustomerGroupTransfer())->setIdCustomerGroup($idCustomerGroup),
            );
        }

        $cmsBlockTransfer->setCustomerGroups($customerGroupCollectionTransfer);
        $this->getFacade()->saveCmsBlockCustomerGroups($cmsBlockTransfer);
    }

    /**
     * @param \Generated\Shared\Transfer\CmsBlockTransfer $cmsBlockTransfer
     *
     * @return array<int>
     */
    protected function getAssignedCustomerGroupIds(CmsBlockTransfer $cmsBlockTransfer): array
    {
        $customerGroupIds = [];
        foreach ($this->getFacade()->getCmsBlockCustomerGroups($cmsBlockTransfer)->getGroups() as $customerGroupTransfer) {
            $customerGroupIds[] = $customerGroupTransfer->getIdCustomerGroup();
        }

        return $this->sortedUnique($customerGroupIds);
    }

    protected function countCmsBlockCustomerGroupRows(int $idCmsBlock): int
    {
        return PyzCmsBlockCustomerGroupQuery::create()
            ->filterByFkCmsBlock($idCmsBlock)
            ->count();
    }

    protected function createValidationRequest(
        CmsBlockTransfer $cmsBlockTransfer,
        CustomerTransfer $customerTransfer,
    ): CmsBlockValidationRequestTransfer {
        return (new CmsBlockValidationRequestTransfer())
            ->setCmsBlock($cmsBlockTransfer)
            ->setCustomer($customerTransfer);
    }

    /**
     * @param array<int> $values
     *
     * @return array<int>
     */
    protected function sortedUnique(array $values): array
    {
        $values = array_values(array_unique($values));
        sort($values);

        return $values;
    }

    protected function getFacade(): CmsBlockCustomerGroupFacadeInterface
    {
        return $this->tester->getLocator()->cmsBlockCustomerGroup()->facade();
    }
}
