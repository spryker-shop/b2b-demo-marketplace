<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace PyzTest\Zed\CustomerGroup\Business\Facade;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\CustomerGroupConditionsTransfer;
use Generated\Shared\Transfer\CustomerGroupTransfer;
use PyzTest\Zed\CustomerGroup\CustomerGroupBusinessTester;
use Spryker\Zed\CustomerGroup\CustomerGroupDependencyProvider;
use SprykerFeature\Zed\CustomerExperienceManagement\Communication\Plugin\CustomerGroup\CompanyBusinessUnitCustomerGroupPostSavePlugin;
use SprykerFeature\Zed\CustomerExperienceManagement\Communication\Plugin\CustomerGroup\CompanyBusinessUnitCustomerGroupQueryExpanderPlugin;
use SprykerFeature\Zed\CustomerExperienceManagement\Communication\Plugin\CustomerGroup\CompanyCustomerGroupPostSavePlugin;
use SprykerFeature\Zed\CustomerExperienceManagement\Communication\Plugin\CustomerGroup\CompanyCustomerGroupQueryExpanderPlugin;

/**
 * Runs `CustomerGroupFacade::getCustomerGroupCollection()` with both customer group query expander plugins, so that the
 * conditions of the customer group module and of the plugins are applied together. The post-save plugins create the
 * company and business unit memberships of the fixtures.
 *
 * Auto-generated group annotations
 *
 * @group PyzTest
 * @group Zed
 * @group CustomerGroup
 * @group Business
 * @group Facade
 * @group GetCustomerGroupCollectionWithAssignmentConditionsTest
 * Add your own group annotations below this line
 */
class GetCustomerGroupCollectionWithAssignmentConditionsTest extends Unit
{
    protected const int ID_NOT_EXISTING = 2147483647;

    protected const string UUID_NOT_EXISTING = '00000000-0000-5000-8000-000000000000';

    protected CustomerGroupBusinessTester $tester;

    protected function _before(): void
    {
        parent::_before();

        $this->tester->setDependency(CustomerGroupDependencyProvider::PLUGINS_CUSTOMER_GROUP_POST_SAVE, [
            new CompanyCustomerGroupPostSavePlugin(),
            new CompanyBusinessUnitCustomerGroupPostSavePlugin(),
        ]);
        $this->tester->setDependency(CustomerGroupDependencyProvider::PLUGINS_CUSTOMER_GROUP_QUERY_EXPANDER, [
            new CompanyCustomerGroupQueryExpanderPlugin(),
            new CompanyBusinessUnitCustomerGroupQueryExpanderPlugin(),
        ]);
    }

    public function testCompanyIdsNarrowToTheGroupsOfTheCompany(): void
    {
        // Arrange
        $companyTransfer = $this->tester->haveCompany();
        $customerGroupTransfer = $this->tester->haveCustomerGroupAssignedTo(companyTransfers: [$companyTransfer]);
        $this->tester->haveCustomerGroupAssignedTo(companyTransfers: [$this->tester->haveCompany()]);

        // Act
        $customerGroupIds = $this->tester->getCustomerGroupIds(
            (new CustomerGroupConditionsTransfer())->addIdCompany($companyTransfer->getIdCompanyOrFail()),
        );

        // Assert
        $this->assertSame([$customerGroupTransfer->getIdCustomerGroupOrFail()], $customerGroupIds);
    }

    public function testCompanyUuidsNarrowToTheGroupsOfTheCompany(): void
    {
        // Arrange
        $companyTransfer = $this->tester->haveCompany();
        $customerGroupTransfer = $this->tester->haveCustomerGroupAssignedTo(companyTransfers: [$companyTransfer]);
        $this->tester->haveCustomerGroupAssignedTo(companyTransfers: [$this->tester->haveCompany()]);

        // Act
        $customerGroupIds = $this->tester->getCustomerGroupIds(
            (new CustomerGroupConditionsTransfer())->addCompanyUuid($companyTransfer->getUuidOrFail()),
        );

        // Assert
        $this->assertSame([$customerGroupTransfer->getIdCustomerGroupOrFail()], $customerGroupIds);
    }

