<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace PyzTest\Glue\CustomerExperienceManagement\BackendApi\Integration\CustomerGroup;

use Generated\Shared\Transfer\CustomerGroupToCustomerAssignmentTransfer;
use Generated\Shared\Transfer\CustomerGroupTransfer;
use PyzTest\Glue\CustomerExperienceManagement\AbstractCustomerExperienceManagementBackendApiTestCase;

abstract class AbstractCustomerGroupBackendApiTestCase extends AbstractCustomerExperienceManagementBackendApiTestCase
{
    protected const string ATTRIBUTE_UUID = 'uuid';

    protected const string ATTRIBUTE_NAME = 'name';

    protected const string ATTRIBUTE_DESCRIPTION = 'description';

    protected const string ATTRIBUTE_CREATED_AT = 'createdAt';

    protected const string ATTRIBUTE_CUSTOMER_REFERENCES = 'customerReferences';

    protected const string ATTRIBUTE_EMAIL = 'email';

    protected const string ATTRIBUTE_FIRST_NAME = 'firstName';

    protected const string ATTRIBUTE_LAST_NAME = 'lastName';

    protected function setUp(): void
    {
        parent::setUp();

        $this->tester->actingAsUser();
    }

    protected function generateCustomerGroupName(): string
    {
        return sprintf('cxm-group-%s', uniqid('', true));
    }

    /**
     * @param array<string, mixed> $seed
     */
    protected function haveCustomerGroup(array $seed = []): CustomerGroupTransfer
    {
        $seed += [CustomerGroupTransfer::NAME => $this->generateCustomerGroupName()];

        return $this->tester->haveCustomerGroup($seed);
    }

    /**
     * @param array<int, \Generated\Shared\Transfer\CustomerTransfer> $customerTransfers
     * @param array<string, mixed> $seed
     */
    protected function haveCustomerGroupWithCustomers(array $customerTransfers, array $seed = []): CustomerGroupTransfer
    {
        $customerIds = [];

        foreach ($customerTransfers as $customerTransfer) {
            $customerIds[] = $customerTransfer->getIdCustomerOrFail();
        }

        return $this->haveCustomerGroup($seed + [
            CustomerGroupTransfer::CUSTOMER_ASSIGNMENT => [
                CustomerGroupToCustomerAssignmentTransfer::IDS_CUSTOMER_TO_ASSIGN => $customerIds,
                CustomerGroupToCustomerAssignmentTransfer::IDS_CUSTOMER_TO_DE_ASSIGN => [],
            ],
        ]);
    }
}
