<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace PyzTest\Zed\CustomerGroup;

use Codeception\Actor;
use Generated\Shared\Transfer\CompanyBusinessUnitTransfer;
use Generated\Shared\Transfer\CompanyTransfer;
use Generated\Shared\Transfer\CustomerGroupCollectionTransfer;
use Generated\Shared\Transfer\CustomerGroupConditionsTransfer;
use Generated\Shared\Transfer\CustomerGroupCriteriaTransfer;
use Generated\Shared\Transfer\CustomerGroupToCompanyAssignmentTransfer;
use Generated\Shared\Transfer\CustomerGroupToCompanyBusinessUnitAssignmentTransfer;
use Generated\Shared\Transfer\CustomerGroupToCustomerAssignmentTransfer;
use Generated\Shared\Transfer\CustomerGroupTransfer;
use Generated\Shared\Transfer\CustomerTransfer;
use Generated\Shared\Transfer\PaginationTransfer;
use Generated\Shared\Transfer\SortTransfer;

/**
 * Inherited Methods
 *
 * @method void wantTo($text)
 * @method void wantToTest($text)
 * @method void execute($callable)
 * @method void expectTo($prediction)
 * @method void expect($prediction)
 * @method void amGoingTo($argumentation)
 * @method void am($role)
 * @method void lookForMissedSteps()
 * @method void comment($description)
 * @method void pause($vars = [])
 *
 * @SuppressWarnings(PHPMD)
 */
class CustomerGroupBusinessTester extends Actor
{
    use _generated\CustomerGroupBusinessTesterActions;

    protected const int PAGE = 1;

    protected const int MAX_PER_PAGE = 100;

    protected const string SORT_FIELD_NAME = 'name';

    /**
     * @param list<\Generated\Shared\Transfer\CustomerTransfer> $customerTransfers
     * @param list<\Generated\Shared\Transfer\CompanyTransfer> $companyTransfers
     * @param list<\Generated\Shared\Transfer\CompanyBusinessUnitTransfer> $companyBusinessUnitTransfers
     * @param array<string, mixed> $seed
     */
    public function haveCustomerGroupAssignedTo(
        array $customerTransfers = [],
        array $companyTransfers = [],
        array $companyBusinessUnitTransfers = [],
        array $seed = [],
    ): CustomerGroupTransfer {
        return $this->haveCustomerGroup($seed + [
            CustomerGroupTransfer::CUSTOMER_ASSIGNMENT => [
                CustomerGroupToCustomerAssignmentTransfer::IDS_CUSTOMER_TO_ASSIGN => array_map(
                    fn (CustomerTransfer $customerTransfer): int => $customerTransfer->getIdCustomerOrFail(),
                    $customerTransfers,
                ),
                CustomerGroupToCustomerAssignmentTransfer::IDS_CUSTOMER_TO_DE_ASSIGN => [],
            ],
            CustomerGroupTransfer::COMPANY_ASSIGNMENT => [
                CustomerGroupToCompanyAssignmentTransfer::IDS_COMPANY_TO_ASSIGN => array_map(
                    fn (CompanyTransfer $companyTransfer): int => $companyTransfer->getIdCompanyOrFail(),
                    $companyTransfers,
                ),
                CustomerGroupToCompanyAssignmentTransfer::IDS_COMPANY_TO_DE_ASSIGN => [],
            ],
            CustomerGroupTransfer::COMPANY_BUSINESS_UNIT_ASSIGNMENT => [
                CustomerGroupToCompanyBusinessUnitAssignmentTransfer::IDS_COMPANY_BUSINESS_UNIT_TO_ASSIGN => array_map(
                    fn (CompanyBusinessUnitTransfer $companyBusinessUnitTransfer): int => $companyBusinessUnitTransfer->getIdCompanyBusinessUnitOrFail(),
                    $companyBusinessUnitTransfers,
                ),
                CustomerGroupToCompanyBusinessUnitAssignmentTransfer::IDS_COMPANY_BUSINESS_UNIT_TO_DE_ASSIGN => [],
            ],
        ]);
    }

    public function haveCompanyBusinessUnitOfCompany(CompanyTransfer $companyTransfer): CompanyBusinessUnitTransfer
    {
        return $this->haveCompanyBusinessUnit([CompanyBusinessUnitTransfer::FK_COMPANY => $companyTransfer->getIdCompanyOrFail()]);
    }

    public function getCustomerGroupCollection(CustomerGroupConditionsTransfer $customerGroupConditionsTransfer): CustomerGroupCollectionTransfer
    {
        return $this->getLocator()->customerGroup()->facade()->getCustomerGroupCollection(
            (new CustomerGroupCriteriaTransfer())
                ->setCustomerGroupConditions($customerGroupConditionsTransfer)
                ->setPagination((new PaginationTransfer())->setPage(static::PAGE)->setMaxPerPage(static::MAX_PER_PAGE)),
        );
    }

    public function getCustomerGroupPageSortedByName(
        CustomerGroupConditionsTransfer $customerGroupConditionsTransfer,
        int $page,
        int $maxPerPage,
    ): CustomerGroupCollectionTransfer {
        return $this->getLocator()->customerGroup()->facade()->getCustomerGroupCollection(
            (new CustomerGroupCriteriaTransfer())
                ->setCustomerGroupConditions($customerGroupConditionsTransfer)
                ->addSort((new SortTransfer())->setField(static::SORT_FIELD_NAME)->setIsAscending(true))
                ->setPagination((new PaginationTransfer())->setPage($page)->setMaxPerPage($maxPerPage)),
        );
    }

    /**
     * @return list<int>
     */
    public function getCustomerGroupIds(CustomerGroupConditionsTransfer $customerGroupConditionsTransfer): array
    {
        $customerGroupIds = [];

        foreach ($this->getCustomerGroupCollection($customerGroupConditionsTransfer)->getGroups() as $customerGroupTransfer) {
            $customerGroupIds[] = $customerGroupTransfer->getIdCustomerGroupOrFail();
        }

        sort($customerGroupIds);

        return $customerGroupIds;
    }
}