    public function testCompanyConditionDoesNotReturnTheGroupsOfItsBusinessUnits(): void
    {
        // Arrange
        $companyTransfer = $this->tester->haveCompany();
        $this->tester->haveCustomerGroupAssignedTo(
            companyBusinessUnitTransfers: [$this->tester->haveCompanyBusinessUnitOfCompany($companyTransfer)],
        );

        // Act
        $customerGroupIds = $this->tester->getCustomerGroupIds(
            (new CustomerGroupConditionsTransfer())->addIdCompany($companyTransfer->getIdCompanyOrFail()),
        );

        // Assert
        $this->assertSame([], $customerGroupIds);
    }

    public function testBusinessUnitIdsNarrowToTheGroupsOfTheBusinessUnit(): void
    {
        // Arrange
        $companyBusinessUnitTransfer = $this->tester->haveCompanyBusinessUnitOfCompany($this->tester->haveCompany());
        $customerGroupTransfer = $this->tester->haveCustomerGroupAssignedTo(companyBusinessUnitTransfers: [$companyBusinessUnitTransfer]);
        $this->tester->haveCustomerGroupAssignedTo(
            companyBusinessUnitTransfers: [$this->tester->haveCompanyBusinessUnitOfCompany($this->tester->haveCompany())],
        );

        // Act
        $customerGroupIds = $this->tester->getCustomerGroupIds(
            (new CustomerGroupConditionsTransfer())->addIdCompanyBusinessUnit($companyBusinessUnitTransfer->getIdCompanyBusinessUnitOrFail()),
        );

        // Assert
        $this->assertSame([$customerGroupTransfer->getIdCustomerGroupOrFail()], $customerGroupIds);
    }

    public function testBusinessUnitUuidsNarrowToTheGroupsOfTheBusinessUnit(): void
    {
        // Arrange
        $companyBusinessUnitTransfer = $this->tester->haveCompanyBusinessUnitOfCompany($this->tester->haveCompany());
        $customerGroupTransfer = $this->tester->haveCustomerGroupAssignedTo(companyBusinessUnitTransfers: [$companyBusinessUnitTransfer]);
        $this->tester->haveCustomerGroupAssignedTo(
            companyBusinessUnitTransfers: [$this->tester->haveCompanyBusinessUnitOfCompany($this->tester->haveCompany())],
        );

        // Act
        $customerGroupIds = $this->tester->getCustomerGroupIds(
            (new CustomerGroupConditionsTransfer())->addCompanyBusinessUnitUuid($companyBusinessUnitTransfer->getUuidOrFail()),
        );

        // Assert
        $this->assertSame([$customerGroupTransfer->getIdCustomerGroupOrFail()], $customerGroupIds);
    }

    public function testCompanyAndBusinessUnitConditionsAreCombinedWithAnd(): void
    {
        // Arrange
        $companyTransfer = $this->tester->haveCompany();
        $companyBusinessUnitTransfer = $this->tester->haveCompanyBusinessUnitOfCompany($companyTransfer);
        $customerGroupOfBothTransfer = $this->tester->haveCustomerGroupAssignedTo(
            companyTransfers: [$companyTransfer],
            companyBusinessUnitTransfers: [$companyBusinessUnitTransfer],
        );
        $this->tester->haveCustomerGroupAssignedTo(companyTransfers: [$companyTransfer]);
        $this->tester->haveCustomerGroupAssignedTo(companyBusinessUnitTransfers: [$companyBusinessUnitTransfer]);

        // Act
        $customerGroupIds = $this->tester->getCustomerGroupIds(
            (new CustomerGroupConditionsTransfer())
                ->addCompanyUuid($companyTransfer->getUuidOrFail())
                ->addCompanyBusinessUnitUuid($companyBusinessUnitTransfer->getUuidOrFail()),
        );

        // Assert
        $this->assertSame([$customerGroupOfBothTransfer->getIdCustomerGroupOrFail()], $customerGroupIds);
    }

    public function testCustomerReferenceAndCompanyConditionsAreCombinedWithAnd(): void
    {
        // Arrange
        $companyTransfer = $this->tester->haveCompany();
        $customerTransfer = $this->tester->haveCustomer();
        $customerGroupOfBothTransfer = $this->tester->haveCustomerGroupAssignedTo(
            customerTransfers: [$customerTransfer],
            companyTransfers: [$companyTransfer],
        );
        $this->tester->haveCustomerGroupAssignedTo(customerTransfers: [$customerTransfer]);
        $this->tester->haveCustomerGroupAssignedTo(companyTransfers: [$companyTransfer]);

        // Act
        $customerGroupIds = $this->tester->getCustomerGroupIds(
            (new CustomerGroupConditionsTransfer())
                ->addCustomerReference($customerTransfer->getCustomerReferenceOrFail())
                ->addCompanyUuid($companyTransfer->getUuidOrFail()),
        );

        // Assert
        $this->assertSame([$customerGroupOfBothTransfer->getIdCustomerGroupOrFail()], $customerGroupIds);
    }

    public function testSeveralCompaniesReturnASharedGroupOnceAndCountItOnce(): void
    {
        // Arrange
        $firstCompanyTransfer = $this->tester->haveCompany();
        $secondCompanyTransfer = $this->tester->haveCompany();
        $sharedCustomerGroupTransfer = $this->tester->haveCustomerGroupAssignedTo(
            companyTransfers: [$firstCompanyTransfer, $secondCompanyTransfer],
        );

        // Act
        $customerGroupCollectionTransfer = $this->tester->getCustomerGroupCollection(
            (new CustomerGroupConditionsTransfer())
                ->addCompanyUuid($firstCompanyTransfer->getUuidOrFail())
                ->addCompanyUuid($secondCompanyTransfer->getUuidOrFail()),
        );

        // Assert
        $this->assertCount(1, $customerGroupCollectionTransfer->getGroups());
        $this->assertSame(
            $sharedCustomerGroupTransfer->getIdCustomerGroupOrFail(),
            $customerGroupCollectionTransfer->getGroups()->offsetGet(0)->getIdCustomerGroupOrFail(),
        );
        $this->assertSame(1, $customerGroupCollectionTransfer->getPaginationOrFail()->getNbResults());
    }

    public function testPagesThroughTheGroupsOfSeveralCompaniesWithoutRepeatingASharedGroup(): void
    {
        // Arrange
        $firstCompanyTransfer = $this->tester->haveCompany();
        $secondCompanyTransfer = $this->tester->haveCompany();
        $namePrefix = uniqid('paged-', false);
        $expectedCustomerGroupIds = [
            $this->tester->haveCustomerGroupAssignedTo(
                companyTransfers: [$firstCompanyTransfer, $secondCompanyTransfer],
                seed: [CustomerGroupTransfer::NAME => sprintf('%s-a', $namePrefix)],
            )->getIdCustomerGroupOrFail(),
            $this->tester->haveCustomerGroupAssignedTo(
                companyTransfers: [$firstCompanyTransfer],
                seed: [CustomerGroupTransfer::NAME => sprintf('%s-b', $namePrefix)],
            )->getIdCustomerGroupOrFail(),
            $this->tester->haveCustomerGroupAssignedTo(
                companyTransfers: [$secondCompanyTransfer],
                seed: [CustomerGroupTransfer::NAME => sprintf('%s-c', $namePrefix)],
            )->getIdCustomerGroupOrFail(),
        ];
        $this->tester->haveCustomerGroupAssignedTo(companyTransfers: [$this->tester->haveCompany()]);
        $customerGroupConditionsTransfer = (new CustomerGroupConditionsTransfer())
            ->addCompanyUuid($firstCompanyTransfer->getUuidOrFail())
            ->addCompanyUuid($secondCompanyTransfer->getUuidOrFail());

        // Act
        $customerGroupCollectionTransfers = [];

        foreach ([1, 2, 3] as $page) {
            $customerGroupCollectionTransfers[] = $this->tester->getCustomerGroupPageSortedByName($customerGroupConditionsTransfer, $page, 1);
        }

        // Assert
        $pagedCustomerGroupIds = [];

        foreach ($customerGroupCollectionTransfers as $customerGroupCollectionTransfer) {
            $this->assertCount(1, $customerGroupCollectionTransfer->getGroups());
            $this->assertSame(3, $customerGroupCollectionTransfer->getPaginationOrFail()->getNbResults());
            $pagedCustomerGroupIds[] = $customerGroupCollectionTransfer->getGroups()->offsetGet(0)->getIdCustomerGroupOrFail();
        }

        $this->assertSame($expectedCustomerGroupIds, $pagedCustomerGroupIds);
    }

    public function testCustomerIdsNarrowToTheGroupsOfTheCustomer(): void
    {
        // Arrange
        $customerTransfer = $this->tester->haveCustomer();
        $customerGroupTransfer = $this->tester->haveCustomerGroupAssignedTo(customerTransfers: [$customerTransfer]);
        $this->tester->haveCustomerGroupAssignedTo(customerTransfers: [$this->tester->haveCustomer()]);

        // Act
        $customerGroupIds = $this->tester->getCustomerGroupIds(
            (new CustomerGroupConditionsTransfer())->addIdCustomer($customerTransfer->getIdCustomerOrFail()),
        );

        // Assert
        $this->assertSame([$customerGroupTransfer->getIdCustomerGroupOrFail()], $customerGroupIds);
    }

    public function testCustomerReferencesNarrowToTheGroupsOfTheCustomer(): void
    {
        // Arrange
        $customerTransfer = $this->tester->haveCustomer();
        $customerGroupTransfer = $this->tester->haveCustomerGroupAssignedTo(customerTransfers: [$customerTransfer]);
        $this->tester->haveCustomerGroupAssignedTo(customerTransfers: [$this->tester->haveCustomer()]);

        // Act
        $customerGroupIds = $this->tester->getCustomerGroupIds(
            (new CustomerGroupConditionsTransfer())->addCustomerReference($customerTransfer->getCustomerReferenceOrFail()),
        );

        // Assert
        $this->assertSame([$customerGroupTransfer->getIdCustomerGroupOrFail()], $customerGroupIds);
    }

    public function testSeveralCustomersReturnASharedGroupOnceAndCountItOnce(): void
    {
        // Arrange
        $firstCustomerTransfer = $this->tester->haveCustomer();
        $secondCustomerTransfer = $this->tester->haveCustomer();
        $sharedCustomerGroupTransfer = $this->tester->haveCustomerGroupAssignedTo(
            customerTransfers: [$firstCustomerTransfer, $secondCustomerTransfer],
        );

        // Act
        $customerGroupCollectionTransfer = $this->tester->getCustomerGroupCollection(
            (new CustomerGroupConditionsTransfer())
                ->addCustomerReference($firstCustomerTransfer->getCustomerReferenceOrFail())
                ->addCustomerReference($secondCustomerTransfer->getCustomerReferenceOrFail()),
        );

        // Assert
        $this->assertCount(1, $customerGroupCollectionTransfer->getGroups());
        $this->assertSame(
            $sharedCustomerGroupTransfer->getIdCustomerGroupOrFail(),
            $customerGroupCollectionTransfer->getGroups()->offsetGet(0)->getIdCustomerGroupOrFail(),
        );
        $this->assertSame(1, $customerGroupCollectionTransfer->getPaginationOrFail()->getNbResults());
    }

    public function testUnknownIdsAndUuidsReturnNoGroup(): void
    {
        // Arrange
        $this->tester->haveCustomerGroupAssignedTo(companyTransfers: [$this->tester->haveCompany()]);

        // Act & Assert
        $this->assertSame([], $this->tester->getCustomerGroupIds((new CustomerGroupConditionsTransfer())->addIdCompany(static::ID_NOT_EXISTING)));
        $this->assertSame([], $this->tester->getCustomerGroupIds((new CustomerGroupConditionsTransfer())->addCompanyUuid(static::UUID_NOT_EXISTING)));
        $this->assertSame(
            [],
            $this->tester->getCustomerGroupIds((new CustomerGroupConditionsTransfer())->addIdCompanyBusinessUnit(static::ID_NOT_EXISTING)),
        );
        $this->assertSame(
            [],
            $this->tester->getCustomerGroupIds((new CustomerGroupConditionsTransfer())->addCompanyBusinessUnitUuid(static::UUID_NOT_EXISTING)),
        );
    }
}
